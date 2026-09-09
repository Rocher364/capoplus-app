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
    public function deposer(Account $account, float $montant, User $agent, array $options = []): Transaction
    {
        $this->validerMontant($montant);

        return DB::transaction(function () use ($account, $montant, $agent, $options) {
            $compte = Account::where('id', $account->id)->lockForUpdate()->first();

            if (! $compte->estActif()) {
                throw new CompteBloqueException;
            }

            $nouveauSolde = bcadd((string) $compte->solde, (string) $montant, 2);

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

            $compte->update(['solde' => $nouveauSolde]);

            $this->journaliser($agent, 'transaction.depot', $transaction, [
                'montant' => $montant,
                'compte' => $compte->numero_compte,
                'solde_apres' => $nouveauSolde,
            ]);

            return $transaction;
        });
    }

    /**
     * @throws CompteBloqueException
     * @throws SoldeInsuffisantException
     */
    public function retirer(Account $account, float $montant, User $agent, array $options = []): Transaction
    {
        $this->validerMontant($montant);

        return DB::transaction(function () use ($account, $montant, $agent, $options) {
            $compte = Account::where('id', $account->id)->lockForUpdate()->first();

            if (! $compte->estActif()) {
                throw new CompteBloqueException;
            }

            if (bccomp((string) $compte->solde, (string) $montant, 2) < 0) {
                throw new SoldeInsuffisantException;
            }

            $nouveauSolde = bcsub((string) $compte->solde, (string) $montant, 2);

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

            $compte->update(['solde' => $nouveauSolde]);

            $this->journaliser($agent, 'transaction.retrait', $transaction, [
                'montant' => $montant,
                'compte' => $compte->numero_compte,
                'solde_apres' => $nouveauSolde,
            ]);

            return $transaction;
        });
    }

    protected function validerMontant(float $montant): void
    {
        if ($montant <= 0) {
            throw new \InvalidArgumentException('Le montant doit etre superieur a zero.');
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

    protected function journaliser(User $agent, string $action, Transaction $transaction, array $nouvellesValeurs): void
    {
        AuditLog::create([
            'user_id' => $agent->id,
            'action' => $action,
            'auditable_type' => Transaction::class,
            'auditable_id' => $transaction->id,
            'nouvelles_valeurs' => $nouvellesValeurs,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);
    }
}
