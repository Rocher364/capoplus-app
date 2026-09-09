<?php

namespace App\Services;

use App\Enums\LoanStatus;
use App\Models\Loan;
use App\Models\LoanSchedule;
use App\Models\Member;
use App\Models\Account;
use App\Models\Repayment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Gere tout le cycle de vie d'un pret : demande, approbation, decaissement
 * (avec generation automatique de l'echeancier) et remboursements.
 */
class LoanService
{
    public function demander(Member $member, Account $account, array $data, User $agent): Loan
    {
        return Loan::create([
            'member_id' => $member->id,
            'account_id' => $account->id,
            'numero_pret' => $this->genererNumeroPret(),
            'montant_demande' => $data['montant_demande'],
            'motif' => $data['motif'],
            'duree_mois' => $data['duree_mois'],
            'type_taux' => $data['type_taux'],
            'taux_interet' => $data['taux_interet'],
            'methode_calcul' => $data['methode_calcul'],
            'statut' => 'demande',
            'demande_par_id' => $agent->id,
            'date_demande' => now(),
        ]);
    }

    public function approuver(Loan $loan, User $admin, ?float $montantApprouve = null): Loan
    {
        if ($loan->statut !== LoanStatus::Demande) {
            throw new InvalidArgumentException('Seul un pret en demande peut etre approuve.');
        }

        $loan->update([
            'statut' => 'approuve',
            'montant_approuve' => $montantApprouve ?? $loan->montant_demande,
            'approuve_par_id' => $admin->id,
            'date_decision' => now(),
        ]);

        return $loan->fresh();
    }

    public function rejeter(Loan $loan, User $admin, string $justification): Loan
    {
        if ($loan->statut !== LoanStatus::Demande) {
            throw new InvalidArgumentException('Seul un pret en demande peut etre rejete.');
        }

        $loan->update([
            'statut' => 'rejete',
            'justification_decision' => $justification,
            'approuve_par_id' => $admin->id,
            'date_decision' => now(),
        ]);

        return $loan->fresh();
    }

    /** Decaisse le pret : verse les fonds sur le compte et genere l'echeancier. */
    public function decaisser(Loan $loan, User $agent, DepotRetraitService $depotService): Loan
    {
        if ($loan->statut !== LoanStatus::Approuve || $loan->montant_approuve === null) {
            throw new InvalidArgumentException('Seul un pret approuve avec un montant valide peut etre decaisse.');
        }

        return DB::transaction(function () use ($loan, $agent, $depotService) {
            $depotService->deposer($loan->account, (float) $loan->montant_approuve, $agent, [
                'moyen' => 'especes',
                'description' => "Decaissement pret {$loan->numero_pret}",
                'operation_type' => Loan::class,
                'operation_id' => $loan->id,
            ]);

            $loan->update([
                'statut' => 'decaisse',
                'date_decaissement' => now(),
            ]);

            $this->genererEcheancier($loan->fresh());

            return $loan->fresh();
        });
    }

    /** Genere l'echeancier selon la methode choisie (simple, degressif, constant). */
    protected function genererEcheancier(Loan $loan): void
    {
        $montant = (float) $loan->montant_approuve;
        $duree = (int) $loan->duree_mois;
        $tauxAnnuel = (float) $loan->taux_interet / 100;
        $tauxMensuel = $tauxAnnuel / 12;
        $depart = $loan->date_decaissement ?? now();

        $lignes = match ($loan->methode_calcul) {
            'simple' => $this->echeancierSimple($montant, $duree, $tauxAnnuel),
            'degressif' => $this->echeancierDegressif($montant, $duree, $tauxMensuel),
            default => $this->echeancierConstant($montant, $duree, $tauxMensuel),
        };

        foreach ($lignes as $i => $ligne) {
            LoanSchedule::create([
                'loan_id' => $loan->id,
                'numero_echeance' => $i + 1,
                'date_echeance' => $depart->copy()->addMonths($i + 1),
                'capital' => round($ligne['capital'], 2),
                'interet' => round($ligne['interet'], 2),
                'montant_total' => round($ligne['capital'] + $ligne['interet'], 2),
                'montant_paye' => 0,
                'solde_restant' => round($ligne['capital'] + $ligne['interet'], 2),
                'statut' => 'a_venir',
            ]);
        }
    }

    /** Interet simple flat sur le capital initial, reparti egalement. */
    protected function echeancierSimple(float $montant, int $duree, float $tauxAnnuel): array
    {
        $interetTotal = $montant * $tauxAnnuel * ($duree / 12);
        $capitalMensuel = $montant / $duree;
        $interetMensuel = $interetTotal / $duree;

        return array_fill(0, $duree, ['capital' => $capitalMensuel, 'interet' => $interetMensuel]);
    }

    /** Capital constant chaque mois, interet degressif sur le solde restant. */
    protected function echeancierDegressif(float $montant, int $duree, float $tauxMensuel): array
    {
        $capitalMensuel = $montant / $duree;
        $solde = $montant;
        $lignes = [];

        for ($i = 0; $i < $duree; $i++) {
            $interet = $solde * $tauxMensuel;
            $lignes[] = ['capital' => $capitalMensuel, 'interet' => $interet];
            $solde -= $capitalMensuel;
        }

        return $lignes;
    }

    /** Mensualite constante (annuite), capital croissant / interet decroissant. */
    protected function echeancierConstant(float $montant, int $duree, float $tauxMensuel): array
    {
        if ($tauxMensuel == 0.0) {
            $capitalMensuel = $montant / $duree;

            return array_fill(0, $duree, ['capital' => $capitalMensuel, 'interet' => 0]);
        }

        $mensualite = $montant * $tauxMensuel / (1 - (1 + $tauxMensuel) ** -$duree);
        $solde = $montant;
        $lignes = [];

        for ($i = 0; $i < $duree; $i++) {
            $interet = $solde * $tauxMensuel;
            $capital = $mensualite - $interet;
            $lignes[] = ['capital' => $capital, 'interet' => $interet];
            $solde -= $capital;
        }

        return $lignes;
    }

    /** Enregistre un remboursement contre une echeance precise. */
    public function enregistrerRemboursement(LoanSchedule $echeance, float $montant, User $agent, array $options = []): Repayment
    {
        if ($montant <= 0) {
            throw new InvalidArgumentException('Le montant doit etre superieur a zero.');
        }

        return DB::transaction(function () use ($echeance, $montant, $agent, $options) {
            $ligne = LoanSchedule::where('id', $echeance->id)->lockForUpdate()->first();
            $loan = $ligne->loan;
            $restant = (float) $ligne->solde_restant;

            if ($loan->statut !== LoanStatus::Decaisse) {
                throw new InvalidArgumentException('Les remboursements sont possibles uniquement pour un pret decaisse.');
            }

            if ($montant > $restant) {
                throw new InvalidArgumentException('Le montant depasse le solde restant de cette echeance.');
            }

            $transaction = app(DepotRetraitService::class)->retirer($loan->account, $montant, $agent, [
                'moyen' => $options['moyen'] ?? 'especes',
                'description' => "Remboursement pret {$loan->numero_pret}, echeance {$ligne->numero_echeance}",
            ]);

            $repayment = Repayment::create([
                'loan_schedule_id' => $ligne->id,
                'user_id' => $agent->id,
                'transaction_id' => $transaction->id,
                'reference' => 'REM-'.now()->format('Ymd').'-'.strtoupper(Str::random(6)),
                'montant' => $montant,
                'moyen' => $options['moyen'] ?? 'especes',
                'effectue_le' => now(),
                'notes' => $options['notes'] ?? null,
            ]);

            $transaction->update([
                'operation_type' => Repayment::class,
                'operation_id' => $repayment->id,
            ]);

            $nouveauPaye = bcadd((string) $ligne->montant_paye, (string) $montant, 2);
            $nouveauRestant = bcsub((string) $ligne->montant_total, $nouveauPaye, 2);

            $ligne->update([
                'montant_paye' => $nouveauPaye,
                'solde_restant' => max(0, (float) $nouveauRestant),
                'statut' => (float) $nouveauRestant <= 0 ? 'payee' : 'partiellement_payee',
            ]);

            $echeancesRestantes = $loan->schedules()->where('statut', '!=', 'payee')->count();
            if ($echeancesRestantes === 0) {
                $loan->update(['statut' => 'solde']);
            }

            return $repayment;
        });
    }

    protected function genererNumeroPret(): string
    {
        do {
            $numero = 'PRT-'.now()->format('Y').'-'.strtoupper(Str::random(6));
        } while (Loan::where('numero_pret', $numero)->exists());

        return $numero;
    }
}
