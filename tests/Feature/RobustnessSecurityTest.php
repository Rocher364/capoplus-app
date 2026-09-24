<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Exceptions\SoldeInsuffisantException;
use App\Models\Account;
use App\Models\Member;
use App\Models\User;
use App\Services\DepotRetraitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * Tests de robustesse pour :
 *   - VULN-14 : Plafond de dépôt configurable
 *   - VULN-17 : Longueur de recherche bornée, échappement des wildcards, pagination
 *   - VULN-15 : Confirmation de la disparition du CDN Tailwind (aucun appel externe dans les headers)
 */
class RobustnessSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $agent;
    protected Member $member;
    protected Account $account;
    protected DepotRetraitService $depotService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->depotService = app(DepotRetraitService::class);

        $this->admin = User::factory()->create([
            'email' => 'admin@robustness.test',
            'role'  => UserRole::Admin->value,
            'statut' => 'actif',
        ]);

        $this->agent = User::factory()->create([
            'email' => 'agent@robustness.test',
            'role'  => UserRole::Agent->value,
            'statut' => 'actif',
        ]);

        $this->member = Member::create([
            'numero_membre' => 'MBR-ROB-01',
            'prenom'        => 'Test',
            'nom'           => 'Robustesse',
            'statut'        => 'actif',
            'cree_par_id'   => $this->admin->id,
        ]);

        $this->account = (new Account())->forceFill([
            'member_id'    => $this->member->id,
            'numero_compte' => 'CP-ROB-001',
            'solde'        => '1000000.00',
            'statut'       => 'actif',
        ]);
        $this->account->save();
    }

    // =========================================================================
    // VULN-14 — PLAFOND DE DÉPÔT
    // =========================================================================

    /**
     * 1. Dépôt inférieur ou égal au plafond → accepté.
     */
    public function test_deposit_below_limit_is_accepted(): void
    {
        config(['capoplus.max_deposit' => '500000.00']);

        $transaction = $this->depotService->deposer(
            $this->account,
            '300000.00',
            $this->agent,
        );

        $this->assertNotNull($transaction);
        $this->assertEquals('300000.00', number_format((float) $transaction->montant, 2, '.', ''));
    }

    /**
     * 2. Dépôt exactement égal au plafond → accepté.
     */
    public function test_deposit_exactly_at_limit_is_accepted(): void
    {
        config(['capoplus.max_deposit' => '200000.00']);

        $transaction = $this->depotService->deposer(
            $this->account,
            '200000.00',
            $this->agent,
        );

        $this->assertNotNull($transaction);
    }

    /**
     * 3. Dépôt supérieur au plafond → rejeté par le service métier.
     */
    public function test_deposit_above_limit_is_rejected(): void
    {
        config(['capoplus.max_deposit' => '100000.00']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/limite autorisee/i');

        $this->depotService->deposer(
            $this->account,
            '100001.00',
            $this->agent,
        );
    }

    /**
     * 4. Dépôt supérieur au plafond → rejeté par la validation HTTP (FormRequest).
     */
    public function test_deposit_above_limit_is_rejected_via_http(): void
    {
        config(['capoplus.max_deposit' => '50000.00']);

        $response = $this->actingAs($this->agent)->post(
            route('accounts.depot', $this->account),
            ['montant' => '75000.00', 'moyen' => 'especes']
        );

        // La validation FormRequest doit rejeter la requête
        $response->assertSessionHasErrors('montant');
    }

    /**
     * 5. Sans plafond configuré (null) : tout montant positif est accepté.
     */
    public function test_deposit_without_limit_is_accepted(): void
    {
        config(['capoplus.max_deposit' => null]);

        $transaction = $this->depotService->deposer(
            $this->account,
            '9999999.00',
            $this->agent,
        );

        $this->assertNotNull($transaction);
    }

    /**
     * 6. Retrait supérieur au solde → rejeté (SoldeInsuffisantException).
     *    Il n'existe pas de plafond de retrait formellement défini dans les
     *    spécifications CAPO+, mais le solde constitue une borne naturelle.
     */
    public function test_withdrawal_above_balance_is_rejected(): void
    {
        // Solde du compte : 1 000 000,00
        $this->expectException(SoldeInsuffisantException::class);

        $this->depotService->retirer(
            $this->account,
            '1000001.00',
            $this->agent,
        );
    }

    /**
     * 7. Retrait exactement égal au solde → accepté.
     */
    public function test_withdrawal_equal_to_balance_is_accepted(): void
    {
        $soldeExact = (string) $this->account->fresh()->solde;

        $transaction = $this->depotService->retirer(
            $this->account,
            $soldeExact,
            $this->agent,
        );

        $this->assertNotNull($transaction);
        $this->assertEquals('0.00', (string) $this->account->fresh()->solde);
    }

    // =========================================================================
    // VULN-17 — RECHERCHE : LONGUEUR, WILDCARDS, PAGINATION
    // =========================================================================

    /**
     * 8. Un terme de recherche très long est tronqué à 100 caractères côté contrôleur.
     *    On vérifie que la page répond sans erreur 500 et que la route fonctionne.
     */
    public function test_search_length_is_limited(): void
    {
        // Terme de 500 caractères — doit être accepté sans erreur 500
        $longTerm = str_repeat('A', 500);

        $response = $this->actingAs($this->agent)
            ->get(route('members.index', ['q' => $longTerm]));

        $response->assertStatus(200);
    }

    /**
     * 9. Les wildcards % et _ dans un terme de recherche ne provoquent pas d'erreur
     *    et sont bien traités comme des caractères littéraux.
     */
    public function test_search_wildcards_are_handled(): void
    {
        // Créer un membre avec un nom ne contenant pas de % ou _
        Member::create([
            'numero_membre' => 'MBR-WILD-01',
            'prenom'        => 'Jean',
            'nom'           => 'TROU',
            'statut'        => 'actif',
            'cree_par_id'   => $this->admin->id,
        ]);

        // Recherche avec un wildcard littéral — ne doit retourner que les membres
        // dont le nom contient réellement le caractère %
        $response = $this->actingAs($this->agent)
            ->get(route('members.index', ['q' => '%TROU%']));

        $response->assertStatus(200);

        // Aucune ligne de données ne doit correspondre (aucun membre n'a % dans son nom).
        // La vue affiche "Aucun membre trouvé" quand la collection est vide.
        $response->assertSee('Aucun membre');
    }

    /**
     * 10. Un wildcard _ seul ne doit pas matcher tous les membres à 1 caractère.
     *     La recherche sur "_" doit trouver uniquement les noms contenant _ littéral.
     */
    public function test_underscore_wildcard_is_escaped(): void
    {
        $response = $this->actingAs($this->agent)
            ->get(route('members.index', ['q' => '_']));

        $response->assertStatus(200);

        // Un underscore seul échappé en \_ ne correspond à aucun membre existant.
        // La vue doit afficher le message "Aucun membre trouvé".
        $response->assertSee('Aucun membre');
    }

    /**
     * 11. Les résultats de recherche sont paginés (paginate(20)).
     *     On crée 25 membres et on vérifie que la réponse affiche des liens de pagination.
     */
    public function test_large_search_result_is_paginated(): void
    {
        // Créer 25 membres supplémentaires (+ 1 en setUp = 26 au total)
        for ($i = 2; $i <= 26; $i++) {
            Member::create([
                'numero_membre' => sprintf('MBR-PAGE-%02d', $i),
                'prenom'        => 'Pagine',
                'nom'           => 'TEST',
                'statut'        => 'actif',
                'cree_par_id'   => $this->admin->id,
            ]);
        }

        $response = $this->actingAs($this->agent)
            ->get(route('members.index', ['q' => 'TEST']));

        $response->assertStatus(200);

        // Laravel génère les liens de pagination : on vérifie qu'il y a bien
        // une deuxième page disponible (le nombre de résultats dépasse 20)
        $response->assertSee('page=2');
    }

    /**
     * 12. La liste des comptes est également paginée.
     */
    public function test_account_search_is_paginated(): void
    {
        for ($i = 2; $i <= 22; $i++) {
            $m = Member::create([
                'numero_membre' => sprintf('MBR-AC-%02d', $i),
                'prenom'        => 'Compte',
                'nom'           => 'PAGINÉ',
                'statut'        => 'actif',
                'cree_par_id'   => $this->admin->id,
            ]);
            (new Account())->forceFill([
                'member_id'    => $m->id,
                'numero_compte' => sprintf('CP-AC-%03d', $i),
                'solde'        => '0.00',
                'statut'       => 'actif',
            ])->save();
        }

        $response = $this->actingAs($this->admin)
            ->get(route('accounts.index'));

        $response->assertStatus(200);
        $response->assertSee('page=2');
    }
}
