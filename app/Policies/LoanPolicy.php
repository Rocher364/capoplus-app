<?php

namespace App\Policies;

use App\Enums\LoanStatus;
use App\Models\Loan;
use App\Models\User;

/**
 * Politique d'autorisation pour les prets.
 *
 * Regles :
 * - Tout utilisateur authentifie (admin, agent) peut consulter et creer des prets.
 * - Seul un admin peut approuver, rejeter ou decaisser.
 * - L'utilisateur qui a demande le pret ne peut pas l'approuver lui-meme (separation des taches).
 * - Le montant approuve ne peut jamais depasser le montant demande.
 * - Le remboursement est autorise sur les prets decaisses ou en retard.
 */
class LoanPolicy
{
    /** Tout utilisateur non-auditeur peut voir la liste des prets. */
    public function viewAny(User $user): bool
    {
        return ! $user->isAuditeur();
    }

    /** Tout utilisateur non-auditeur peut voir un pret. */
    public function view(User $user, Loan $loan): bool
    {
        return ! $user->isAuditeur();
    }

    /** Tout utilisateur non-auditeur peut creer une demande de pret. */
    public function create(User $user): bool
    {
        return ! $user->isAuditeur();
    }

    /** Seul un admin peut modifier un pret (hors workflow). */
    public function update(User $user, Loan $loan): bool
    {
        return $user->isAdmin();
    }

    /**
     * Seul un admin peut approuver un pret.
     * Prevention de l'auto-approbation : l'admin ne peut pas approuver un pret qu'il a lui-meme demande.
     */
    public function approve(User $user, Loan $loan): bool
    {
        return $user->isAdmin()
            && $user->id !== $loan->demande_par_id;
    }

    /**
     * Seul un admin peut rejeter un pret.
     * Prevention de l'auto-rejet : l'admin ne peut pas rejeter un pret qu'il a lui-meme demande.
     */
    public function reject(User $user, Loan $loan): bool
    {
        return $user->isAdmin()
            && $user->id !== $loan->demande_par_id;
    }

    /** Seul un admin peut decaisser un pret approuve. */
    public function disburse(User $user, Loan $loan): bool
    {
        return $user->isAdmin();
    }

    /**
     * Tout utilisateur non-auditeur peut enregistrer un remboursement
     * sur un pret decaisse ou en retard.
     */
    public function repay(User $user, Loan $loan): bool
    {
        return ! $user->isAuditeur()
            && in_array($loan->statut, [LoanStatus::Decaisse, LoanStatus::EnRetard]);
    }
}
