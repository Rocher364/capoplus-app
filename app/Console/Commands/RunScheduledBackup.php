<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

class RunScheduledBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:run {--force : Forcer la sauvegarde meme si non planifiee}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Execute la sauvegarde automatique de la base de donnees CAPO+';

    /**
     * Execute the console command.
     */
    public function handle(BackupService $backupService): int
    {
        $this->info('Verification des sauvegardes CAPO+...');

        if ($this->option('force')) {
            $filename = $backupService->createBackup('Sauvegarde console manuelle (force)');
            $this->info("Sauvegarde forcee creee : {$filename}");
            return Command::SUCCESS;
        }

        $result = $backupService->runScheduledBackupIfDue();

        if ($result) {
            $this->info("Sauvegarde planifiee executee avec succes : {$result}");
        } else {
            $this->info("Aucune sauvegarde requise a cette heure.");
        }

        return Command::SUCCESS;
    }
}
