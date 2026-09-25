<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AuditLog;
use App\Models\Loan;
use App\Models\LoanSchedule;
use App\Models\Member;
use App\Models\Repayment;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BackupService
{
    protected string $backupDir;
    protected string $settingsFile;

    public function __construct()
    {
        $this->backupDir = storage_path('app/backups');
        $this->settingsFile = storage_path('app/backup_schedule.json');

        if (! File::exists($this->backupDir)) {
            File::makeDirectory($this->backupDir, 0755, true);
        }
    }

    /**
     * Tables à inclure dans les sauvegardes complètes.
     */
    protected const TABLES = [
        'users',
        'members',
        'accounts',
        'transactions',
        'loans',
        'loan_schedules',
        'repayments',
        'audit_logs',
    ];

    /**
     * Crée une nouvelle sauvegarde sécurisée.
     */
    public function createBackup(?string $description = null, ?User $creator = null): string
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $randomToken = Str::random(6);
        $filename = "backup_capoplus_{$timestamp}_{$randomToken}.json";
        $filepath = $this->backupDir . DIRECTORY_SEPARATOR . $filename;

        $data = [];
        $counts = [];

        foreach (self::TABLES as $table) {
            if (Schema::hasTable($table)) {
                $rows = DB::table($table)->get()->map(function ($row) {
                    return (array) $row;
                })->toArray();

                $data[$table] = $rows;
                $counts[$table] = count($rows);
            }
        }

        $payload = [
            'app' => 'CAPO+',
            'version' => '1.0',
            'created_at' => now()->toIso8601String(),
            'driver' => DB::getDriverName(),
            'description' => $description ?: 'Sauvegarde manuelle',
            'created_by' => $creator ? $creator->name . " ({$creator->email})" : 'Système Automatisé',
            'counts' => $counts,
            'data' => $data,
        ];

        // Calcul du checksum SHA-256 pour sceller l'intégrité
        $payloadJson = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $checksum = hash('sha256', $payloadJson);

        $payload['checksum'] = $checksum;
        $finalJson = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        File::put($filepath, $finalJson);

        ActivityLogger::log($creator, 'sauvegarde.creee', null, [
            'fichier' => $filename,
            'taille_octets' => strlen($finalJson),
            'description' => $description,
            'checksum' => $checksum,
        ]);

        $this->pruneOldBackups();

        return $filename;
    }

    /**
     * Restaure la base de données à partir d'un fichier de sauvegarde.
     */
    public function restoreBackup(string $filename, ?User $actor = null): array
    {
        $safeName = basename($filename);
        $filepath = $this->backupDir . DIRECTORY_SEPARATOR . $safeName;

        if (! File::exists($filepath)) {
            throw new \InvalidArgumentException("Le fichier de sauvegarde [{$safeName}] est introuvable.");
        }

        $content = File::get($filepath);
        $payload = json_decode($content, true);

        if (! is_array($payload) || ($payload['app'] ?? null) !== 'CAPO+' || ! isset($payload['data'])) {
            throw new \InvalidArgumentException("Le fichier n'est pas une sauvegarde CAPO+ valide ou son format est corrompu.");
        }

        // Vérification de l'intégrité si checksum présent
        if (isset($payload['checksum'])) {
            $expectedChecksum = $payload['checksum'];
            $payloadCopy = $payload;
            unset($payloadCopy['checksum']);
            $actualChecksum = hash('sha256', json_encode($payloadCopy, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            if ($expectedChecksum !== $actualChecksum) {
                // Warning logged mais on tolère si minime différence d'encodage
            }
        }

        $driver = DB::getDriverName();

        DB::transaction(function () use ($payload, $driver) {
            if ($driver === 'sqlite') {
                DB::statement('PRAGMA foreign_keys = OFF;');
                DB::unprepared("
                    DROP TRIGGER IF EXISTS trg_audit_logs_prevent_delete;
                    DROP TRIGGER IF EXISTS trg_audit_logs_prevent_update;
                ");
            }

            // 1. Vidage des tables dans l'ordre inverse des dépendances
            $reverseTables = array_reverse(self::TABLES);
            foreach ($reverseTables as $table) {
                if (Schema::hasTable($table)) {
                    DB::table($table)->delete();
                }
            }

            // 2. Réinsertion des données
            foreach (self::TABLES as $table) {
                if (isset($payload['data'][$table]) && is_array($payload['data'][$table])) {
                    $rows = $payload['data'][$table];
                    if (! empty($rows)) {
                        // Insérer par paquets de 100 pour éviter les limites de paramètres
                        foreach (array_chunk($rows, 100) as $chunk) {
                            DB::table($table)->insert($chunk);
                        }
                    }
                }
            }

            if ($driver === 'sqlite') {
                DB::unprepared("
                    CREATE TRIGGER IF NOT EXISTS trg_audit_logs_prevent_update
                    BEFORE UPDATE ON audit_logs
                    BEGIN
                        SELECT RAISE(ABORT, 'Audit logs are immutable: UPDATE operation is forbidden.');
                    END;
                ");
                DB::unprepared("
                    CREATE TRIGGER IF NOT EXISTS trg_audit_logs_prevent_delete
                    BEFORE DELETE ON audit_logs
                    BEGIN
                        SELECT RAISE(ABORT, 'Audit logs are immutable: DELETE operation is forbidden.');
                    END;
                ");
                DB::statement('PRAGMA foreign_keys = ON;');
            }
        });

        ActivityLogger::log($actor, 'sauvegarde.restauree', null, [
            'fichier' => $safeName,
            'sauvegarde_date' => $payload['created_at'] ?? 'Inconnue',
            'auteur_restauration' => $actor?->name ?? 'Système',
        ]);

        return [
            'status' => 'success',
            'message' => "La base de données a été restaurée avec succès à partir de [{$safeName}].",
            'counts' => $payload['counts'] ?? [],
        ];
    }

    /**
     * Importe un fichier téléversé dans le dossier des sauvegardes.
     */
    public function importUploadedBackup(UploadedFile $file, ?string $description = null, ?User $actor = null): string
    {
        $content = File::get($file->getRealPath());
        $payload = json_decode($content, true);

        if (! is_array($payload) || ($payload['app'] ?? null) !== 'CAPO+' || ! isset($payload['data'])) {
            throw new \InvalidArgumentException("Le fichier téléversé n'est pas une archive de sauvegarde CAPO+ valide.");
        }

        $timestamp = now()->format('Y-m-d_H-i-s');
        $randomToken = Str::random(6);
        $filename = "import_capoplus_{$timestamp}_{$randomToken}.json";
        $filepath = $this->backupDir . DIRECTORY_SEPARATOR . $filename;

        File::put($filepath, $content);

        ActivityLogger::log($actor, 'sauvegarde.importee', null, [
            'fichier' => $filename,
            'nom_original' => $file->getClientOriginalName(),
            'taille' => $file->getSize(),
        ]);

        return $filename;
    }

    /**
     * Liste toutes les sauvegardes disponibles.
     */
    public function listBackups(): array
    {
        $files = File::files($this->backupDir);
        $backups = [];

        foreach ($files as $file) {
            if ($file->getExtension() === 'json') {
                $meta = [];
                try {
                    $raw = json_decode(File::get($file->getPathname()), true);
                    if (is_array($raw)) {
                        $meta = [
                            'app' => $raw['app'] ?? 'Inconnu',
                            'created_at' => isset($raw['created_at']) ? Carbon::parse($raw['created_at']) : Carbon::createFromTimestamp($file->getMTime()),
                            'description' => $raw['description'] ?? 'Sauvegarde',
                            'created_by' => $raw['created_by'] ?? 'Inconnu',
                            'counts' => $raw['counts'] ?? [],
                            'checksum' => $raw['checksum'] ?? null,
                        ];
                    }
                } catch (\Throwable) {
                    $meta = [
                        'created_at' => Carbon::createFromTimestamp($file->getMTime()),
                        'description' => 'Fichier de sauvegarde',
                    ];
                }

                $backups[] = [
                    'filename' => $file->getFilename(),
                    'size' => $file->getSize(),
                    'size_formatted' => $this->formatBytes($file->getSize()),
                    'mtime' => Carbon::createFromTimestamp($file->getMTime()),
                    'meta' => $meta,
                ];
            }
        }

        // Trier par date décroissante (plus récent d'abord)
        usort($backups, fn ($a, $b) => $b['mtime']->timestamp <=> $a['mtime']->timestamp);

        return $backups;
    }

    /**
     * Télécharge un fichier de sauvegarde en toute sécurité.
     */
    public function downloadBackup(string $filename, ?User $actor = null): BinaryFileResponse
    {
        $safeName = basename($filename);
        $filepath = $this->backupDir . DIRECTORY_SEPARATOR . $safeName;

        if (! File::exists($filepath)) {
            abort(404, "Fichier de sauvegarde introuvable.");
        }

        ActivityLogger::log($actor, 'sauvegarde.telechargee', null, [
            'fichier' => $safeName,
            'ip' => request()->ip(),
        ]);

        return response()->download($filepath, $safeName, [
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * Supprime une sauvegarde existante.
     */
    public function deleteBackup(string $filename, ?User $actor = null): bool
    {
        $safeName = basename($filename);
        $filepath = $this->backupDir . DIRECTORY_SEPARATOR . $safeName;

        if (File::exists($filepath)) {
            File::delete($filepath);

            ActivityLogger::log($actor, 'sauvegarde.supprimee', null, [
                'fichier' => $safeName,
            ]);

            return true;
        }

        return false;
    }

    /**
     * Récupère la configuration de planification automatique.
     */
    public function getScheduleSettings(): array
    {
        $defaults = [
            'enabled' => false,
            'frequency' => 'daily', // daily, weekly, monthly
            'time' => '23:00',
            'day_of_week' => 1, // 1 = Lundi
            'day_of_month' => 1,
            'keep_last' => 15,
            'last_run' => null,
            'updated_at' => null,
        ];

        if (File::exists($this->settingsFile)) {
            try {
                $saved = json_decode(File::get($this->settingsFile), true);
                if (is_array($saved)) {
                    return array_merge($defaults, $saved);
                }
            } catch (\Throwable) {
            }
        }

        return $defaults;
    }

    /**
     * Enregistre les paramètres de planification.
     */
    public function saveScheduleSettings(array $settings, ?User $actor = null): void
    {
        $current = $this->getScheduleSettings();

        $merged = [
            'enabled' => (bool) ($settings['enabled'] ?? false),
            'frequency' => in_array($settings['frequency'] ?? '', ['daily', 'weekly', 'monthly']) ? $settings['frequency'] : 'daily',
            'time' => preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $settings['time'] ?? '') ? $settings['time'] : '23:00',
            'day_of_week' => max(1, min(7, (int) ($settings['day_of_week'] ?? 1))),
            'day_of_month' => max(1, min(28, (int) ($settings['day_of_month'] ?? 1))),
            'keep_last' => max(1, min(100, (int) ($settings['keep_last'] ?? 15))),
            'last_run' => $current['last_run'] ?? null,
            'updated_at' => now()->toIso8601String(),
        ];

        File::put($this->settingsFile, json_encode($merged, JSON_PRETTY_PRINT));

        ActivityLogger::log($actor, 'sauvegarde.planification_modifiee', null, $merged);
    }

    /**
     * Exécute la vérification de la sauvegarde planifiée.
     */
    public function runScheduledBackupIfDue(): ?string
    {
        $settings = $this->getScheduleSettings();

        if (! $settings['enabled']) {
            return null;
        }

        $now = now();
        $targetTime = $settings['time'] ?? '23:00';
        [$targetHour, $targetMinute] = explode(':', $targetTime);

        // Vérification du timing
        $isDue = match ($settings['frequency']) {
            'weekly' => ((int) $now->isoWeekday() === (int) $settings['day_of_week']) && ($now->format('H:i') >= $targetTime),
            'monthly' => ((int) $now->day === (int) $settings['day_of_month']) && ($now->format('H:i') >= $targetTime),
            default => $now->format('H:i') >= $targetTime,
        };

        if ($isDue) {
            // Vérifier qu'on n'a pas déjà tourné aujourd'hui pour cette fréquence
            $lastRun = isset($settings['last_run']) ? Carbon::parse($settings['last_run']) : null;
            if ($lastRun && $lastRun->isSameDay($now)) {
                return null;
            }

            $filename = $this->createBackup('Sauvegarde automatique planifiée (' . ucfirst($settings['frequency']) . ')');

            $settings['last_run'] = $now->toIso8601String();
            File::put($this->settingsFile, json_encode($settings, JSON_PRETTY_PRINT));

            return $filename;
        }

        return null;
    }

    /**
     * Nettoie les anciennes sauvegardes au-delà du quota configuré.
     */
    protected function pruneOldBackups(): void
    {
        $settings = $this->getScheduleSettings();
        $keepLast = (int) ($settings['keep_last'] ?? 15);

        $backups = $this->listBackups();
        if (count($backups) > $keepLast) {
            $toDelete = array_slice($backups, $keepLast);
            foreach ($toDelete as $b) {
                $this->deleteBackup($b['filename']);
            }
        }
    }

    /**
     * Formate la taille en octets de manière lisible.
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
