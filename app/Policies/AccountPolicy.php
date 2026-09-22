<?php

namespace App\Policies;

use App\Models\Account;
use App\Models\User;

/**
 * Politique d'autorisation pour les comptes financiers.
 *
 * Regles :
 * - Tout utilisateur non-auditeur peut consulter et creer des comptes.
 * - Les depots et retraits sont autorises pour admin et agent.
 * - Le blocage est autorise pour admin et agent.
 * - Le deblocage est reserve uniquement aux admins (operation sensible).
 */
class AccountPolicy
{
    /** Tout utilisateur non-auditeur peut voir la liste des comptes. */
    public function viewAny(User $user): bool
    {
        return ! $user->isAuditeur();
    }

    /** Tout utilisateur non-auditeur peut voir un compte. */
    public function view(User $user, Account $account): bool
    {
        return ! $user->isAuditeur();
    }

    /** Tout utilisateur non-auditeur peut creer un compte. */
    public function create(User $user): bool
    {
        return ! $user->isAuditeur();
    }

    /** Admin et Agent peuvent effectuer des depots. */
    public function deposer(User $user, Account $account): bool
    {
        return ! $user->isAuditeur();
    }

    /** Admin et Agent peuvent effectuer des retraits. */
    public function retirer(User $user, Account $account): bool
    {
        return ! $user->isAuditeur();
    }

    /** Admin et Agent peuvent bloquer un compte. */
    public function bloquer(User $user, Account $account): bool
    {
        return ! $user->isAuditeur();
    }

    /**
     * Seul un admin peut debloquer un compte.
     * Le deblocage est une operation sensible necessitant un niveau d'autorisation superieur.
     */
    public function debloquer(User $user, Account $account): bool
    {
        return $user->isAdmin();
    }
}
