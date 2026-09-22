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
    ];

    protected static function booted(): void
    {
        static::saving(function (Account $account) {
            if (bccomp((string) ($account->solde ?? 0), '0', 2) < 0) {
                throw new \InvalidArgumentException('Le solde du compte ne peut pas être négatif.');
            }
        });
    }

    protected function casts(): array
    {
        return [
            'solde' => 'decimal:2',
            'bloque_le' => 'datetime',
        ];
    }

    public function bloquer(string $raison, User $bloquePar): void
    {
        $this->forceFill([
            'statut' => 'bloque',
            'raison_blocage' => $raison,
            'bloque_par_id' => $bloquePar->id,
            'bloque_le' => now(),
        ])->save();
    }

    public function debloquer(): void
    {
        $this->forceFill([
            'statut' => 'actif',
            'raison_blocage' => null,
            'bloque_par_id' => null,
            'bloque_le' => null,
        ])->save();
    }

    public function actualiserSolde(string $nouveauSolde): void
    {
        if (bccomp((string) $nouveauSolde, '0', 2) < 0) {
            throw new \InvalidArgumentException('Le solde ne peut pas être négatif.');
        }

        $this->forceFill(['solde' => $nouveauSolde])->save();
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
