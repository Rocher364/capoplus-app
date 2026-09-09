<?php

namespace Tests\Feature;

use App\Exceptions\SoldeInsuffisantException;
use App\Models\Account;
use App\Models\Member;
use App\Models\User;
use App\Services\DepotRetraitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_deposit_updates_balance_and_records_transaction(): void
    {
        $user = User::factory()->create();
        $account = Account::create([
            'member_id' => Member::create([
                'numero_membre' => 'MBR-TEST01',
                'prenom' => 'Jean',
                'nom' => 'TEST',
            ])->id,
            'numero_compte' => 'CP-TEST-01',
            'solde' => 0,
            'statut' => 'actif',
        ]);

        $transaction = app(DepotRetraitService::class)->deposer($account, 125.50, $user);

        $this->assertSame('125.50', (string) $account->fresh()->solde);
        $this->assertSame('depot', $transaction->type);
        $this->assertSame('125.50', (string) $transaction->solde_apres);
    }

    public function test_withdrawal_cannot_exceed_balance(): void
    {
        $user = User::factory()->create();
        $member = Member::create([
            'numero_membre' => 'MBR-TEST02',
            'prenom' => 'Marie',
            'nom' => 'TEST',
        ]);
        $account = Account::create([
            'member_id' => $member->id,
            'numero_compte' => 'CP-TEST-02',
            'solde' => 50,
            'statut' => 'actif',
        ]);

            $this->expectException(\App\Exceptions\SoldeInsuffisantException::class);

        app(DepotRetraitService::class)->retirer($account, 50.01, $user);
    }
}