<?php

namespace App\Enums;

enum LoanStatus: string
{
    case Demande = 'demande';
    case Approuve = 'approuve';
    case Rejete = 'rejete';
    case Decaisse = 'decaisse';
    case EnRetard = 'en_retard';
    case Solde = 'solde';
    case Annule = 'annule';

    public function label(): string
    {
        return match ($this) {
            self::Demande => 'Demande en attente',
            self::Approuve => 'Approuve',
            self::Rejete => 'Rejete',
            self::Decaisse => 'Decaisse',
            self::EnRetard => 'En retard',
            self::Solde => 'Solde',
            self::Annule => 'Annule',
        };
    }
}
