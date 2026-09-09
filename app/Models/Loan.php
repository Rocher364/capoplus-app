<?php

namespace App\Models;

use App\Enums\LoanStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Micro-credit demande par un membre, avec echeancier genere automatiquement. */
class Loan extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id',
        'account_id',
        'numero_pret',
        'montant_demande',
        'montant_approuve',
        'motif',
        'duree_mois',
        'type_taux',
        'taux_interet',
        'methode_calcul',
        'statut',
        'justification_decision',
        'demande_par_id',
        'approuve_par_id',
        'date_demande',
        'date_decision',
        'date_decaissement',
    ];

    protected function casts(): array
    {
        return [
            'montant_demande' => 'decimal:2',
            'montant_approuve' => 'decimal:2',
            'taux_interet' => 'decimal:3',
            'statut' => LoanStatus::class,
            'date_demande' => 'datetime',
            'date_decision' => 'datetime',
            'date_decaissement' => 'datetime',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function demandePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'demande_par_id');
    }

    public function approuvePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approuve_par_id');
    }

    /** Echeancier de remboursement complet, ordonne par numero d'echeance. */
    public function schedules(): HasMany
    {
        return $this->hasMany(LoanSchedule::class)->orderBy('numero_echeance');
    }

    public function echeancesEnRetard(): HasMany
    {
        return $this->schedules()->where('statut', 'en_retard');
    }

    /** Capital + interets restant dus sur l'ensemble de l'echeancier. */
    public function getSoldeRestantDuAttribute(): string
    {
        return (string) $this->schedules()->sum('solde_restant');
    }
}
