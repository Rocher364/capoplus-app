<?php

namespace App\Services;

use App\Exceptions\CompteBloqueException;
use App\Exceptions\SoldeInsuffisantException;
use App\Models\Account;
use App\Models\AuditLog;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DepotRetraitService
{
    /**
     * @throws CompteBloqueException
     */
    public function deposer(Account $account, float|string $montant, User $agent, array $options = []): Transaction
    {
        $this->validerMontant($montant, 'depot', empty($options['operation_type']));

        return DB::transaction(function () use ($account, $montant, $agent, $options) {
            $compte = Account::where('id', $account->id)->lockForUpdate()->firstOrFail();

            if (! $compte->estActif()) {
                throw new CompteBloqueException;
            }

            $montantStr = is_string($montant) ? $montant : number_format($montant, 2, '.', '');
            $soldeAvant = (string) $compte->solde;
            $nouveauSolde = bcadd($soldeAvant, $montantStr, 2);

            $transaction = Transaction::create([
                'account_id' => $compte->id,
                'user_id' => $agent->id,
                'reference' => $this->genererReference('DEP'),
                'type' => 'depot',
                'montant' => $montant,
                'moyen' => $options['moyen'] ?? 'especes',
                'solde_apres' => $nouveauSolde,
                'effectuee_le' => now(),
                'description' => $options['description'] ?? null,
                'operation_type' => $options['operation_type'] ?? null,
                'operation_id' => $options['operation_id'] ?? null,
            ]);

            $compte->actualiserSolde($nouveauSolde);

            $this->journaliser($agent, 'transaction.depot', $transaction, [
                'montant' => $montant,
                'compte' => $compte->numero_compte,
                'account_id' => $compte->id,
                'transaction_id' => $transaction->id,
                'reference' => $transaction->reference,
                'solde_apres' => $nouveauSolde,
            ], [
                'solde' => $soldeAvant,
            ]);

            return $transaction;
        });
    }

    /**
     * @throws CompteBloqueException
     * @throws SoldeInsuffisantException
     */
    public function retirer(Account $account, float|string $montant, User $agent, array $options = []): Transaction
    {
        $this->validerMontant($montant, 'retrait');

        return DB::transaction(function () use ($account, $montant, $agent, $options) {
            $compte = Account::where('id', $account->id)->lockForUpdate()->firstOrFail();

            if (! $compte->estActif()) {
                throw new CompteBloqueException;
            }

            $montantStr = is_string($montant) ? $montant : number_format($montant, 2, '.', '');

            if (bccomp((string) $compte->solde, $montantStr, 2) < 0) {
                throw new SoldeInsuffisantException;
            }

            $soldeAvant = (string) $compte->solde;
            $nouveauSolde = bcsub((string) $compte->solde, $montantStr, 2);

            $transaction = Transaction::create([
                'account_id' => $compte->id,
                'user_id' => $agent->id,
                'reference' => $this->genererReference('RET'),
                'type' => 'retrait',
                'montant' => $montant,
                'moyen' => $options['moyen'] ?? 'especes',
                'solde_apres' => $nouveauSolde,
                'effectuee_le' => now(),
                'description' => $options['description'] ?? null,
                'operation_type' => $options['operation_type'] ?? null,
                'operation_id' => $options['operation_id'] ?? null,
            ]);

            $compte->actualiserSolde($nouveauSolde);

            $this->journaliser($agent, 'transaction.retrait', $transaction, [
                'montant' => $montant,
                'compte' => $compte->numero_compte,
                'account_id' => $compte->id,
                'transaction_id' => $transaction->id,
                'reference' => $transaction->reference,
                'solde_apres' => $nouveauSolde,
            ], [
                'solde' => $soldeAvant,
            ]);

            return $transaction;
        });
    }

    protected function validerMontant(float|string $montant, string $type = 'depot', bool $verifierPlafond = true): void
    {
        $montantStr = is_string($montant) ? $montant : number_format($montant, 2, '.', '');

        if (bccomp($montantStr, '0.00', 2) <= 0) {
            throw new \InvalidArgumentException('Le montant doit etre superieur a zero.');
        }

        if ($type === 'depot' && $verifierPlafond) {
            $maxDeposit = config('capoplus.max_deposit');
            if ($maxDeposit !== null) {
                $maxDepositStr = is_string($maxDeposit) ? $maxDeposit : (string) $maxDeposit;
                if (bccomp($montantStr, $maxDepositStr, 2) > 0) {
                    throw new \InvalidArgumentException(sprintf('Le montant du depot depasse la limite autorisee (%s HTG).', $maxDepositStr));
                }
            }
        }
    }

    protected function genererReference(string $prefixe): string
    {
        return sprintf(
            '%s-%s-%s',
            $prefixe,
            now()->format('Ymd'),
            strtoupper(Str::random(6))
        );
    }

    protected function journaliser(User $agent, string $action, Transaction $transaction, array $nouvellesValeurs, ?array $anciennesValeurs = null): void
    {
        \App\Services\ActivityLogger::log($agent, $action, $transaction, $nouvellesValeurs, $anciennesValeurs);
    }
}
