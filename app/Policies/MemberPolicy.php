<?php

namespace App\Policies;

use App\Models\Member;
use App\Models\User;

/**
 * Politique d'autorisation pour les membres/clients.
 *
 * Regles :
 * - Tout utilisateur non-auditeur peut consulter, creer et modifier des membres.
 * - Seul un admin peut supprimer un membre (operation irreversible).
 */
class MemberPolicy
{
    /** Tout utilisateur non-auditeur peut voir la liste des membres. */
    public function viewAny(User $user): bool
    {
        return ! $user->isAuditeur();
    }

    /** Tout utilisateur non-auditeur peut voir un membre. */
    public function view(User $user, Member $member): bool
    {
        return ! $user->isAuditeur();
    }

    /** Tout utilisateur non-auditeur peut creer un membre. */
    public function create(User $user): bool
    {
        return ! $user->isAuditeur();
    }

    /** Tout utilisateur non-auditeur peut modifier un membre. */
    public function update(User $user, Member $member): bool
    {
        return ! $user->isAuditeur();
    }

    /**
     * Seul un admin peut supprimer un membre.
     * La suppression est une operation irreversible necessitant un niveau d'autorisation superieur.
     */
    public function delete(User $user, Member $member): bool
    {
        return $user->isAdmin();
    }
}
