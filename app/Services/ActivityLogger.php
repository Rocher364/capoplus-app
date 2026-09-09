<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Point d'entree unique pour journaliser une action dans audit_logs.
 * Chaque action sensible (creation, modification, suppression, blocage,
 * transaction, decision sur un pret...) doit passer par ici, pour que le
 * tableau de bord du Directeur reflete TOUT ce qui se passe dans le systeme.
 */
class ActivityLogger
{
    public static function log(?User $auteur, string $action, ?Model $sujet = null, array $details = []): AuditLog
    {
        return AuditLog::create([
            'user_id' => $auteur?->id,
            'action' => $action,
            'auditable_type' => $sujet ? get_class($sujet) : null,
            'auditable_id' => $sujet?->id,
            'nouvelles_valeurs' => $details ?: null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);
    }
}
