<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\BackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class BackupSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected User $director;
    protected User $admin;
    protected User $agent;
    protected BackupService $backupService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->backupService = app(BackupService::class);

        $this->director = User::factory()->create([
            'email' => 'director@backup.test',
            'role' => UserRole::Auditeur->value,
            'statut' => 'actif',
        ]);

        $this->admin = User::factory()->create([
            'email' => 'admin@backup.test',
            'role' => UserRole::Admin->value,
            'statut' => 'actif',
        ]);

        $this->agent = User::factory()->create([
            'email' => 'agent@backup.test',
            'role' => UserRole::Agent->value,
            'statut' => 'actif',
        ]);
    }

    public function test_director_can_access_backup_page(): void
    {
        $response = $this->actingAs($this->director)->get(route('director.sauvegardes'));

        $response->assertStatus(200);
        $response->assertSee('Gestion des Sauvegardes');
    }

    public function test_agent_and_unauthorized_users_cannot_access_backup_page(): void
    {
        // Agent est bloqué (403)
        $response = $this->actingAs($this->agent)->get(route('director.sauvegardes'));
        $response->assertStatus(403);

        // Guest est bloqué ou redirigé vers login
        $response = $this->get(route('director.sauvegardes'));
        $this->assertTrue(in_array($response->status(), [403, 302]));
    }

    public function test_director_can_create_and_list_backup(): void
    {
        $response = $this->actingAs($this->director)->post(route('director.sauvegardes.creer'), [
            'description' => 'Test de sauvegarde manuelle',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $backups = $this->backupService->listBackups();
        $this->assertNotEmpty($backups);
        $this->assertEquals('Test de sauvegarde manuelle', $backups[0]['meta']['description']);
    }

    public function test_director_can_download_existing_backup(): void
    {
        $filename = $this->backupService->createBackup('Pour test download', $this->director);

        $response = $this->actingAs($this->director)->get(route('director.sauvegardes.telecharger', $filename));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/json');
    }

    public function test_director_can_save_schedule_settings(): void
    {
        $response = $this->actingAs($this->director)->post(route('director.sauvegardes.planification'), [
            'enabled' => '1',
            'frequency' => 'weekly',
            'time' => '03:30',
            'day_of_week' => 5,
            'keep_last' => 20,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $settings = $this->backupService->getScheduleSettings();
        $this->assertTrue($settings['enabled']);
        $this->assertEquals('weekly', $settings['frequency']);
        $this->assertEquals('03:30', $settings['time']);
        $this->assertEquals(20, $settings['keep_last']);
    }

    public function test_director_can_restore_backup_safely(): void
    {
        // Créer un utilisateur test supplémentaire
        User::factory()->create(['email' => 'extra@restore.test', 'statut' => 'actif']);
        $this->assertDatabaseHas('users', ['email' => 'extra@restore.test']);

        // Créer sauvegarde
        $filename = $this->backupService->createBackup('Snapshot avant suppression', $this->director);

        // Supprimer l'utilisateur
        User::where('email', 'extra@restore.test')->delete();
        $this->assertDatabaseMissing('users', ['email' => 'extra@restore.test']);

        // Restaurer la sauvegarde
        $response = $this->actingAs($this->director)->post(route('director.sauvegardes.restaurer', $filename));
        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Vérifier que l'utilisateur a bien été restauré
        $this->assertDatabaseHas('users', ['email' => 'extra@restore.test']);
    }

    public function test_corrupted_backup_import_is_rejected(): void
    {
        $fakeFile = UploadedFile::fake()->createWithContent('bad_backup.json', json_encode([
            'app' => 'MaliciousApp',
            'bad_payload' => true,
        ]));

        $response = $this->actingAs($this->director)->post(route('director.sauvegardes.importer'), [
            'fichier_sauvegarde' => $fakeFile,
        ]);

        $response->assertSessionHasErrors('backup');
    }
}
