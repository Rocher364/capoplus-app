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
        $loan = Loan::create([
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

        ActivityLogger::log($agent, 'pret.demande', $loan, [
            'numero_pret' => $loan->numero_pret,
            'montant_demande' => $data['montant_demande'],
            'compte_id' => $account->id,
            'member_id' => $member->id,
            'statut' => 'demande',
        ]);

        return $loan->fresh();
    }

    public function approuver(Loan $loan, User $admin, ?float $montantApprouve = null): Loan
    {
        if ($loan->statut !== LoanStatus::Demande) {
            throw new InvalidArgumentException('Seul un pret en demande peut etre approuve.');
        }

        // Defense-in-depth : verification du role
        if (! $admin->isAdmin()) {
            throw new \App\Exceptions\UnauthorizedActionException('Seul un administrateur peut approuver un pret.');
        }

        // Defense-in-depth : prevention de l'auto-approbation
        if ($admin->id === $loan->demande_par_id) {
            throw new \App\Exceptions\UnauthorizedActionException('Vous ne pouvez pas approuver un pret que vous avez demande.');
        }

        // Defense-in-depth : le montant approuve ne peut pas depasser le montant demande
        $montantFinal = $montantApprouve !== null ? number_format($montantApprouve, 2, '.', '') : (string) $loan->montant_demande;
        if (bccomp($montantFinal, (string) $loan->montant_demande, 2) > 0) {
            throw new InvalidArgumentException('Le montant approuve ne peut pas depasser le montant demande.');
        }

        $anciennes = [
            'statut' => $loan->statut->value ?? (string) $loan->statut,
            'montant_approuve' => $loan->montant_approuve ? (string) $loan->montant_approuve : null,
        ];

        $loan->forceFill([
            'statut' => 'approuve',
            'montant_approuve' => $montantFinal,
            'approuve_par_id' => $admin->id,
            'date_decision' => now(),
        ])->save();

        ActivityLogger::log($admin, 'pret.approuve', $loan, [
            'numero_pret' => $loan->numero_pret,
            'compte_id' => $loan->account_id,
            'member_id' => $loan->member_id,
            'montant_approuve' => $montantFinal,
            'statut' => 'approuve',
        ], $anciennes);

        return $loan->fresh();
    }

    public function rejeter(Loan $loan, User $admin, string $justification): Loan
    {
        if ($loan->statut !== LoanStatus::Demande) {
            throw new InvalidArgumentException('Seul un pret en demande peut etre rejete.');
        }

        // Defense-in-depth : verification du role
        if (! $admin->isAdmin()) {
            throw new \App\Exceptions\UnauthorizedActionException('Seul un administrateur peut rejeter un pret.');
        }

        // Defense-in-depth : prevention de l'auto-rejet
        if ($admin->id === $loan->demande_par_id) {
            throw new \App\Exceptions\UnauthorizedActionException('Vous ne pouvez pas rejeter un pret que vous avez demande.');
        }

        $anciennes = [
            'statut' => $loan->statut->value ?? (string) $loan->statut,
        ];

        $loan->forceFill([
            'statut' => 'rejete',
            'justification_decision' => $justification,
            'approuve_par_id' => $admin->id,
            'date_decision' => now(),
        ])->save();

        ActivityLogger::log($admin, 'pret.rejete', $loan, [
            'numero_pret' => $loan->numero_pret,
            'compte_id' => $loan->account_id,
            'member_id' => $loan->member_id,
            'justification' => $justification,
            'statut' => 'rejete',
        ], $anciennes);

        return $loan->fresh();
    }

    /** Decaisse le pret : verse les fonds sur le compte et genere l'echeancier. */
    public function decaisser(Loan $loan, User $agent, DepotRetraitService $depotService): Loan
    {
        // Defense-in-depth : verification du role
        if (! $agent->isAdmin()) {
            throw new \App\Exceptions\UnauthorizedActionException('Seul un administrateur peut decaisser un pret.');
        }

        return DB::transaction(function () use ($loan, $agent, $depotService) {
            // Verrouillage pessimiste pour eviter le double decaissement
            $loan = Loan::where('id', $loan->id)->lockForUpdate()->firstOrFail();

            if ($loan->statut !== LoanStatus::Approuve || $loan->montant_approuve === null || bccomp((string) $loan->montant_approuve, '0.00', 2) <= 0) {
                throw new InvalidArgumentException('Seul un pret approuve avec un montant valide peut etre decaisse.');
            }

            $anciennes = [
                'statut' => $loan->statut->value ?? (string) $loan->statut,
            ];

            $depotService->deposer($loan->account, (string) $loan->montant_approuve, $agent, [
                'moyen' => 'especes',
                'description' => "Decaissement pret {$loan->numero_pret}",
                'operation_type' => Loan::class,
                'operation_id' => $loan->id,
            ]);

            $loan->forceFill([
                'statut' => LoanStatus::Decaisse,
                'date_decaissement' => now(),
            ])->save();

            $this->genererEcheancier($loan->fresh());

            ActivityLogger::log($agent, 'pret.decaisse', $loan, [
                'numero_pret' => $loan->numero_pret,
                'compte_id' => $loan->account_id,
                'member_id' => $loan->member_id,
                'montant' => (string) $loan->montant_approuve,
                'statut' => 'decaisse',
            ], $anciennes);

            return $loan->fresh();
        });
    }

    /** Genere l'echeancier selon la methode choisie (simple, degressif, constant). */
    protected function genererEcheancier(Loan $loan): void
    {
        $montant = (string) $loan->montant_approuve;
        $duree = (int) $loan->duree_mois;
        $tauxAnnuel = (float) $loan->taux_interet / 100;
        $tauxMensuel = $tauxAnnuel / 12;
        $depart = $loan->date_decaissement ?? now();

        $lignes = match ($loan->methode_calcul) {
            'simple' => $this->echeancierSimple((float) $montant, $duree, $tauxAnnuel),
            'degressif' => $this->echeancierDegressif((float) $montant, $duree, $tauxMensuel),
            default => $this->echeancierConstant((float) $montant, $duree, $tauxMensuel),
        };

        $cumulCapital = '0.00';
        $totalLignes = count($lignes);

        foreach ($lignes as $i => $ligne) {
            $isLast = ($i === $totalLignes - 1);
            if ($isLast) {
                // VULN-11 : Ajustement de la derniere echeance par difference pour garantir SUM(capital) == montant_approuve
                $capital = bcsub($montant, $cumulCapital, 2);
            } else {
                $capital = number_format(round($ligne['capital'], 2), 2, '.', '');
                $cumulCapital = bcadd($cumulCapital, $capital, 2);
            }

            $interet = number_format(round($ligne['interet'], 2), 2, '.', '');
            $montantTotal = bcadd($capital, $interet, 2);

            LoanSchedule::create([
                'loan_id' => $loan->id,
                'numero_echeance' => $i + 1,
                'date_echeance' => $depart->copy()->addMonths($i + 1),
                'capital' => $capital,
                'interet' => $interet,
                'montant_total' => $montantTotal,
                'montant_paye' => '0.00',
                'solde_restant' => $montantTotal,
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
    public function enregistrerRemboursement(LoanSchedule $echeance, float|string $montant, User $agent, array $options = []): Repayment
    {
        $montantStr = is_numeric($montant) ? number_format((float) $montant, 2, '.', '') : '0.00';

        if (bccomp($montantStr, '0.00', 2) <= 0) {
            throw new InvalidArgumentException('Le montant doit etre superieur a zero.');
        }

        // Defense-in-depth : verification de role (un auditeur ne peut pas enregistrer de remboursement)
        if ($agent->isAuditeur()) {
            throw new \App\Exceptions\UnauthorizedActionException('Un auditeur ne peut pas enregistrer de remboursement.');
        }

        // Recuperer l'identite unique du paiement (reference ou idempotency_key)
        $reference = $options['reference'] ?? $options['idempotency_key'] ?? null;

        // Idempotence : si une reference de remboursement est fournie et existe deja avant transaction
        if (! empty($reference)) {
            $existing = Repayment::where('reference', $reference)->first();
            if ($existing) {
                return $existing;
            }
        }

        return DB::transaction(function () use ($echeance, $montantStr, $agent, $options, $reference) {
            $ligne = LoanSchedule::where('id', $echeance->id)->lockForUpdate()->firstOrFail();
            $loan = Loan::where('id', $ligne->loan_id)->lockForUpdate()->firstOrFail();

            // Re-verification d'idempotence a l'interieur du verrou pour les requetes concurrentes
            if (! empty($reference)) {
                $existing = Repayment::where('reference', $reference)->first();
                if ($existing) {
                    return $existing;
                }
            }

            // VULN-06 : Les prets decaisses ET en retard peuvent recevoir des remboursements
            if (! in_array($loan->statut, [LoanStatus::Decaisse, LoanStatus::EnRetard])) {
                throw new InvalidArgumentException('Les remboursements sont possibles uniquement pour un pret decaisse ou en retard.');
            }

            if ($ligne->statut === 'payee' || bccomp((string) $ligne->solde_restant, '0.00', 2) <= 0) {
                throw new InvalidArgumentException('Cette echeance est deja integralement payee.');
            }

            if (bccomp($montantStr, (string) $ligne->solde_restant, 2) > 0) {
                throw new InvalidArgumentException('Le montant depasse le solde restant de cette echeance.');
            }

            $transaction = app(DepotRetraitService::class)->retirer($loan->account, $montantStr, $agent, [
                'moyen' => $options['moyen'] ?? 'especes',
                'description' => "Remboursement pret {$loan->numero_pret}, echeance {$ligne->numero_echeance}",
            ]);

            $refFinale = $reference ?? ('REM-'.now()->format('Ymd').'-'.strtoupper(Str::random(6)));

            $repayment = Repayment::create([
                'loan_schedule_id' => $ligne->id,
                'user_id' => $agent->id,
                'transaction_id' => $transaction->id,
                'reference' => $refFinale,
                'montant' => $montantStr,
                'moyen' => $options['moyen'] ?? 'especes',
                'effectue_le' => now(),
                'notes' => $options['notes'] ?? null,
            ]);

            $transaction->update([
                'operation_type' => Repayment::class,
                'operation_id' => $repayment->id,
            ]);

            $nouveauPaye = bcadd((string) $ligne->montant_paye, $montantStr, 2);
            $nouveauRestant = bcsub((string) $ligne->montant_total, $nouveauPaye, 2);
            $isPayee = bccomp($nouveauRestant, '0.00', 2) <= 0;

            $ligne->update([
                'montant_paye' => $nouveauPaye,
                'solde_restant' => $isPayee ? '0.00' : $nouveauRestant,
                'statut' => $isPayee ? 'payee' : 'partiellement_payee',
            ]);

            // Mise a jour du statut du pret
            $echeancesNonPayees = $loan->schedules()->where('statut', '!=', 'payee')->count();
            if ($echeancesNonPayees === 0) {
                $loan->forceFill(['statut' => LoanStatus::Solde])->save();
            } elseif ($loan->statut === LoanStatus::EnRetard) {
                // Si le pret etait en retard et qu'aucune echeance n'est plus en retard, repasser en decaisse
                $echeancesEnRetard = $loan->schedules()->where('statut', 'en_retard')->count();
                if ($echeancesEnRetard === 0) {
                    $loan->forceFill(['statut' => LoanStatus::Decaisse])->save();
                }
            }

            ActivityLogger::log($agent, 'pret.remboursement', $loan, [
                'numero_pret' => $loan->numero_pret,
                'compte_id' => $loan->account_id,
                'membre_id' => $loan->member_id,
                'loan_id' => $loan->id,
                'loan_schedule_id' => $ligne->id,
                'repayment_id' => $repayment->id,
                'transaction_id' => $transaction->id,
                'echeance' => $ligne->numero_echeance,
                'montant' => $montantStr,
                'reference' => $repayment->reference,
                'solde_restant_echeance' => $isPayee ? '0.00' : $nouveauRestant,
                'statut_echeance' => $isPayee ? 'payee' : 'partiellement_payee',
            ], [
                'solde_restant_echeance' => (string) $echeance->solde_restant,
                'statut_echeance' => $echeance->statut,
            ]);

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
