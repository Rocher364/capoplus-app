<?php

namespace Tests\Feature;

use App\Enums\LoanStatus;
use App\Enums\UserRole;
use App\Models\Account;
use App\Models\Loan;
use App\Models\Member;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Tests de sécurité pour la Phase 4 :
 * - VULN-08 : Contrainte d'unicité accounts.member_id
 * - VULN-12 : Protection contre le Mass Assignment sur champs financiers et critiques
 * - Intégrité du solde (solde >= 0)
 * - VULN-18 : Interaction SoftDeletes et contraintes uniques sur les membres
 */
class DataIntegritySecurityTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $agent;
    protected Member $member;

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

        $this->member = Member::create([
            'numero_membre' => 'MBR-DATA-01',
            'prenom' => 'Jean',
            'nom' => 'DUPONT',
            'telephone' => '+50930000001',
            'email' => 'jean.dupont@test.com',
            'nif_cin' => 'NIF-000-001',
            'statut' => 'actif',
            'cree_par_id' => $this->admin->id,
        ]);
    }

    /**
     * Test 1 : duplicate_account_for_member_is_rejected
     * Un membre ne peut pas posséder plus d'un compte (contrainte unique accounts.member_id).
     */
    public function test_duplicate_account_for_member_is_rejected(): void
    {
        // 1. Premier compte créé légitimement
        Account::create([
            'member_id' => $this->member->id,
            'numero_compte' => 'CP-FIRST-001',
        ]);

        $this->assertDatabaseHas('accounts', [
            'member_id' => $this->member->id,
            'numero_compte' => 'CP-FIRST-001',
        ]);

        // 2. Tentative de création d'un deuxième compte pour le même membre
        $this->expectException(QueryException::class);

        Account::create([
            'member_id' => $this->member->id,
            'numero_compte' => 'CP-SECOND-002',
        ]);
    }

    /**
     * Test 2 : balance_cannot_be_mass_assigned
     * Le solde d'un compte ne peut pas être modifié via mass assignment ($account->update(['solde' => ...])).
     */
    public function test_balance_cannot_be_mass_assigned(): void
    {
        $account = Account::create([
            'member_id' => $this->member->id,
            'numero_compte' => 'CP-BAL-001',
        ]);

        // Vérifier solde initial par défaut (0.00)
        $this->assertEquals('0.00', (string) $account->fresh()->solde);

        // Tentative de modification directe du solde par mass assignment
        $account->update([
            'solde' => 999999.99,
        ]);

        // Le solde doit être protégé et inchangé
        $this->assertEquals('0.00', (string) $account->fresh()->solde);
    }

    /**
     * Test 3 : loan_status_cannot_be_mass_assigned
     * Le statut d'un prêt ne peut pas être modifié par mass assignment.
     */
    public function test_loan_status_cannot_be_mass_assigned(): void
    {
        $account = Account::create([
            'member_id' => $this->member->id,
            'numero_compte' => 'CP-LOAN-001',
        ]);

        $loan = Loan::create([
            'member_id' => $this->member->id,
            'account_id' => $account->id,
            'numero_pret' => 'PRT-TEST-001',
            'montant_demande' => 5000.00,
            'motif' => 'Investissement',
            'duree_mois' => 6,
            'type_taux' => 'fixe',
            'taux_interet' => 12.000,
            'methode_calcul' => 'simple',
            'demande_par_id' => $this->agent->id,
            'date_demande' => now(),
        ]);

        $this->assertEquals(LoanStatus::Demande, $loan->fresh()->statut);

        // Tentative d'auto-approbation / falsification de statut par mass assignment
        $loan->update([
            'statut' => LoanStatus::Approuve->value,
        ]);

        // Le statut doit rester inchangé en 'demande'
        $this->assertEquals(LoanStatus::Demande, $loan->fresh()->statut);
    }

    /**
     * Test 4 : loan_approved_amount_cannot_be_mass_assigned
     * Le montant approuvé d'un prêt ne peut pas être défini par mass assignment.
     */
    public function test_loan_approved_amount_cannot_be_mass_assigned(): void
    {
        $account = Account::create([
            'member_id' => $this->member->id,
            'numero_compte' => 'CP-LOAN-002',
        ]);

        $loan = Loan::create([
            'member_id' => $this->member->id,
            'account_id' => $account->id,
            'numero_pret' => 'PRT-TEST-002',
            'montant_demande' => 5000.00,
            'motif' => 'Commerce',
            'duree_mois' => 12,
            'type_taux' => 'fixe',
            'taux_interet' => 10.000,
            'methode_calcul' => 'simple',
            'demande_par_id' => $this->agent->id,
            'date_demande' => now(),
        ]);

        $this->assertNull($loan->fresh()->montant_approuve);

        // Tentative d'injection d'un montant approuvé par mass assignment
        $loan->update([
            'montant_approuve' => 99999.00,
        ]);

        // Le montant approuvé doit rester null
        $this->assertNull($loan->fresh()->montant_approuve);
    }

    /**
     * Test 5 : user_role_cannot_be_mass_assigned
     * Le rôle et le statut d'un utilisateur ne peuvent pas être escaladés par mass assignment.
     */
    public function test_user_role_cannot_be_mass_assigned(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Agent->value,
            'statut' => 'actif',
        ]);

        $this->assertEquals(UserRole::Agent, $user->fresh()->role);

        // Tentative d'élévation de privilèges vers admin
        $user->update([
            'role' => UserRole::Admin->value,
        ]);

        // Le rôle doit rester agent
        $this->assertEquals(UserRole::Agent, $user->fresh()->role);

        // Tentative de changement de statut
        $user->update([
            'statut' => 'inactif',
        ]);

        $this->assertEquals('actif', $user->fresh()->statut);
    }

    /**
     * Test 6 : negative_balance_is_rejected
     * Un solde négatif est rejeté au niveau modèle et au niveau base de données.
     */
    public function test_negative_balance_is_rejected(): void
    {
        $account = Account::create([
            'member_id' => $this->member->id,
            'numero_compte' => 'CP-NEG-001',
        ]);

        // 1. Rejet au niveau modèle / méthode métier
        $this->expectException(\InvalidArgumentException::class);
        $account->actualiserSolde('-25.00');
    }

    /**
     * Test 6b : Rejet au niveau base de données (contrainte DB / trigger solde >= 0).
     */
    public function test_database_enforces_positive_balance(): void
    {
        $this->expectException(QueryException::class);

        // Tentative d'insertion SQL directe outrepassant le modèle
        DB::table('accounts')->insert([
            'member_id' => $this->member->id,
            'numero_compte' => 'CP-RAW-NEG',
            'solde' => -100.00,
            'statut' => 'actif',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Test 7 : VULN-18 : Un membre supprimé (soft delete) libère ses identifiants uniques.
     */
    public function test_soft_deleted_member_unique_fields_can_be_reused_by_new_active_member(): void
    {
        // 1. Supprimer le membre initial (soft delete)
        $this->member->delete();
        $this->assertSoftDeleted('members', ['id' => $this->member->id]);

        // 2. Créer un nouveau membre avec les mêmes coordonnées (téléphone, email, NIF)
        $newMember = Member::create([
            'numero_membre' => 'MBR-DATA-02',
            'prenom' => 'Pierre',
            'nom' => 'DUPONT',
            'telephone' => '+50930000001',
            'email' => 'jean.dupont@test.com',
            'nif_cin' => 'NIF-000-001',
            'statut' => 'actif',
            'cree_par_id' => $this->admin->id,
        ]);

        $this->assertDatabaseHas('members', [
            'id' => $newMember->id,
            'numero_membre' => 'MBR-DATA-02',
            'telephone' => '+50930000001',
            'deleted_at' => null,
        ]);
    }

    /**
     * Test 8 : VULN-18 : Deux membres actifs NE PEUVENT PAS avoir le même téléphone.
     */
    public function test_active_members_cannot_have_duplicate_telephone(): void
    {
        $this->expectException(QueryException::class);

        // Tentative de créer un 2ème membre actif avec le même téléphone
        Member::create([
            'numero_membre' => 'MBR-DATA-03',
            'prenom' => 'Paul',
            'nom' => 'TEST',
            'telephone' => '+50930000001', // Même téléphone que $this->member actif
            'statut' => 'actif',
            'cree_par_id' => $this->admin->id,
        ]);
    }
}
