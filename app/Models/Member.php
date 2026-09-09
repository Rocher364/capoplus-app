<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

/** 
 * Fiche d'un membre/client : titulaire d'un compte, epargnant ou emprunteur.
 */
class Member extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Champ ki gen dwa anrejistre pou anpeche Mass Assignment Vulnerability.
     */
    protected $fillable = [
        'user_id',
        'numero_membre',
        'prenom',
        'nom',
        'nif_cin',
        'date_naissance',
        'sexe',
        'type_piece_identite',
        'numero_piece_identite',
        'photo_path',
        'piece_identite_path',
        'telephone',
        'email',
        'adresse',
        'statut',
        'cree_par_id',
        'notes',
    ];

    /**
     * Konvèsyon automatik pou tip de done yo.
     */
    protected function casts(): array
    {
        return [
            'date_naissance' => 'date',
            'created_at'     => 'datetime',
            'updated_at'     => 'datetime',
            'deleted_at'     => 'datetime',
        ];
    }

    /**
     * Netwaye e sekirize Prenom anvan l anrejistre (Netwaye espas, premye lèt an majiskil).
     */
    protected function prenom(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => $value ? ucfirst(trim(strip_tags($value))) : null,
        );
    }

    /**
     * Netwaye e majiskil Nom an anvan l anrejistre.
     */
    protected function nom(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => $value ? mb_strtoupper(trim(strip_tags($value))) : null,
        );
    }

    /**
     * Netwaye Email la (Mete tout an miniskil epi efase espas).
     */
    protected function email(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => $value ? strtolower(trim($value)) : null,
        );
    }

    /**
     * Non konplè membre a (Prenom + NOM).
     */
    public function getNomCompletAttribute(): string
    {
        return trim("{$this->prenom} {$this->nom}");
    }

    /**
     * Compte de connexion portail, si activé.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Agent/administrateur ayant créé la fiche membre.
     */
    public function creePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cree_par_id');
    }

    /**
     * Portefeuille numérique du membre (1 Membre = 1 Compte).
     */
    public function account(): HasOne
    {
        return $this->hasOne(Account::class);
    }

    /**
     * Liste des prêts associés au membre.
     */
    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }
}