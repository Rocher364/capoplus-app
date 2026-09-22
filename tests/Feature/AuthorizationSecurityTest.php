<?php

namespace Tests\Feature;

use App\Enums\LoanStatus;
use App\Enums\UserRole;
use App\Models\Account;
use App\Models\Loan;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests de securite pour la Phase 1 :
 * - VULN-01 : Autorisation des operations sur les prets (approve/reject/disburse)
 * - VULN-02 : Isolation de l'auditeur
 * - VULN-10 : Autorisation sur comptes (debloquer) et membres (delete)
 */
class AuthorizationSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $agent;
    protected User $auditeur;
    protected Member $member;
    protected Account $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => UserRole::Admin->value,
            'statut' => 'actif',
        ]);

        $this->agent = User::factory()->create([
            'role' => UserRole::Agent->value,
            'statut' => 'actif',
        ]);

        $this->auditeur = User::factory()->create([
            'role' => UserRole::Auditeur->value,
            'statut' => 'actif',
        ]);

        $this->member = Member::create([
            'numero_membre' => 'MBR-TEST01',
            'prenom' => 'Jean',
            'nom' => 'DUPONT',
            'statut' => 'actif',
            'cree_par_id' => $this->admin->id,
        ]);

        $this->account = Account::create([
            'member_id' => $this->member->id,
            'numero_compte' => 'CP-TEST-000001',
            'solde' => 0,
            'statut' => 'actif',
        ]);
    }

    /**
     * Helper : creer un pret en statut 'demande' demande par un utilisateur specifique.
     */
    protected function createLoanInStatus(LoanStatus $status, ?User $demandePar = null): Loan
    {
        $requestor = $demandePar ?? $this->agent;

        $loan = new Loan([
            'member_id' => $this->member->id,
            'account_id' => $this->account->id,
            'numero_pret' => 'PRT-TEST-' . strtoupper(uniqid()),
            'montant_demande' => 10000.00,
            'motif' => 'Test motif',
            'duree_mois' => 12,
            'type_taux' => 'fixe',
            'taux_interet' => 10.000,
            'methode_calcul' => 'simple',
            'demande_par_id' => $requestor->id,
            'date_demande' => now(),
        ]);
        $loan->forceFill(['statut' => $status->value])->save();

        if ($status === LoanStatus::Approuve || $status === LoanStatus::Decaisse) {
            $loan->forceFill([
                'montant_approuve' => 10000.00,
                'approuve_par_id' => $this->admin->id,
                'date_decision' => now(),
            ])->save();
        }

        return $loan;
    }

    // =========================================================================
    // VULN-01 : Tests d'autorisation sur les prets
    // =========================================================================

    /** Test 1 : Un agent ne peut PAS approuver un pret (403). */
    public function test_agent_cannot_approve_loan(): void
    {
        $loan = $this->createLoanInStatus(LoanStatus::Demande);

        $response = $this->actingAs($this->agent)->post(
            route('loans.approuver', $loan)
        );

        $response->assertStatus(403);
        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'statut' => LoanStatus::Demande->value,
        ]);
    }

    /** Test 2 : Un admin PEUT approuver un pret. */
    public function test_admin_can_approve_loan(): void
    {
        $loan = $this->createLoanInStatus(LoanStatus::Demande);

        $response = $this->actingAs($this->admin)->post(
            route('loans.approuver', $loan),
            ['montant_approuve' => 5000]
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'statut' => LoanStatus::Approuve->value,
            'montant_approuve' => '5000.00',
        ]);
    }

    /** Test 3 : Un admin ne peut PAS approuver un pret qu'il a lui-meme demande (auto-approbation). */
    public function test_admin_cannot_self_approve_loan(): void
    {
        $loan = $this->createLoanInStatus(LoanStatus::Demande, $this->admin);

        $response = $this->actingAs($this->admin)->post(
            route('loans.approuver', $loan)
        );

        $response->assertStatus(403);
        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'statut' => LoanStatus::Demande->value,
        ]);
    }

    /** Test 4 : Un agent ne peut PAS rejeter un pret (403). */
    public function test_agent_cannot_reject_loan(): void
    {
        $loan = $this->createLoanInStatus(LoanStatus::Demande);

        $response = $this->actingAs($this->agent)->post(
            route('loans.rejeter', $loan),
            ['justification_decision' => 'Dossier incomplet']
        );

        $response->assertStatus(403);
        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'statut' => LoanStatus::Demande->value,
        ]);
    }

    /** Test 5 : Un agent ne peut PAS decaisser un pret (403). */
    public function test_agent_cannot_disburse_loan(): void
    {
        $loan = $this->createLoanInStatus(LoanStatus::Approuve);

        $response = $this->actingAs($this->agent)->post(
            route('loans.decaisser', $loan)
        );

        $response->assertStatus(403);
        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'statut' => LoanStatus::Approuve->value,
        ]);
    }

    /** Test 6 : Le montant approuve ne peut pas depasser le montant demande. */
    public function test_approved_amount_cannot_exceed_requested(): void
    {
        $loan = $this->createLoanInStatus(LoanStatus::Demande);

        $response = $this->actingAs($this->admin)->post(
            route('loans.approuver', $loan),
            ['montant_approuve' => 99999.99]
        );

        // La validation Laravel devrait rejeter avec une erreur de validation (302 redirect avec erreurs)
        $response->assertSessionHasErrors('montant_approuve');
        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'statut' => LoanStatus::Demande->value,
        ]);
    }

    // =========================================================================
    // VULN-02 : Isolation de l'auditeur
    // =========================================================================

    /** Test 7 : Un auditeur est redirige quand il accede aux routes operationnelles. */
    public function test_auditeur_redirected_from_operational_routes(): void
    {
        $response = $this->actingAs($this->auditeur)->get(route('loans.index'));

        $response->assertRedirect(route('director.dashboard'));
    }

    /** Test 8 : Un auditeur recoit un 403 sur les routes operationnelles en AJAX. */
    public function test_auditeur_gets_403_on_operational_routes_ajax(): void
    {
        $response = $this->actingAs($this->auditeur)
            ->getJson(route('loans.index'));

        $response->assertStatus(403);
    }

    /** Test 9 : Un admin/agent peut acceder aux routes operationnelles normalement. */
    public function test_admin_can_access_operational_routes(): void
    {
        $response = $this->actingAs($this->admin)->get(route('loans.index'));

        $response->assertStatus(200);
    }

    // =========================================================================
    // VULN-10 : Autorisation sur comptes et membres
    // =========================================================================

    /** Test 10 : Un agent ne peut PAS debloquer un compte (403). */
    public function test_agent_cannot_unblock_account(): void
    {
        $this->account->forceFill([
            'statut' => 'bloque',
            'raison_blocage' => 'Test',
            'bloque_par_id' => $this->admin->id,
            'bloque_le' => now(),
        ])->save();

        $response = $this->actingAs($this->agent)->post(
            route('accounts.debloquer', $this->account)
        );

        $response->assertStatus(403);
        $this->assertDatabaseHas('accounts', [
            'id' => $this->account->id,
            'statut' => 'bloque',
        ]);
    }

    /** Test 11 : Un admin PEUT debloquer un compte. */
    public function test_admin_can_unblock_account(): void
    {
        $this->account->forceFill([
            'statut' => 'bloque',
            'raison_blocage' => 'Test',
            'bloque_par_id' => $this->admin->id,
            'bloque_le' => now(),
        ])->save();

        $response = $this->actingAs($this->admin)->post(
            route('accounts.debloquer', $this->account)
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('accounts', [
            'id' => $this->account->id,
            'statut' => 'actif',
        ]);
    }

    /** Test 12 : Un agent ne peut PAS supprimer un membre (403). */
    public function test_agent_cannot_delete_member(): void
    {
        $member = Member::create([
            'numero_membre' => 'MBR-DEL-TEST',
            'prenom' => 'Marie',
            'nom' => 'CLAIRE',
            'statut' => 'actif',
        ]);

        $response = $this->actingAs($this->agent)->delete(
            route('members.destroy', $member)
        );

        $response->assertStatus(403);
        $this->assertDatabaseHas('members', ['id' => $member->id, 'deleted_at' => null]);
    }

    /** Test 13 : Un admin PEUT supprimer un membre (sans historique financier). */
    public function test_admin_can_delete_member(): void
    {
        $member = Member::create([
            'numero_membre' => 'MBR-DEL-ADM',
            'prenom' => 'Paul',
            'nom' => 'MARTIN',
            'statut' => 'actif',
        ]);

        $response = $this->actingAs($this->admin)->delete(
            route('members.destroy', $member)
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertSoftDeleted('members', ['id' => $member->id]);
    }
}
