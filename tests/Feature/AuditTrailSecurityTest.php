<?php

namespace Tests\Feature;

use App\Actions\Fortify\UpdateUserPassword;
use App\Enums\LoanStatus;
use App\Enums\UserRole;
use App\Models\Account;
use App\Models\AuditLog;
use App\Models\Loan;
use App\Models\Member;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\DepotRetraitService;
use App\Services\LoanService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Tests de sécurité pour VULN-13 / Phase 6 :
 * Intégrité de la piste d'audit, traçabilité financière, masquage des secrets et immutabilité.
 */
class AuditTrailSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected User $agent;
    protected User $admin;
    protected User $director;
    protected Member $member;
    protected Account $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->agent = User::factory()->create([
            'email' => 'agent.audit@test.com',
            'password' => Hash::make('Password123!'),
            'role' => UserRole::Agent->value,
            'statut' => 'actif',
        ]);

        $this->admin = User::factory()->create([
            'email' => 'admin.audit@test.com',
            'password' => Hash::make('Password123!'),
            'role' => UserRole::Admin->value,
            'statut' => 'actif',
        ]);

        $this->director = User::factory()->create([
            'email' => 'director.audit@test.com',
            'password' => Hash::make('Password123!'),
            'role' => UserRole::Auditeur->value,
            'statut' => 'actif',
        ]);

        $this->member = Member::create([
            'numero_membre' => 'MBR-AUDIT-01',
            'prenom' => 'Jean',
            'nom' => 'Audit',
            'statut' => 'actif',
            'telephone' => '509-3700-1111',
            'cree_par_id' => $this->admin->id,
        ]);

        $this->account = (new Account())->forceFill([
            'member_id' => $this->member->id,
            'numero_compte' => 'CP-AUDIT-001',
            'solde' => '10000.00',
            'statut' => 'actif',
        ]);
        $this->account->save();
    }

    /**
     * 1. Test : Les logs d'audit capturent l'état AVANT et APRÈS modification sur un membre.
     */
    public function test_audit_logs_record_old_and_new_values_on_update(): void
    {
        $response = $this->actingAs($this->agent)->put(route('members.update', $this->member), [
            'prenom' => 'Jean-Paul',
            'nom' => 'Audit-Modifie',
            'telephone' => '509-3700-9999',
        ]);

        $response->assertRedirect(route('members.index'));

        $log = AuditLog::where('action', 'membre.modifie')
            ->where('auditable_type', Member::class)
            ->where('auditable_id', $this->member->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($log, "Le journal d'audit de modification de membre doit exister.");
        $this->assertEquals($this->agent->id, $log->user_id);

        // Vérification de l'état AVANT
        $this->assertIsArray($log->anciennes_valeurs);
        $this->assertEquals('Jean', $log->anciennes_valeurs['prenom']);
        $this->assertEquals('AUDIT', $log->anciennes_valeurs['nom']);
        $this->assertEquals('509-3700-1111', $log->anciennes_valeurs['telephone']);

        // Vérification de l'état APRÈS
        $this->assertIsArray($log->nouvelles_valeurs);
        $this->assertEquals('Jean-Paul', $log->nouvelles_valeurs['prenom']);
        $this->assertEquals('AUDIT-MODIFIE', $log->nouvelles_valeurs['nom']);
        $this->assertEquals('509-3700-9999', $log->nouvelles_valeurs['telephone']);
    }

    /**
     * Alias requis : member_update_logs_old_and_new_values
     */
    public function test_member_update_logs_old_and_new_values(): void
    {
        $this->test_audit_logs_record_old_and_new_values_on_update();
    }

    /**
     * 2. Test : Les valeurs sensibles (mots de passe, tokens, secrets) sont automatiquement masquées.
     */
    public function test_sensitive_values_are_redacted_from_audit_logs(): void
    {
        $log = ActivityLogger::log($this->agent, 'test.sanitization', $this->agent, [
            'password' => 'UltraSecretPassword123!',
            'current_password' => 'OldSecretPassword!',
            'token' => 'token-xyz-12345',
            'two_factor_secret' => '2fa-secret-code',
            'normal_key' => 'donnee_publique',
            'nested' => [
                'api_key' => 'api-key-secret',
                'description' => 'operation normale',
            ],
        ], [
            'password' => 'PreviousPassword!',
            'mot_de_passe' => 'AncienMotDePasse',
        ]);

        $this->assertEquals('[MASQUÉ]', $log->nouvelles_valeurs['password']);
        $this->assertEquals('[MASQUÉ]', $log->nouvelles_valeurs['current_password']);
        $this->assertEquals('[MASQUÉ]', $log->nouvelles_valeurs['token']);
        $this->assertEquals('[MASQUÉ]', $log->nouvelles_valeurs['two_factor_secret']);
        $this->assertEquals('donnee_publique', $log->nouvelles_valeurs['normal_key']);
        $this->assertEquals('[MASQUÉ]', $log->nouvelles_valeurs['nested']['api_key']);
        $this->assertEquals('operation normale', $log->nouvelles_valeurs['nested']['description']);

        $this->assertEquals('[MASQUÉ]', $log->anciennes_valeurs['password']);
        $this->assertEquals('[MASQUÉ]', $log->anciennes_valeurs['mot_de_passe']);
    }

    /**
     * 3. Test : Une connexion réussie produit un log d'audit avec IP et User-Agent.
     */
    public function test_successful_login_is_audited(): void
    {
        $response = $this->withServerVariables([
            'REMOTE_ADDR' => '192.168.1.50',
            'HTTP_USER_AGENT' => 'TestBrowser/1.0',
        ])->post(route('login'), [
            'email' => 'agent.audit@test.com',
            'password' => 'Password123!',
        ]);

        $response->assertRedirect();
        $this->assertAuthenticatedAs($this->agent);

        $log = AuditLog::where('action', 'auth.login')
            ->where('user_id', $this->agent->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($log, "La connexion réussie doit être journalisée.");
        $this->assertEquals($this->agent->id, $log->user_id);
        $this->assertEquals('192.168.1.50', $log->ip_address);
        $this->assertEquals('TestBrowser/1.0', $log->user_agent);
        $this->assertEquals('succes', $log->nouvelles_valeurs['statut']);
        $this->assertEquals('agent.audit@test.com', $log->nouvelles_valeurs['email']);
        $this->assertArrayNotHasKey('password', $log->nouvelles_valeurs);
    }

    /**
     * Alias requis : successful_login_is_logged
     */
    public function test_successful_login_is_logged(): void
    {
        $this->test_successful_login_is_audited();
    }

    /**
     * 4. Test : Un échec de connexion produit un log d'audit sans mot de passe en clair.
     */
    public function test_failed_login_is_audited_without_password(): void
    {
        $response = $this->withServerVariables([
            'REMOTE_ADDR' => '192.168.1.99',
            'HTTP_USER_AGENT' => 'MaliciousClient/2.0',
        ])->post(route('login'), [
            'email' => 'agent.audit@test.com',
            'password' => 'AttemptedWrongPassword999!',
        ]);

        $this->assertGuest();

        $log = AuditLog::where('action', 'auth.failed')
            ->latest('id')
            ->first();

        $this->assertNotNull($log, "L'échec de connexion doit être journalisé.");
        $this->assertEquals('192.168.1.99', $log->ip_address);
        $this->assertEquals('MaliciousClient/2.0', $log->user_agent);
        $this->assertEquals('agent.audit@test.com', $log->nouvelles_valeurs['email']);
        $this->assertEquals('echec', $log->nouvelles_valeurs['statut']);

        // Vérification absolue : le mot de passe tenté n'apparaît JAMAIS
        $logJson = json_encode($log->toArray());
        $this->assertStringNotContainsString('AttemptedWrongPassword999!', $logJson);
        $this->assertArrayNotHasKey('password', $log->nouvelles_valeurs);
    }

    /**
     * Alias requis : failed_login_is_logged
     */
    public function test_failed_login_is_logged(): void
    {
        $this->test_failed_login_is_audited_without_password();
    }

    /**
     * 5. Test : La déconnexion produit un log d'audit.
     */
    public function test_logout_is_audited(): void
    {
        $this->actingAs($this->agent);

        $response = $this->post(route('logout'));
        $response->assertRedirect();
        $this->assertGuest();

        $log = AuditLog::where('action', 'auth.logout')
            ->where('user_id', $this->agent->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($log, "La déconnexion doit être journalisée.");
        $this->assertEquals($this->agent->id, $log->user_id);
    }

    /**
     * Alias requis : logout_is_logged
     */
    public function test_logout_is_logged(): void
    {
        $this->test_logout_is_audited();
    }

    /**
     * 6. Test : Le changement de mot de passe est journalisé sans exposer le mot de passe.
     */
    public function test_password_change_is_audited_without_secret(): void
    {
        // 1. Changement via le contrôleur de direction
        $response = $this->actingAs($this->director)->post(route('director.motdepasse'), [
            'mot_de_passe_actuel' => 'Password123!',
            'nouveau_mot_de_passe' => 'UltraSecureDirectorPass99!',
            'nouveau_mot_de_passe_confirmation' => 'UltraSecureDirectorPass99!',
        ]);

        $response->assertRedirect();

        $log = AuditLog::where('action', 'auth.motdepasse_modifie')
            ->where('user_id', $this->director->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($log, "Le changement de mot de passe doit être journalisé.");
        $logJson = json_encode($log->toArray());
        $this->assertStringNotContainsString('UltraSecureDirectorPass99!', $logJson);
        $this->assertStringNotContainsString('Password123!', $logJson);

        // 2. Changement via l'action Fortify UpdateUserPassword
        $this->actingAs($this->agent);
        app(UpdateUserPassword::class)->update($this->agent, [
            'current_password' => 'Password123!',
            'password' => 'NewAgentPassword456!',
            'password_confirmation' => 'NewAgentPassword456!',
        ]);

        $logAgent = AuditLog::where('action', 'auth.motdepasse_modifie')
            ->where('user_id', $this->agent->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($logAgent);
        $agentLogJson = json_encode($logAgent->toArray());
        $this->assertStringNotContainsString('NewAgentPassword456!', $agentLogJson);
        $this->assertStringNotContainsString('Password123!', $agentLogJson);
    }

    /**
     * Alias requis : password_change_is_logged
     */
    public function test_password_change_is_logged(): void
    {
        $this->test_password_change_is_audited_without_secret();
    }

    /**
     * 7. Test : Les opérations financières sensibles (dépôt, retrait) sont journalisées avec avant/après.
     */
    public function test_sensitive_financial_operation_is_audited(): void
    {
        $depotService = app(DepotRetraitService::class);

        // Dépôt
        $depot = $depotService->deposer($this->account, '2500.00', $this->agent, [
            'description' => 'Dépôt test audit',
        ]);

        $logDepot = AuditLog::where('action', 'transaction.depot')
            ->where('auditable_id', $depot->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($logDepot, "Le dépôt doit être journalisé.");
        $this->assertEquals($this->agent->id, $logDepot->user_id);
        $this->assertEquals('10000.00', $logDepot->anciennes_valeurs['solde']);
        $this->assertEquals('12500.00', $logDepot->nouvelles_valeurs['solde_apres']);
        $this->assertEquals($this->account->numero_compte, $logDepot->nouvelles_valeurs['compte']);

        // Retrait
        $retrait = $depotService->retirer($this->account, '1500.00', $this->agent, [
            'description' => 'Retrait test audit',
        ]);

        $logRetrait = AuditLog::where('action', 'transaction.retrait')
            ->where('auditable_id', $retrait->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($logRetrait, "Le retrait doit être journalisé.");
        $this->assertEquals($this->agent->id, $logRetrait->user_id);
        $this->assertEquals('12500.00', $logRetrait->anciennes_valeurs['solde']);
        $this->assertEquals('11000.00', $logRetrait->nouvelles_valeurs['solde_apres']);
    }

    /**
     * 8. Test : Approbation de prêt journalisée avec état avant et après.
     */
    public function test_loan_approval_is_logged(): void
    {
        $loanService = app(LoanService::class);

        $loan = $loanService->demander($this->member, $this->account, [
            'montant_demande' => '50000.00',
            'motif' => 'Commerce',
            'duree_mois' => 12,
            'type_taux' => 'fixe',
            'taux_interet' => 12,
            'methode_calcul' => 'constant',
        ], $this->agent);

        $response = $this->actingAs($this->admin)->post(route('loans.approuver', $loan), [
            'montant_approuve' => '45000.00',
        ]);

        $response->assertRedirect();

        $log = AuditLog::where('action', 'pret.approuve')
            ->where('auditable_id', $loan->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($log, "L'approbation du prêt doit être journalisée.");
        $this->assertEquals($this->admin->id, $log->user_id);
        $this->assertEquals('demande', $log->anciennes_valeurs['statut']);
        $this->assertEquals('approuve', $log->nouvelles_valeurs['statut']);
        $this->assertEquals('45000.00', $log->nouvelles_valeurs['montant_approuve']);
        $this->assertEquals($loan->numero_pret, $log->nouvelles_valeurs['numero_pret']);
    }

    /**
     * 9. Test : Décaissement de prêt journalisé avec traçabilité complète.
     */
    public function test_loan_disbursement_is_logged(): void
    {
        $loanService = app(LoanService::class);

        $loan = $loanService->demander($this->member, $this->account, [
            'montant_demande' => '20000.00',
            'motif' => 'Agriculture',
            'duree_mois' => 6,
            'type_taux' => 'fixe',
            'taux_interet' => 10,
            'methode_calcul' => 'constant',
        ], $this->agent);

        $loanService->approuver($loan, $this->admin, 20000.00);

        $response = $this->actingAs($this->admin)->post(route('loans.decaisser', $loan));
        $response->assertRedirect();

        $log = AuditLog::where('action', 'pret.decaisse')
            ->where('auditable_id', $loan->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($log, "Le décaissement doit être journalisé.");
        $this->assertEquals($this->admin->id, $log->user_id);
        $this->assertEquals('approuve', $log->anciennes_valeurs['statut']);
        $this->assertEquals('decaisse', $log->nouvelles_valeurs['statut']);
        $this->assertEquals('20000.00', $log->nouvelles_valeurs['montant']);

        // Vérifier que le versement sur le compte a également produit un log de dépôt
        $logDepot = AuditLog::where('action', 'transaction.depot')
            ->where('nouvelles_valeurs->account_id', $this->account->id)
            ->latest('id')
            ->first();
        $this->assertNotNull($logDepot, "Le versement de fonds lié au prêt doit être journalisé.");
    }

    /**
     * 10. Test : Remboursement de prêt journalisé avec toutes les relations préservées.
     */
    public function test_repayment_is_logged(): void
    {
        $loanService = app(LoanService::class);
        $depotService = app(DepotRetraitService::class);

        $loan = $loanService->demander($this->member, $this->account, [
            'montant_demande' => '12000.00',
            'motif' => 'Artisanat',
            'duree_mois' => 6,
            'type_taux' => 'fixe',
            'taux_interet' => 12,
            'methode_calcul' => 'simple',
        ], $this->agent);

        $loanService->approuver($loan, $this->admin, 12000.00);
        $loanService->decaisser($loan, $this->admin, $depotService);

        $echeance = $loan->schedules()->first();

        $response = $this->actingAs($this->agent)->post(
            route('loans.rembourser', ['loan' => $loan, 'echeance' => $echeance]),
            [
                'montant' => $echeance->montant_total,
                'moyen' => 'especes',
            ]
        );

        $response->assertRedirect();

        $log = AuditLog::where('action', 'pret.remboursement')
            ->where('auditable_id', $loan->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($log, "Le remboursement doit être journalisé.");
        $this->assertEquals($this->agent->id, $log->user_id);
        $this->assertEquals($loan->id, $log->nouvelles_valeurs['loan_id']);
        $this->assertEquals($this->account->id, $log->nouvelles_valeurs['compte_id']);
        $this->assertEquals($echeance->id, $log->nouvelles_valeurs['loan_schedule_id']);
        $this->assertNotEmpty($log->nouvelles_valeurs['transaction_id']);
        $this->assertNotEmpty($log->nouvelles_valeurs['repayment_id']);
        $this->assertEquals('payee', $log->nouvelles_valeurs['statut_echeance']);
    }

    /**
     * 11. Test : Un AuditLog NE PEUT PAS être modifié (ni via Eloquent, ni via SQL direct).
     */
    public function test_audit_log_cannot_be_updated(): void
    {
        $log = ActivityLogger::log($this->agent, 'action.originale', $this->member, [
            'statut' => 'original',
        ]);

        // 1. Protection au niveau Eloquent
        $eloquentBlocked = false;
        try {
            $log->update(['action' => 'action.falsifiee']);
        } catch (\RuntimeException $e) {
            $eloquentBlocked = true;
            $this->assertStringContainsString('immuables', $e->getMessage());
        }
        $this->assertTrue($eloquentBlocked, "L'appel à update() sur AuditLog doit lever une RuntimeException.");

        // 2. Protection au niveau Base de Données (Trigger SQL)
        $sqlBlocked = false;
        try {
            DB::statement("UPDATE audit_logs SET action = 'action.piratee' WHERE id = ?", [$log->id]);
        } catch (QueryException $e) {
            $sqlBlocked = true;
            $this->assertStringContainsString('immutable', $e->getMessage());
        }
        $this->assertTrue($sqlBlocked, "Une tentative d'UPDATE SQL direct doit être rejetée par le trigger de base de données.");

        // Vérification de persistance de l'état d'origine
        $logRefreshed = AuditLog::find($log->id);
        $this->assertEquals('action.originale', $logRefreshed->action);
    }

    /**
     * 12. Test : Un AuditLog NE PEUT PAS être supprimé (ni via Eloquent, ni via SQL direct).
     */
    public function test_audit_log_cannot_be_deleted(): void
    {
        $log = ActivityLogger::log($this->agent, 'action.indélébile', $this->member, [
            'statut' => 'intact',
        ]);

        // 1. Protection au niveau Eloquent : delete()
        $deleteBlocked = false;
        try {
            $log->delete();
        } catch (\RuntimeException $e) {
            $deleteBlocked = true;
            $this->assertStringContainsString('immuables', $e->getMessage());
        }
        $this->assertTrue($deleteBlocked, "L'appel à delete() sur AuditLog doit lever une RuntimeException.");

        // 2. Protection au niveau Eloquent : forceDelete()
        $forceDeleteBlocked = false;
        try {
            $log->forceDelete();
        } catch (\RuntimeException $e) {
            $forceDeleteBlocked = true;
            $this->assertStringContainsString('immuables', $e->getMessage());
        }
        $this->assertTrue($forceDeleteBlocked, "L'appel à forceDelete() sur AuditLog doit lever une RuntimeException.");

        // 3. Protection au niveau Base de Données (Trigger SQL)
        $sqlBlocked = false;
        try {
            DB::statement("DELETE FROM audit_logs WHERE id = ?", [$log->id]);
        } catch (QueryException $e) {
            $sqlBlocked = true;
            $this->assertStringContainsString('immutable', $e->getMessage());
        }
        $this->assertTrue($sqlBlocked, "Une tentative de DELETE SQL direct doit être rejetée par le trigger de base de données.");

        // Vérification de la présence continue en base
        $this->assertDatabaseHas('audit_logs', ['id' => $log->id]);
    }

    /**
     * 13. Test : Préservation des informations relatives à l'acteur et à la cible.
     */
    public function test_audit_log_preserves_actor_and_target_information(): void
    {
        $log = ActivityLogger::log($this->admin, 'pret.decision', $this->account, [
            'note' => 'Contrôle conformité',
        ]);

        $this->assertEquals($this->admin->id, $log->user_id);
        $this->assertEquals(Account::class, $log->auditable_type);
        $this->assertEquals($this->account->id, $log->auditable_id);
        $this->assertNotNull($log->created_at);
        $this->assertNotNull($log->ip_address);
    }

    /**
     * 14. Test : Blocage et déblocage de compte avec anciennes et nouvelles valeurs.
     */
    public function test_account_block_and_unblock_is_audited(): void
    {
        // Blocage
        $responseBloquer = $this->actingAs($this->admin)->post(route('accounts.bloquer', $this->account), [
            'raison_blocage' => 'Suspicion de fraude fiscale',
        ]);
        $responseBloquer->assertRedirect();

        $logBlocage = AuditLog::where('action', 'compte.bloque')
            ->where('auditable_id', $this->account->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($logBlocage, "Le blocage doit être journalisé.");
        $this->assertEquals('actif', $logBlocage->anciennes_valeurs['statut']);
        $this->assertEquals('bloque', $logBlocage->nouvelles_valeurs['statut']);
        $this->assertEquals('Suspicion de fraude fiscale', $logBlocage->nouvelles_valeurs['raison']);

        // Déblocage
        $responseDebloquer = $this->actingAs($this->admin)->post(route('accounts.debloquer', $this->account));
        $responseDebloquer->assertRedirect();

        $logDeblocage = AuditLog::where('action', 'compte.debloque')
            ->where('auditable_id', $this->account->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($logDeblocage, "Le déblocage doit être journalisé.");
        $this->assertEquals('bloque', $logDeblocage->anciennes_valeurs['statut']);
        $this->assertEquals('actif', $logDeblocage->nouvelles_valeurs['statut']);
    }

    /**
     * 15. Test : Activation et désactivation de compte utilisateur journalisées.
     */
    public function test_user_activation_and_deactivation_is_audited(): void
    {
        // Désactivation de l'agent
        $this->agent->forceFill(['statut' => 'inactif'])->save();

        $logDesactivation = AuditLog::where('action', 'utilisateur.desactive')
            ->where('auditable_id', $this->agent->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($logDesactivation, "La désactivation de l'utilisateur doit être journalisée.");
        $this->assertEquals('actif', $logDesactivation->anciennes_valeurs['statut']);
        $this->assertEquals('inactif', $logDesactivation->nouvelles_valeurs['statut']);

        // Réactivation de l'agent
        $this->agent->forceFill(['statut' => 'actif'])->save();

        $logActivation = AuditLog::where('action', 'utilisateur.active')
            ->where('auditable_id', $this->agent->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($logActivation, "L'activation de l'utilisateur doit être journalisée.");
        $this->assertEquals('inactif', $logActivation->anciennes_valeurs['statut']);
        $this->assertEquals('actif', $logActivation->nouvelles_valeurs['statut']);
    }
}
