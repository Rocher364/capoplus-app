<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Une echeance de l'echeancier d'un pret : capital, interet et statut de paiement. */
class LoanSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_id',
        'numero_echeance',
        'date_echeance',
        'capital',
        'interet',
        'montant_total',
        'montant_paye',
        'solde_restant',
        'statut',
    ];

    protected function casts(): array
    {
        return [
            'date_echeance' => 'date',
            'capital' => 'decimal:2',
            'interet' => 'decimal:2',
            'montant_total' => 'decimal:2',
            'montant_paye' => 'decimal:2',
            'solde_restant' => 'decimal:2',
        ];
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function repayments(): HasMany
    {
        return $this->hasMany(Repayment::class);
    }

    public function estEnRetard(): bool
    {
        return $this->statut !== 'payee' && $this->date_echeance->isPast();
    }

    public function estSoldee(): bool
    {
        return $this->statut === 'payee';
    }
}
