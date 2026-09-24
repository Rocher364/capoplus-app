<?php

namespace Tests\Feature;

use App\Enums\LoanStatus;
use App\Enums\UserRole;
use App\Exceptions\UnauthorizedActionException;
use App\Models\Account;
use App\Models\Loan;
use App\Models\LoanSchedule;
use App\Models\Member;
use App\Models\Repayment;
use App\Models\Transaction;
use App\Models\User;
use App\Services\DepotRetraitService;
use App\Services\LoanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * Tests de securite et d'integrite financiere pour la Phase 5 :
 * - VULN-06 : Remboursement des prets decaisses et en retard
 * - VULN-07 : Prevention de la race condition et double decaissement
 * - VULN-11 : Precision et ajustement des arrondis de l'echeancier (SUM(capital) === montant_approuve)
 * - VULN-14 : Plafond maximal configurable sur les depots
 * - Prevention du double remboursement / idempotence
 * - Coherence transactionnelle et atomicite (rollback sans orphelins)
 * - Non-regression RBAC
 */
class FinancialIntegritySecurityTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $agent;
    protected User $auditeur;
    protected Member $member;
    protected Account $account;
    protected LoanService $loanService;
    protected DepotRetraitService $depotService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loanService = app(LoanService::class);
        $this->depotService = app(DepotRetraitService::class);

        $this->admin = User::factory()->create([
            'email' => 'admin@capoplus.test',
            'role' => UserRole::Admin->value,
            'statut' => 'actif',
        ]);

        $this->agent = User::factory()->create([
            'email' => 'agent@capoplus.test',
            'role' => UserRole::Agent->value,
            'statut' => 'actif',
        ]);

        $this->auditeur = User::factory()->create([
            'email' => 'auditeur@capoplus.test',
            'role' => UserRole::Auditeur->value,
            'statut' => 'actif',
        ]);

        $this->member = Member::create([
            'numero_membre' => 'MBR-FIN-01',
            'prenom' => 'Marie',
            'nom' => 'JEAN',
            'telephone' => '+50938880001',
            'nif_cin' => 'NIF-FIN-001',
            'statut' => 'actif',
            'cree_par_id' => $this->admin->id,
        ]);

        $this->account = (new Account())->forceFill([
            'member_id' => $this->member->id,
            'numero_compte' => 'CP-FIN-001',
            'solde' => 5000.00,
            'statut' => 'actif',
        ]);
        $this->account->save();
    }

    // =========================================================================
    // 1. VULN-06 — REMBOURSEMENT DES PRÊTS (Decaisse & EnRetard)
    // =========================================================================

    public function test_repayment_is_allowed_for_disbursed_loan(): void
    {
        $loan = $this->creerPretDecaisse(1000.00, 2);
        $echeance = $loan->schedules()->first();

        $montantRemboursement = (float) $echeance->montant_total;

        $repayment = $this->loanService->enregistrerRemboursement($echeance, $montantRemboursement, $this->agent);

        $this->assertInstanceOf(Repayment::class, $repayment);
        $this->assertEquals('payee', $echeance->fresh()->statut);
        $this->assertEquals('0.00', (string) $echeance->fresh()->solde_restant);
    }

    public function test_repayment_is_allowed_for_overdue_loan(): void
    {
        $loan = $this->creerPretDecaisse(1000.00, 2);
        // Passer le pret et son echeance en retard
        $loan->forceFill(['statut' => LoanStatus::EnRetard])->save();
        $echeance = $loan->schedules()->first();
        $echeance->update(['statut' => 'en_retard']);

        $montantRemboursement = (float) $echeance->montant_total;

        $repayment = $this->loanService->enregistrerRemboursement($echeance, $montantRemboursement, $this->agent);

        $this->assertInstanceOf(Repayment::class, $repayment);
        $this->assertEquals('payee', $echeance->fresh()->statut);
        $this->assertEquals('0.00', (string) $echeance->fresh()->solde_restant);

        // Puisqu'il reste une echeance non en retard, le statut du pret doit etre repasse a 'decaisse'
        $this->assertEquals(LoanStatus::Decaisse, $loan->fresh()->statut);
    }

    public function test_repayment_rejects_invalid_loan_status(): void
    {
        $loan = Loan::create([
            'member_id' => $this->member->id,
            'account_id' => $this->account->id,
            'numero_pret' => 'PRT-INVALID-01',
            'montant_demande' => 1000.00,
            'motif' => 'Test',
            'duree_mois' => 3,
            'type_taux' => 'fixe',
            'taux_interet' => 10.00,
            'methode_calcul' => 'simple',
            'demande_par_id' => $this->agent->id,
            'date_demande' => now(),
        ]);
        $loan->forceFill(['statut' => LoanStatus::Demande])->save();

        $echeance = LoanSchedule::create([
            'loan_id' => $loan->id,
            'numero_echeance' => 1,
            'date_echeance' => now()->addMonth(),
            'capital' => 333.33,
            'interet' => 10.00,
            'montant_total' => 343.33,
            'montant_paye' => 0.00,
            'solde_restant' => 343.33,
            'statut' => 'a_venir',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Les remboursements sont possibles uniquement pour un pret decaisse ou en retard.');

        $this->loanService->enregistrerRemboursement($echeance, 100.00, $this->agent);
    }

    public function test_repayment_updates_remaining_balance_correctly(): void
    {
        $loan = $this->creerPretDecaisse(1200.00, 3);
        $echeance = $loan->schedules()->first();

        $montantTotalInitial = (float) $echeance->montant_total;
        $paiementPartiel = 150.00;

        $this->loanService->enregistrerRemboursement($echeance, $paiementPartiel, $this->agent);

        $echeanceFraiche = $echeance->fresh();
        $this->assertEquals('partiellement_payee', $echeanceFraiche->statut);
        $this->assertEquals(number_format($paiementPartiel, 2, '.', ''), (string) $echeanceFraiche->montant_paye);
        $attenduRestant = bcsub((string) $montantTotalInitial, (string) $paiementPartiel, 2);
        $this->assertEquals($attenduRestant, (string) $echeanceFraiche->solde_restant);
    }

    // =========================================================================
    // 2. VULN-07 — RACE CONDITION DU DÉCAISSEMENT
    // =========================================================================

    public function test_loan_cannot_be_disbursed_twice(): void
    {
        $loan = Loan::create([
            'member_id' => $this->member->id,
            'account_id' => $this->account->id,
            'numero_pret' => 'PRT-DISB-01',
            'montant_demande' => 1000.00,
            'motif' => 'Commerce',
            'duree_mois' => 3,
            'type_taux' => 'fixe',
            'taux_interet' => 10.00,
            'methode_calcul' => 'simple',
            'demande_par_id' => $this->agent->id,
            'date_demande' => now(),
        ]);
        $loan->forceFill([
            'statut' => LoanStatus::Approuve,
            'montant_approuve' => 1000.00,
            'approuve_par_id' => $this->admin->id,
            'date_decision' => now(),
        ])->save();

        $soldeAvant = $this->account->fresh()->solde;

        // 1er decaissement : doit reussir
        $this->loanService->decaisser($loan, $this->admin, $this->depotService);
        $this->assertEquals(LoanStatus::Decaisse, $loan->fresh()->statut);
        $this->assertEquals(bcadd((string) $soldeAvant, '1000.00', 2), (string) $this->account->fresh()->solde);

        // 2eme tentative : doit echouer
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Seul un pret approuve avec un montant valide peut etre decaisse.');

        $this->loanService->decaisser($loan->fresh(), $this->admin, $this->depotService);
    }

    public function test_second_disbursement_is_rejected(): void
    {
        $loan = $this->creerPretApprouve(2000.00);

        // 1er decaissement
        $this->loanService->decaisser($loan, $this->admin, $this->depotService);

        // 2eme tentative directe via service
        try {
            $this->loanService->decaisser($loan, $this->admin, $this->depotService);
            $this->fail('La seconde tentative de decaissement aurait du lever une exception.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('Seul un pret approuve', $e->getMessage());
        }

        // Verifier qu'il n'existe qu'une seule transaction de decaissement
        $nbTransactions = Transaction::where('operation_type', Loan::class)
            ->where('operation_id', $loan->id)
            ->count();
        $this->assertEquals(1, $nbTransactions);
    }

    public function test_disbursement_is_atomic(): void
    {
        $loan = $this->creerPretApprouve(1500.00);
        $soldeInitial = $this->account->fresh()->solde;

        // Simuler un echec de generation d'echeancier ou d'etat invalide
        // en injectant une duree de 0 (calcul division par zero)
        $loan->forceFill(['duree_mois' => 0])->save();

        try {
            $this->loanService->decaisser($loan, $this->admin, $this->depotService);
            $this->fail('Le decaissement avec duree 0 aurait du echouer.');
        } catch (\Throwable $e) {
            // L'erreur doit avoir provoque un rollback complet
        }

        // Le statut du pret doit etre reste 'approuve' (rollback)
        $this->assertEquals(LoanStatus::Approuve, $loan->fresh()->statut);
        // Le solde du compte ne doit pas avoir ete incremente
        $this->assertEquals((string) $soldeInitial, (string) $this->account->fresh()->solde);
        // Aucune transaction financiere ne doit subsister
        $this->assertEquals(0, Transaction::where('operation_id', $loan->id)->count());
        // Aucun echeancier partiel ne doit subsister
        $this->assertEquals(0, LoanSchedule::where('loan_id', $loan->id)->count());
    }

    // =========================================================================
    // 3. VULN-11 — ARRONDIS DE L'ÉCHÉANCIER
    // =========================================================================

    public function test_schedule_capital_sum_equals_approved_amount(): void
    {
        $montants = [1000.00, 1000.01, 10000.00, 12345.67];
        $durees = [3, 6, 7, 12];

        foreach ($montants as $montant) {
            foreach ($durees as $duree) {
                $loan = $this->creerPretDecaisse($montant, $duree, 'simple');

                $sommeCapital = LoanSchedule::where('loan_id', $loan->id)
                    ->get()
                    ->reduce(fn ($carry, $s) => bcadd((string) $carry, (string) $s->capital, 2), '0.00');

                $this->assertEquals(
                    number_format($montant, 2, '.', ''),
                    $sommeCapital,
                    "Echec SUM(capital) pour montant {$montant} et duree {$duree}"
                );
            }
        }
    }

    public function test_schedule_rounding_is_correct(): void
    {
        // 1000 / 3 = 333.33, 333.33, 333.34
        $loan = $this->creerPretDecaisse(1000.00, 3, 'simple');
        $schedules = LoanSchedule::where('loan_id', $loan->id)->orderBy('numero_echeance')->get();

        $this->assertEquals('333.33', (string) $schedules[0]->capital);
        $this->assertEquals('333.33', (string) $schedules[1]->capital);
        $this->assertEquals('333.34', (string) $schedules[2]->capital);

        $totalCapital = bcadd(bcadd((string) $schedules[0]->capital, (string) $schedules[1]->capital, 2), (string) $schedules[2]->capital, 2);
        $this->assertEquals('1000.00', $totalCapital);
    }

    public function test_last_installment_adjusts_rounding_difference(): void
    {
        // Montant 1000.01 sur 3 mois
        $loan = $this->creerPretDecaisse(1000.01, 3, 'simple');
        $schedules = LoanSchedule::where('loan_id', $loan->id)->orderBy('numero_echeance')->get();

        $somme = '0.00';
        foreach ($schedules as $s) {
            $somme = bcadd($somme, (string) $s->capital, 2);
        }

        $this->assertEquals('1000.01', $somme);
    }

    // =========================================================================
    // 4. VULN-14 — LIMITE DES DÉPÔTS (DÉSACTIVÉE PAR DÉFAUT, SANS RÈGLE ARBITRAIRE)
    // =========================================================================

    public function test_deposit_when_limit_is_not_configured_is_allowed(): void
    {
        // Par defaut, aucun plafond arbitraire n'est impose (max_deposit = null)
        config(['capoplus.max_deposit' => null]);

        $this->actingAs($this->agent);

        $response = $this->post(route('accounts.depot', $this->account), [
            'montant' => '250000.00',
            'moyen' => 'especes',
            'description' => 'Depot sans limite configuree',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_deposit_under_configured_limit_is_allowed(): void
    {
        config(['capoplus.max_deposit' => 10000.00]);

        $this->actingAs($this->agent);

        $response = $this->post(route('accounts.depot', $this->account), [
            'montant' => '5000.00',
            'moyen' => 'especes',
            'description' => 'Depot sous la limite configuree',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_deposit_above_configured_limit_is_rejected(): void
    {
        config(['capoplus.max_deposit' => 10000.00]);

        $this->actingAs($this->agent);

        // 1. Rejet niveau FormRequest
        $response = $this->from(route('accounts.show', $this->account))->post(route('accounts.depot', $this->account), [
            'montant' => '15000.00',
            'moyen' => 'especes',
            'description' => 'Depot au-dessus de la limite',
        ]);

        $response->assertRedirect(route('accounts.show', $this->account));
        $response->assertSessionHasErrors('montant');

        // 2. Rejet niveau service direct
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Le montant du depot depasse la limite autorisee');

        $this->depotService->deposer($this->account, 15000.00, $this->agent);
    }

    // =========================================================================
    // 5. DOUBLE REMBOURSEMENT / IDEMPOTENCE
    // =========================================================================

    public function test_exact_idempotent_repayment_scenario_solde_500_remboursement_100(): void
    {
        // Etat initial : echeance avec solde_restant = 500.00
        $loan = $this->creerPretApprouve(500.00, 1);
        $loan->forceFill(['statut' => LoanStatus::Decaisse, 'date_decaissement' => now()])->save();
        $echeance = LoanSchedule::create([
            'loan_id' => $loan->id,
            'numero_echeance' => 1,
            'date_echeance' => now()->addMonth(),
            'capital' => '500.00',
            'interet' => '0.00',
            'montant_total' => '500.00',
            'montant_paye' => '0.00',
            'solde_restant' => '500.00',
            'statut' => 'a_venir',
        ]);

        $soldeCompteInitial = (string) $this->account->fresh()->solde;
        $idempotencyKey = 'IDEMP-TEST-500-TO-400';

        // 1ere requete : remboursement de 100.00 avec identite de paiement
        $rep1 = $this->loanService->enregistrerRemboursement($echeance, 100.00, $this->agent, [
            'reference' => $idempotencyKey,
        ]);

        $this->assertInstanceOf(Repayment::class, $rep1);
        $this->assertEquals('400.00', (string) $echeance->fresh()->solde_restant);
        $this->assertEquals('100.00', (string) $echeance->fresh()->montant_paye);
        $this->assertEquals(bcsub($soldeCompteInitial, '100.00', 2), (string) $this->account->fresh()->solde);
        $this->assertEquals(1, Repayment::where('reference', $idempotencyKey)->count());

        // 2eme requete : envoi EXACTEMENT de la meme identite de paiement pour 100.00
        $rep2 = $this->loanService->enregistrerRemboursement($echeance->fresh(), 100.00, $this->agent, [
            'reference' => $idempotencyKey,
        ]);

        // Verification du resultat attendu :
        // 1. Retourne le remboursement existant sans duplication
        $this->assertEquals($rep1->id, $rep2->id);
        $this->assertEquals(1, Repayment::where('reference', $idempotencyKey)->count());

        // 2. Solde final de l'echeance EXACTEMENT a 400.00 (aucun 2eme debit sur l'echeance)
        $this->assertEquals('400.00', (string) $echeance->fresh()->solde_restant);

        // 3. Solde du compte debite une seule et unique fois
        $this->assertEquals(bcsub($soldeCompteInitial, '100.00', 2), (string) $this->account->fresh()->solde);
    }

    public function test_same_repayment_via_http_controller_is_idempotent(): void
    {
        $loan = $this->creerPretDecaisse(1000.00, 2);
        $echeance = $loan->schedules()->first();

        $this->actingAs($this->agent);
        $idempotencyKey = 'IDEMP-HTTP-PAY-01';

        // 1ere requete HTTP
        $res1 = $this->post(route('loans.rembourser', [$loan, $echeance]), [
            'montant' => '100.00',
            'moyen' => 'especes',
            'idempotency_key' => $idempotencyKey,
        ]);
        $res1->assertRedirect();
        $this->assertEquals(1, Repayment::where('reference', $idempotencyKey)->count());

        // 2eme requete HTTP avec le meme header / idempotency_key
        $res2 = $this->post(route('loans.rembourser', [$loan, $echeance]), [
            'montant' => '100.00',
            'moyen' => 'especes',
            'idempotency_key' => $idempotencyKey,
        ]);
        $res2->assertRedirect();
        // Aucun double paiement cree
        $this->assertEquals(1, Repayment::where('reference', $idempotencyKey)->count());
    }

    public function test_repayments_without_idempotency_key_are_treated_as_two_distinct_payments(): void
    {
        // Quand aucune cle d'idempotence n'est fournie, chaque requete genere une reference unique
        // et constitue un nouveau paiement partiel legitime debitant le compte
        $loan = $this->creerPretApprouve(500.00, 1);
        $loan->forceFill(['statut' => LoanStatus::Decaisse, 'date_decaissement' => now()])->save();
        $echeance = LoanSchedule::create([
            'loan_id' => $loan->id,
            'numero_echeance' => 1,
            'date_echeance' => now()->addMonth(),
            'capital' => '500.00',
            'interet' => '0.00',
            'montant_total' => '500.00',
            'montant_paye' => '0.00',
            'solde_restant' => '500.00',
            'statut' => 'a_venir',
        ]);

        $soldeCompteInitial = (string) $this->account->fresh()->solde;

        // 1er remboursement de 100 sans cle d'idempotence
        $rep1 = $this->loanService->enregistrerRemboursement($echeance, 100.00, $this->agent);
        $this->assertEquals('400.00', (string) $echeance->fresh()->solde_restant);
        $this->assertEquals(bcsub($soldeCompteInitial, '100.00', 2), (string) $this->account->fresh()->solde);

        // 2eme remboursement de 100 sans cle d'idempotence
        $rep2 = $this->loanService->enregistrerRemboursement($echeance->fresh(), 100.00, $this->agent);

        // Deux enregistrements distincts avec des references differentes sont crees
        $this->assertNotEquals($rep1->id, $rep2->id);
        $this->assertNotEquals($rep1->reference, $rep2->reference);
        $this->assertEquals(2, Repayment::where('loan_schedule_id', $echeance->id)->count());

        // L'echeance passe a 300.00 et le compte est debite de 200.00 au total
        $this->assertEquals('300.00', (string) $echeance->fresh()->solde_restant);
        $this->assertEquals(bcsub($soldeCompteInitial, '200.00', 2), (string) $this->account->fresh()->solde);
    }

    public function test_concurrent_repayment_does_not_double_credit_account(): void
    {
        $loan = $this->creerPretDecaisse(1000.00, 2);
        $echeance = $loan->schedules()->first();
        $montantTotal = (float) $echeance->montant_total;

        $soldeInitial = (float) $this->account->fresh()->solde;

        // Payer l'integralite de l'echeance
        $this->loanService->enregistrerRemboursement($echeance, $montantTotal, $this->agent);

        $soldeApresPremier = (string) $this->account->fresh()->solde;
        $this->assertEquals(
            bcsub((string) $soldeInitial, (string) $montantTotal, 2),
            $soldeApresPremier
        );

        // Tentative d'un second paiement identique sur la meme echeance deja payee
        try {
            $this->loanService->enregistrerRemboursement($echeance, $montantTotal, $this->agent);
            $this->fail('Un second paiement sur une echeance payee doit etre rejete.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('deja integralement payee', $e->getMessage());
        }

        // Le compte ne doit PAS avoir ete debite deux fois
        $this->assertEquals($soldeApresPremier, (string) $this->account->fresh()->solde);
    }

    public function test_repayment_and_loan_state_remain_consistent(): void
    {
        $loan = $this->creerPretDecaisse(1000.00, 1);
        $echeance = $loan->schedules()->first();

        // Paiement complet de la seule echeance
        $this->loanService->enregistrerRemboursement($echeance, (float) $echeance->montant_total, $this->agent);

        // Le pret doit etre passe a 'solde'
        $this->assertEquals(LoanStatus::Solde, $loan->fresh()->statut);
        $this->assertEquals('payee', $echeance->fresh()->statut);
        $this->assertEquals('0.00', (string) $echeance->fresh()->solde_restant);
    }

    // =========================================================================
    // 6. COHÉRENCE TRANSACTION ↔ SOLDE & ROLLBACK
    // =========================================================================

    public function test_transaction_and_account_balance_are_always_in_sync(): void
    {
        $soldeInitial = $this->account->fresh()->solde;
        $depot = 750.00;

        $tx = $this->depotService->deposer($this->account, $depot, $this->agent);

        $this->assertEquals($this->account->fresh()->solde, $tx->solde_apres);
        $this->assertEquals(bcadd((string) $soldeInitial, (string) $depot, 2), (string) $this->account->fresh()->solde);
    }

    public function test_failed_financial_operation_rolls_back_both_transaction_and_balance(): void
    {
        $soldeInitial = $this->account->fresh()->solde;

        // Tentative de retrait avec montant superieur au solde
        try {
            $this->depotService->retirer($this->account, (float) $soldeInitial + 1000.00, $this->agent);
            $this->fail('Le retrait au-dela du solde aurait du echouer.');
        } catch (\App\Exceptions\SoldeInsuffisantException $e) {
            // Attendu
        }

        // Verification de l'absence d'ecriture orpheline
        $this->assertEquals((string) $soldeInitial, (string) $this->account->fresh()->solde);
        $this->assertEquals(0, Transaction::where('account_id', $this->account->id)->where('type', 'retrait')->count());
    }

    public function test_deposit_rolls_back_completely_if_downstream_step_fails(): void
    {
        $soldeInitial = (string) $this->account->fresh()->solde;
        $nbTransactionsInitial = Transaction::where('account_id', $this->account->id)->count();

        try {
            DB::transaction(function () {
                $this->depotService->deposer($this->account, 250.00, $this->agent);
                // Erreur artificielle declenchee apres le depot
                throw new \RuntimeException('Erreur artificielle simulant un crash post-depot.');
            });
            $this->fail('L exception aurait du interrompre la transaction.');
        } catch (\RuntimeException $e) {
            $this->assertEquals('Erreur artificielle simulant un crash post-depot.', $e->getMessage());
        }

        // L'ecriture Transaction et la mise a jour de Account.solde doivent toutes deux etre annulees
        $this->assertEquals($soldeInitial, (string) $this->account->fresh()->solde);
        $this->assertEquals($nbTransactionsInitial, Transaction::where('account_id', $this->account->id)->count());
    }

    public function test_repayment_rolls_back_completely_if_downstream_step_fails(): void
    {
        $loan = $this->creerPretDecaisse(1000.00, 2);
        $echeance = $loan->schedules()->first();

        $soldeCompteInitial = (string) $this->account->fresh()->solde;
        $soldeEcheanceInitial = (string) $echeance->fresh()->solde_restant;
        $nbRepaymentsInitial = Repayment::count();
        $nbTransactionsInitial = Transaction::count();

        try {
            DB::transaction(function () use ($echeance) {
                $this->loanService->enregistrerRemboursement($echeance, 100.00, $this->agent);
                // Erreur artificielle declenchee apres le remboursement
                throw new \RuntimeException('Erreur artificielle simulant un crash post-remboursement.');
            });
            $this->fail('L exception aurait du interrompre la transaction.');
        } catch (\RuntimeException $e) {
            $this->assertEquals('Erreur artificielle simulant un crash post-remboursement.', $e->getMessage());
        }

        // Verifications strictes du rollback atomique :
        // 1. Solde du compte inchange
        $this->assertEquals($soldeCompteInitial, (string) $this->account->fresh()->solde);
        // 2. Solde de l'echeance inchange
        $this->assertEquals($soldeEcheanceInitial, (string) $echeance->fresh()->solde_restant);
        // 3. Aucun Repayment orphelin
        $this->assertEquals($nbRepaymentsInitial, Repayment::count());
        // 4. Aucune Transaction financiere orpheline
        $this->assertEquals($nbTransactionsInitial, Transaction::count());
    }

    // =========================================================================
    // 7. NON-RÉGRESSION RBAC
    // =========================================================================

    public function test_auditeur_cannot_register_repayment(): void
    {
        $loan = $this->creerPretDecaisse(1000.00, 2);
        $echeance = $loan->schedules()->first();

        $this->expectException(UnauthorizedActionException::class);
        $this->expectExceptionMessage('Un auditeur ne peut pas enregistrer de remboursement.');

        $this->loanService->enregistrerRemboursement($echeance, 50.00, $this->auditeur);
    }

    public function test_agent_cannot_disburse_loan(): void
    {
        $loan = $this->creerPretApprouve(1000.00);

        $this->expectException(UnauthorizedActionException::class);
        $this->expectExceptionMessage('Seul un administrateur peut decaisser un pret.');

        $this->loanService->decaisser($loan, $this->agent, $this->depotService);
    }

    // =========================================================================
    // 8. PRÉCISION MONÉTAIRE & VALEURS LIMITES (BCMATH)
    // =========================================================================

    public function test_boundary_decimal_and_exact_precision_calculations(): void
    {
        // 1. Depot d'un centime (0.01) exact
        $soldeInitial = (string) $this->account->fresh()->solde;
        $tx = $this->depotService->deposer($this->account, 0.01, $this->agent);
        $this->assertEquals(bcadd($soldeInitial, '0.01', 2), (string) $this->account->fresh()->solde);
        $this->assertEquals('0.01', (string) $tx->montant);

        // 2. Retrait d'un centime (0.01) exact
        $txRetrait = $this->depotService->retirer($this->account, 0.01, $this->agent);
        $this->assertEquals((string) $soldeInitial, (string) $this->account->fresh()->solde);
        $this->assertEquals('0.01', (string) $txRetrait->montant);

        // 3. Montant zero ou negatif strictement rejete
        try {
            $this->depotService->deposer($this->account, 0.00, $this->agent);
            $this->fail('Un montant de 0.00 aurait du etre rejete.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('superieur a zero', $e->getMessage());
        }
    }

    public function test_decimal_monetary_precision_across_typical_boundary_values(): void
    {
        // Teste 0.01, 0.10, 0.99, 1.00, 1000.01, 12345.67
        $valeurs = ['0.01', '0.10', '0.99', '1.00', '1000.01', '12345.67'];

        foreach ($valeurs as $valeur) {
            $soldeAvant = (string) $this->account->fresh()->solde;

            // Depot
            $tx = $this->depotService->deposer($this->account, $valeur, $this->agent);
            $soldeAttenduApresDepot = bcadd($soldeAvant, $valeur, 2);
            $this->assertEquals($soldeAttenduApresDepot, (string) $this->account->fresh()->solde);
            $this->assertEquals($valeur, (string) $tx->montant);

            // Retrait
            $txRetrait = $this->depotService->retirer($this->account, $valeur, $this->agent);
            $this->assertEquals($soldeAvant, (string) $this->account->fresh()->solde);
            $this->assertEquals($valeur, (string) $txRetrait->montant);
        }
    }

    public function test_loan_approval_strictly_rejects_one_cent_above_requested_amount(): void
    {
        $loan = Loan::create([
            'member_id' => $this->member->id,
            'account_id' => $this->account->id,
            'numero_pret' => 'PRT-BCCOMP-01',
            'montant_demande' => 1000.00,
            'motif' => 'Test precision',
            'duree_mois' => 3,
            'type_taux' => 'fixe',
            'taux_interet' => 10.00,
            'methode_calcul' => 'simple',
            'demande_par_id' => $this->agent->id,
            'date_demande' => now(),
        ]);
        $loan->forceFill(['statut' => LoanStatus::Demande])->save();

        // 1000.01 doit etre strictement rejete sans imprecision flottante
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Le montant approuve ne peut pas depasser le montant demande.');

        $this->loanService->approuver($loan, $this->admin, 1000.01);
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    protected function creerPretApprouve(float $montant, int $duree = 3): Loan
    {
        $loan = Loan::create([
            'member_id' => $this->member->id,
            'account_id' => $this->account->id,
            'numero_pret' => 'PRT-'.uniqid(),
            'montant_demande' => $montant,
            'motif' => 'Projet Test',
            'duree_mois' => $duree,
            'type_taux' => 'fixe',
            'taux_interet' => 12.00,
            'methode_calcul' => 'simple',
            'demande_par_id' => $this->agent->id,
            'date_demande' => now(),
        ]);

        $loan->forceFill([
            'statut' => LoanStatus::Approuve,
            'montant_approuve' => $montant,
            'approuve_par_id' => $this->admin->id,
            'date_decision' => now(),
        ])->save();

        return $loan;
    }

    protected function creerPretDecaisse(float $montant, int $duree = 3, string $methode = 'simple'): Loan
    {
        $loan = $this->creerPretApprouve($montant, $duree);
        $loan->forceFill(['methode_calcul' => $methode])->save();

        return $this->loanService->decaisser($loan, $this->admin, $this->depotService);
    }
}
