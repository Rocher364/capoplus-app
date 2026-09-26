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
    /** Liste des clés sensibles à masquer systématiquement dans les journaux d'audit */
    protected const SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'current_password',
        'mot_de_passe',
        'mot_de_passe_actuel',
        'nouveau_mot_de_passe',
        'nouveau_mot_de_passe_confirmation',
        'token',
        'access_token',
        'refresh_token',
        'api_key',
        'authorization',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'recovery_codes',
        'remember_token',
        'secret',
        'app_key',
    ];

    /**
     * Enregistre un événement dans la piste d'audit.
     */
    public static function log(
        ?User $auteur,
        string $action,
        ?Model $sujet = null,
        ?array $details = null,
        ?array $anciennesValeurs = null
    ): AuditLog {
        $candidateUserId = $auteur?->getKey() ?? auth()->id();
        $userId = $candidateUserId && User::whereKey($candidateUserId)->exists()
            ? $candidateUserId
            : null;

        $ip = request()?->ip() ?? '127.0.0.1';
        $userAgent = request()?->userAgent();

        $nouvellesSanitized = $details !== null ? static::sanitizeData($details) : null;
        $anciennesSanitized = $anciennesValeurs !== null ? static::sanitizeData($anciennesValeurs) : null;

        return AuditLog::create([
            'user_id' => $userId,
            'action' => $action,
            'auditable_type' => $sujet ? get_class($sujet) : null,
            'auditable_id' => $sujet?->id,
            'anciennes_valeurs' => $anciennesSanitized ?: null,
            'nouvelles_valeurs' => $nouvellesSanitized ?: null,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'created_at' => now(),
        ]);
    }

    /**
     * Enregistre explicitement une mutation de modèle avec état avant et après.
     */
    public static function logModelChange(
        ?User $auteur,
        string $action,
        Model $sujet,
        array $anciennesValeurs,
        array $nouvellesValeurs
    ): AuditLog {
        return static::log($auteur, $action, $sujet, $nouvellesValeurs, $anciennesValeurs);
    }

    /**
     * Nettoie et masque récursivement tous les secrets et mots de passe.
     */
    public static function sanitizeData(mixed $data): mixed
    {
        if (! is_array($data)) {
            return $data;
        }

        $sanitized = [];

        foreach ($data as $key => $value) {
            if (is_string($key) && static::isSensitiveKey($key)) {
                $sanitized[$key] = '[MASQUÉ]';
            } elseif (is_array($value)) {
                $sanitized[$key] = static::sanitizeData($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Vérifie si une clé de tableau correspond à une donnée sensible.
     */
    public static function isSensitiveKey(string $key): bool
    {
        $lower = strtolower(trim($key));

        foreach (static::SENSITIVE_KEYS as $sensitive) {
            if ($lower === $sensitive || str_contains($lower, 'password') || str_contains($lower, 'secret') || str_contains($lower, 'token') || str_contains($lower, 'recovery_code')) {
                return true;
            }
        }

        return false;
    }
}
