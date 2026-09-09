<?php

namespace App\Http\Controllers;

use App\Exceptions\CompteBloqueException;
use App\Exceptions\SoldeInsuffisantException;
use App\Http\Requests\StoreTransactionRequest;
use App\Models\Account;
use App\Services\DepotRetraitService;
use InvalidArgumentException;

class TransactionController extends Controller
{
    public function __construct(protected DepotRetraitService $depotRetraitService)
    {
    }

    /** Historique des transactions d'un compte. */
    public function index(Account $account)
    {
        $transactions = $account->transactions()->latest('effectuee_le')->paginate(30);

        return view('accounts.transactions', compact('account', 'transactions'));
    }

    /** Enregistre un depot sur le compte. */
    public function deposer(StoreTransactionRequest $request, Account $account)
    {
        try {
            $transaction = $this->depotRetraitService->deposer(
                $account,
                (float) $request->validated('montant'),
                $request->user(),
                $request->only('moyen', 'description')
            );
        } catch (CompteBloqueException|InvalidArgumentException $e) {
            return back()->withErrors(['montant' => $e->getMessage()]);
        }

        return redirect()
            ->route('accounts.show', $account)
            ->with('success', "Depot de {$transaction->montant} enregistre (ref: {$transaction->reference}).");
    }

    /** Enregistre un retrait sur le compte. */
    public function retirer(StoreTransactionRequest $request, Account $account)
    {
        try {
            $transaction = $this->depotRetraitService->retirer(
                $account,
                (float) $request->validated('montant'),
                $request->user(),
                $request->only('moyen', 'description')
            );
        } catch (CompteBloqueException|SoldeInsuffisantException|InvalidArgumentException $e) {
            return back()->withErrors(['montant' => $e->getMessage()]);
        }

        return redirect()
            ->route('accounts.show', $account)
            ->with('success', "Retrait de {$transaction->montant} enregistre (ref: {$transaction->reference}).");
    }
}
