<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Member;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AccountController extends Controller
{
    public function index(Request $request)
    {
        $query = Account::with('member');

        if ($request->filled('q')) {
            $terme = mb_substr((string) $request->string('q'), 0, 100);
            $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $terme);

            $query->where('numero_compte', 'like', "%{$escaped}%")
                ->orWhereHas('member', function ($w) use ($escaped) {
                    $w->where('nom', 'like', "%{$escaped}%")
                        ->orWhere('prenom', 'like', "%{$escaped}%");
                });
        }

        $accounts = $query->latest()->paginate(20)->withQueryString();

        return view('accounts.index', compact('accounts'));
    }

    public function show(Account $account)
    {
        $account->load([
            'member',
            'transactions' => fn ($q) => $q->latest()->limit(50),
        ]);

        return view('accounts.show', compact('account'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'member_id' => ['required', 'exists:members,id'],
        ]);

        $member = Member::findOrFail($data['member_id']);

        if ($member->account()->exists()) {
            return back()->withErrors(['member_id' => 'Ce membre possede deja un compte.']);
        }

        $account = Account::create([
            'member_id' => $member->id,
            'numero_compte' => $this->genererNumeroCompte(),
            'solde' => 0,
            'statut' => 'actif',
        ]);

        ActivityLogger::log($request->user(), 'compte.cree', $account, [
            'numero_compte' => $account->numero_compte,
            'membre' => "{$member->prenom} {$member->nom}",
        ]);

        return redirect()
            ->route('accounts.show', $account)
            ->with('success', "Compte {$account->numero_compte} cree pour {$member->prenom} {$member->nom}.");
    }

    public function bloquer(Request $request, Account $account)
    {
        $this->authorize('bloquer', $account);

        $data = $request->validate([
            'raison_blocage' => ['required', 'string', 'max:500'],
        ]);

        $anciennes = [
            'statut' => $account->statut,
            'raison_blocage' => $account->raison_blocage,
        ];

        $account->bloquer($data['raison_blocage'], $request->user());

        ActivityLogger::log($request->user(), 'compte.bloque', $account, [
            'numero_compte' => $account->numero_compte,
            'statut' => 'bloque',
            'raison' => $data['raison_blocage'],
        ], $anciennes);

        return back()->with('success', "Compte {$account->numero_compte} bloque.");
    }

    public function debloquer(Request $request, Account $account)
    {
        $this->authorize('debloquer', $account);

        $anciennes = [
            'statut' => $account->statut,
            'raison_blocage' => $account->raison_blocage,
        ];

        $account->debloquer();

        ActivityLogger::log($request->user(), 'compte.debloque', $account, [
            'numero_compte' => $account->numero_compte,
            'statut' => 'actif',
        ], $anciennes);

        return back()->with('success', "Compte {$account->numero_compte} debloque.");
    }

    protected function genererNumeroCompte(): string
    {
        do {
            $numero = sprintf('CP-%s-%s', now()->format('Y'), strtoupper(Str::random(6)));
        } while (Account::where('numero_compte', $numero)->exists());

        return $numero;
    }
}