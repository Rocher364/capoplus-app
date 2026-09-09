<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Portefeuille numerique ("wallet") lie a un membre, avec solde en temps reel. */
class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id',
        'numero_compte',
        'solde',
        'statut',
        'raison_blocage',
        'bloque_par_id',
        'bloque_le',
    ];

    protected function casts(): array
    {
        return [
            'solde' => 'decimal:2',
            'bloque_le' => 'datetime',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function bloquePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'bloque_par_id');
    }

    /** Journal complet des depots et retraits de ce compte. */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function estActif(): bool
    {
        return $this->statut === 'actif';
    }
}
