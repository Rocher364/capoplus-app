<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Agent = 'agent';
    case Auditeur = 'auditeur';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrateur',
            self::Agent => 'Agent / Caissier',
            self::Auditeur => 'Auditeur',
        };
    }
}
