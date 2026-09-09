<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Remboursement enregistre contre une echeance precise de l'echeancier d'un pret. */
class Repayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_schedule_id',
        'user_id',
        'transaction_id',
        'reference',
        'montant',
        'moyen',
        'effectue_le',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'montant' => 'decimal:2',
            'effectue_le' => 'datetime',
        ];
    }

    public function loanSchedule(): BelongsTo
    {
        return $this->belongsTo(LoanSchedule::class);
    }

    /** Agent/administrateur ayant enregistre le remboursement. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
