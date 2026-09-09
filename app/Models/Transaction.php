<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Ecriture atomique de depot ou de retrait (voir 4.2 du cahier des charges).
 * Une fois creee, une transaction ne doit jamais etre modifiee : toute correction
 * passe par une nouvelle ecriture d'ajustement pour preserver la piste d'audit.
 */
class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'user_id',
        'reference',
        'type',
        'montant',
        'moyen',
        'solde_apres',
        'effectuee_le',
        'description',
        'operation_type',
        'operation_id',
    ];

    protected function casts(): array
    {
        return [
            'montant' => 'decimal:2',
            'solde_apres' => 'decimal:2',
            'effectuee_le' => 'datetime',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /** Agent/administrateur ayant enregistre l'operation. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Rattachement optionnel a un decaissement de pret ou un remboursement. */
    public function operation(): MorphTo
    {
        return $this->morphTo();
    }

    public function estDepot(): bool
    {
        return $this->type === 'depot';
    }

    public function estRetrait(): bool
    {
        return $this->type === 'retrait';
    }
}
