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

    /**
     * Test 1 : La route nommée 'director.purger-donnees' n'existe plus dans l'application.
     */
    public function test_purge_named_route_does_not_exist(): void
    {
        $this->assertFalse(
            Route::has('director.purger-donnees'),
            "La route director.purger-donnees ne doit plus être enregistrée."
        );
    }

    /**
     * Test 2 : Une requête POST directe sur l'ancien URI '/direction/purger-donnees' échoue (404/405).
     */
    public function test_direct_post_to_purge_endpoint_returns_404(): void
    {
        $response = $this->actingAs($this->auditeur)->post('/direction/purger-donnees', [
            'mot_de_passe_actuel' => 'password',
            'confirmation' => 'VIDER LES DONNEES',
        ]);

        $response->assertNotFound();
    }

    /**
     * Test 3 : Les données financières et membres ne sont pas supprimées via l'ancien endpoint.
     */
    public function test_financial_and_member_data_cannot_be_purged_via_http(): void
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

        $this->actingAs($this->auditeur)->post('/direction/purger-donnees', [
            'mot_de_passe_actuel' => 'password',
            'confirmation' => 'VIDER LES DONNEES',
        ]);

        $this->assertDatabaseHas('members', ['id' => $member->id]);
        $this->assertDatabaseHas('accounts', ['id' => $account->id]);
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

        $this->actingAs($this->auditeur)->post('/direction/purger-donnees', [
            'mot_de_passe_actuel' => 'password',
            'confirmation' => 'VIDER LES DONNEES',
        ]);

        $this->assertDatabaseHas('audit_logs', ['id' => $log->id]);
    }

    /**
     * Test 5 : La vue dashboard de direction ne contient plus le formulaire de purge ("Zone dangereuse").
     */
    public function test_director_dashboard_does_not_contain_purge_form(): void
    {
        $response = $this->actingAs($this->auditeur)->get(route('director.dashboard'));

        $response->assertStatus(200);
        $response->assertDontSee('director.purger-donnees');
        $response->assertDontSee('Zone dangereuse');
        $response->assertDontSee('VIDER LES DONNEES');
        $response->assertDontSee('Effacer toutes les donnees');
    }
}
