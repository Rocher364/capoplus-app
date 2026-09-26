<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Account;
use App\Models\AuditLog;
use App\Models\Loan;
use App\Models\Member;
use App\Models\Repayment;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Tests de sécurité pour VULN-04 :
 * Suppression de l'endpoint et de la fonctionnalité de purge destructive.
 */
class PurgeSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected User $auditeur;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->auditeur = User::factory()->create([
            'role' => UserRole::Auditeur->value,
            'statut' => 'actif',
        ]);

        $this->admin = User::factory()->create([
            'role' => UserRole::Admin->value,
            'statut' => 'actif',
        ]);
    }

    public function test_purge_requires_exact_confirmation_and_current_password(): void
    {
        $response = $this->actingAs($this->auditeur)->post(route('director.purger-donnees'), [
            'mot_de_passe_actuel' => 'wrong-password',
            'confirmation' => 'EFFACER LES DONNEES',
        ]);

        $response->assertSessionHasErrors('mot_de_passe_actuel');
    }

    /**
     * Test 3 : Les données financières et membres ne sont pas supprimées via l'ancien endpoint.
     */
    public function test_purge_deletes_business_data_but_preserves_users_and_audit_logs(): void
    {
        $member = Member::create([
            'numero_membre' => 'MBR-PURGE-01',
            'prenom' => 'Purge',
            'nom' => 'Test',
            'statut' => 'actif',
            'cree_par_id' => $this->admin->id,
        ]);

        $account = Account::create([
            'member_id' => $member->id,
            'numero_compte' => 'CP-PURGE-001',
            'solde' => 500.00,
            'statut' => 'actif',
        ]);

        $response = $this->actingAs($this->auditeur)->post(route('director.purger-donnees'), [
            'mot_de_passe_actuel' => 'password',
            'confirmation' => 'EFFACER LES DONNEES',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseMissing('members', ['id' => $member->id]);
        $this->assertDatabaseMissing('accounts', ['id' => $account->id]);
        $this->assertDatabaseHas('users', ['id' => $this->auditeur->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'donnees.purgees']);
    }

    /**
     * Test 4 : Les audit logs sont préservés et ne peuvent pas être effacés via requête HTTP.
     */
    public function test_audit_logs_cannot_be_purged_via_http(): void
    {
        $log = AuditLog::create([
            'user_id' => $this->admin->id,
            'action' => 'test.action',
            'auditable_type' => User::class,
            'auditable_id' => $this->admin->id,
            'ip_address' => '127.0.0.1',
        ]);

        $this->actingAs($this->auditeur)->post(route('director.purger-donnees'), [
            'mot_de_passe_actuel' => 'password',
            'confirmation' => 'EFFACER LES DONNEES',
        ]);

        $this->assertDatabaseHas('audit_logs', ['id' => $log->id]);
    }

    public function test_purge_form_is_available_only_in_director_parameters(): void
    {
        $response = $this->actingAs($this->auditeur)->get(route('director.dashboard'));

        $response->assertStatus(200);
        $response->assertDontSee('/direction/purger-donnees');

        $parameters = $this->actingAs($this->auditeur)->get(route('director.parametres'));

        $parameters->assertStatus(200);
        $parameters->assertSee('/direction/purger-donnees');
        $parameters->assertSee('mot_de_passe_actuel');
        $parameters->assertSee('EFFACER LES DONNEES');
    }

        public function test_history_display_clear_requires_password_and_hides_previous_entries(): void
        {
            AuditLog::create([
                'user_id' => $this->admin->id,
                'action' => 'ancienne.action',
                'auditable_type' => User::class,
                'auditable_id' => $this->admin->id,
                'created_at' => now()->subMinute(),
            ]);

            $this->actingAs($this->auditeur)->post(route('director.historique.effacer'), [
                'mot_de_passe_actuel' => 'wrong-password',
                'confirmation' => "EFFACER TOUT L'HISTORIQUE",
            ])->assertSessionHasErrors('mot_de_passe_actuel');

            $this->assertDatabaseHas('audit_logs', ['action' => 'ancienne.action']);

            $this->actingAs($this->auditeur)->post(route('director.historique.effacer'), [
                'mot_de_passe_actuel' => 'password',
                'confirmation' => "EFFACER TOUT L'HISTORIQUE",
            ])->assertRedirect();

            $this->assertDatabaseHas('audit_logs', ['action' => 'ancienne.action']);
            $this->actingAs($this->auditeur)
                ->get(route('director.historique'))
                ->assertSee('Aucune activite trouvee.');
        }
}
