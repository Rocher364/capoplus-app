<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * Compte d'acces a la plateforme : Administrateur, Agent/Caissier ou Auditeur.
 * Les Membres/Clients sont geres via le modele Member (voir 3. Acteurs du systeme).
 */
#[Fillable(['name', 'email', 'password', 'role', 'telephone', 'statut'])]
#[Hidden(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'verrouille_jusqu_a' => 'datetime',
            'derniere_connexion_a' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isAgent(): bool
    {
        return $this->role === UserRole::Agent;
    }

    public function isAuditeur(): bool
    {
        return $this->role === UserRole::Auditeur;
    }

    /** Fiche(s) membre liee(s), si ce compte dispose aussi d'un acces portail client. */
    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }

    /** Transactions (depots/retraits) enregistrees par cet agent au guichet. */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /** Prets approuves par cet administrateur. */
    public function pretsApprouves(): HasMany
    {
        return $this->hasMany(Loan::class, 'approuve_par_id');
    }

    /** Remboursements enregistres par cet agent. */
    public function remboursementsEnregistres(): HasMany
    {
        return $this->hasMany(Repayment::class);
    }

    /** Entrees de la piste d'audit dont cet utilisateur est l'auteur. */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }
}
