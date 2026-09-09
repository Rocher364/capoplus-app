<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Piste d'audit : historise chaque action sensible avec son auteur et sa date
 * (voir 4.4 et 4.5 du cahier des charges). Table volontairement en lecture seule
 * une fois ecrite - pas de $fillable ouvert au-dela de la creation controlee.
 */
class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'action',
        'auditable_type',
        'auditable_id',
        'anciennes_valeurs',
        'nouvelles_valeurs',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'anciennes_valeurs' => 'array',
            'nouvelles_valeurs' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /** Auteur de l'action (nullable pour les evenements systeme). */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Modele concerne par l'action (Member, Account, Loan, ...). */
    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }
}
