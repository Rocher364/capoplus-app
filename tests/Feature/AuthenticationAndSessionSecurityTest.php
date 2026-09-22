<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Account;
use App\Models\Member;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Fortify\Contracts\UpdatesUserPasswords;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;
use Tests\TestCase;

/**
 * Tests de sécurité pour VULN-05 et VULN-16 :
 * - VULN-05 : Invalidation des sessions existantes après désactivation / changement de mot de passe
 * - VULN-16 : Configuration et liaison robuste des fonctionnalités Fortify
 */
class AuthenticationAndSessionSecurityTest extends TestCase
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
            'email' => 'agent@test.com',
            'password' => Hash::make('Password123!'),
            'role' => UserRole::Agent->value,
            'statut' => 'actif',
        ]);

        $this->admin = User::factory()->create([
            'email' => 'admin@test.com',
            'password' => Hash::make('Password123!'),
            'role' => UserRole::Admin->value,
            'statut' => 'actif',
        ]);

        $this->director = User::factory()->create([
            'email' => 'director@test.com',
            'password' => Hash::make('Password123!'),
            'role' => UserRole::Auditeur->value,
            'statut' => 'actif',
        ]);

        $this->member = Member::create([
            'numero_membre' => 'MBR-SESS-01',
            'prenom' => 'Session',
            'nom' => 'Test',
            'statut' => 'actif',
            'cree_par_id' => $this->admin->id,
        ]);

        $this->account = (new Account())->forceFill([
            'member_id'     => $this->member->id,
            'numero_compte' => 'CP-SESS-001',
            'solde'         => 100.00,
            'statut'        => 'actif',
        ]);
        $this->account->save();
    }

    /**
     * Test 1 : Un utilisateur désactivé ne peut plus utiliser une session existante.
     * Le comportement attendu : session active -> compte désactivé -> requête suivante -> session invalidée -> redirection login.
     */
    public function test_deactivated_user_cannot_continue_existing_session(): void
    {
        // Session active initiale
        $response = $this->actingAs($this->agent)->get(route('accounts.index'));
        $response->assertOk();

        // Le compte est désactivé en base
        $this->agent->forceFill(['statut' => 'inactif'])->save();

        // Requête suivante avec la même session
        $nextResponse = $this->get(route('accounts.index'));

        // Doit être redirigé vers le login et déconnecté
        $nextResponse->assertRedirect(route('login'));
        $this->assertGuest();
    }

    /**
     * Test 2 : Le changement de mot de passe invalide les anciennes sessions.
     */
    public function test_password_change_invalidates_other_sessions(): void
    {
        // 1. Établir une première session avec le mot de passe actuel
        $response = $this->actingAs($this->agent)->get(route('accounts.index'));
        $response->assertOk();

        // 2. Le mot de passe change en base (ex: via un autre appareil ou réinitialisation par le directeur)
        $this->agent->update([
            'password' => Hash::make('NewSecretPassword999!'),
        ]);

        // 3. La requête suivante dans l'ancienne session doit être rejetée (AuthenticateSession)
        $nextResponse = $this->get(route('accounts.index'));

        $nextResponse->assertRedirect(route('login'));
        $this->assertGuest();
    }

    /**
     * Test 3 : Un utilisateur actif peut se connecter, un compte inactif est refusé.
     */
    public function test_active_user_can_login_and_inactive_user_cannot(): void
    {
        // Utilisateur actif -> connexion réussie
        $response = $this->post(route('login'), [
            'email' => 'agent@test.com',
            'password' => 'Password123!',
        ]);

        $response->assertRedirect();
        $this->assertAuthenticatedAs($this->agent);

        // Déconnexion
        $this->post(route('logout'));
        $this->assertGuest();

        // Désactivation du compte
        $this->agent->forceFill(['statut' => 'inactif'])->save();

        // Tentative de connexion -> refusée
        $failedResponse = $this->post(route('login'), [
            'email' => 'agent@test.com',
            'password' => 'Password123!',
        ]);

        $this->assertGuest();
    }

    /**
     * Test 4 : Le logout fonctionne et détruit l'authentification.
     */
    public function test_logout_works_and_clears_session(): void
    {
        $this->actingAs($this->agent);
        $this->assertAuthenticated();

        $response = $this->post(route('logout'));

        $response->assertRedirect();
        $this->assertGuest();
    }

    /**
     * Test 5 : La mise à jour de mot de passe fonctionne via Fortify et via l'espace Direction.
     */
    public function test_password_update_works_via_fortify(): void
    {
        $this->actingAs($this->agent);

        $response = $this->put(route('user-password.update'), [
            'current_password' => 'Password123!',
            'password' => 'BrandNewPassword888!',
            'password_confirmation' => 'BrandNewPassword888!',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('BrandNewPassword888!', $this->agent->fresh()->password));
    }

    /**
     * Test 5b : La mise à jour de mot de passe du directeur fonctionne et préserve sa session courante.
     */
    public function test_director_can_change_own_password(): void
    {
        $this->actingAs($this->director);

        $response = $this->post(route('director.motdepasse'), [
            'mot_de_passe_actuel' => 'Password123!',
            'nouveau_mot_de_passe' => 'DirectorNewPass999!',
            'nouveau_mot_de_passe_confirmation' => 'DirectorNewPass999!',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertTrue(Hash::check('DirectorNewPass999!', $this->director->fresh()->password));
    }

    /**
     * Test 6 : 2FA fonctionne si elle est activée.
     */
    public function test_two_factor_authentication_challenge_flow(): void
    {
        // Configurer 2FA manuellement sur l'utilisateur
        $this->agent->forceFill([
            'two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'),
            'two_factor_recovery_codes' => encrypt(json_encode(['recovery-code-123', 'recovery-code-456'])),
            'two_factor_confirmed_at' => now(),
        ])->save();

        // Tentative de login
        $response = $this->post(route('login'), [
            'email' => 'agent@test.com',
            'password' => 'Password123!',
        ]);

        // Doit rediriger vers le challenge 2FA et ne pas être encore pleinement connecté
        $response->assertRedirect(route('two-factor.login'));
        $this->assertGuest();

        // Répondre avec un recovery code valide
        $challengeResponse = $this->post(route('two-factor.login'), [
            'recovery_code' => 'recovery-code-123',
        ]);

        $challengeResponse->assertRedirect();
        $this->assertAuthenticatedAs($this->agent);
    }

    /**
     * Test 7 : Aucune BindingResolutionException liée à Fortify.
     */
    public function test_fortify_bindings_resolve_without_exception(): void
    {
        $updater = app(UpdatesUserPasswords::class);
        $this->assertInstanceOf(UpdatesUserPasswords::class, $updater);

        $profileUpdater = app(UpdatesUserProfileInformation::class);
        $this->assertInstanceOf(UpdatesUserProfileInformation::class, $profileUpdater);

        // Route login GET
        $this->get(route('login'))->assertOk();

        // Route confirm password GET (avec utilisateur connecté)
        $this->actingAs($this->agent)->get(route('password.confirm'))->assertOk();
    }

    /**
     * Test 8 : Un compte désactivé ne peut pas effectuer une opération financière.
     */
    public function test_deactivated_account_cannot_perform_financial_operation(): void
    {
        $initialBalance = $this->account->solde;

        // Session initiale
        $this->actingAs($this->agent);

        // Désactivation du compte
        $this->agent->forceFill(['statut' => 'inactif'])->save();

        // Tentative d'effectuer un dépôt
        $response = $this->post(route('accounts.depot', $this->account), [
            'montant' => 500.00,
            'moyen' => 'especes',
            'description' => 'Depot illegal par compte desactive',
        ]);

        // Requête bloquée et session invalidée
        $response->assertRedirect(route('login'));
        $this->assertGuest();

        // Le solde du compte ne doit pas avoir bougé
        $this->assertEquals($initialBalance, $this->account->fresh()->solde);

        // Aucune transaction ne doit avoir été enregistrée
        $this->assertEquals(0, Transaction::where('account_id', $this->account->id)->count());
    }
}
