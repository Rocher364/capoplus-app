<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Account;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MemberController extends Controller
{
    public function index(Request $request)
    {
        $query = Member::with('account');

        if ($request->filled('q')) {
            $terme = $request->string('q');
            $query->where(function ($w) use ($terme) {
                $w->where('nom', 'like', "%{$terme}%")
                    ->orWhere('prenom', 'like', "%{$terme}%")
                    ->orWhere('numero_membre', 'like', "%{$terme}%")
                    ->orWhere('nif_cin', 'like', "%{$terme}%")
                    ->orWhere('telephone', 'like', "%{$terme}%");
            });
        }

        $members = $query->latest()->paginate(20)->withQueryString();

        return view('members.index', compact('members'));
    }

    public function create()
    {
        return view('members.create');
    }

    public function store(Request $request)
    {
        $data = $this->validerDonnees($request);

        $member = DB::transaction(function () use ($request, $data) {
            // 1. Kreye membre la
            $membreCree = Member::create($data + [
                'numero_membre' => $this->genererNumeroMembre(),
                'statut'        => 'actif',
                'cree_par_id'   => $request->user()->id,
            ]);

            // 2. Kreye kont finansyè (Account) pou membre la otomatikman
            Account::create([
                'member_id'      => $membreCree->id,
                'numero_compte'  => $this->genererNumeroCompte(),
                'solde'          => 0.00,
                'statut'         => 'actif',
            ]);

            return $membreCree;
        });

        ActivityLogger::log($request->user(), 'membre.cree', $member, [
            'numero_membre' => $member->numero_membre,
            'nom'           => "{$member->prenom} {$member->nom}",
        ]);

        return redirect()
            ->route('members.index')
            ->with('success', "Membre {$member->prenom} {$member->nom} créé avec succès (N° {$member->numero_membre}).");
    }

    public function edit(Member $member)
    {
        return view('members.edit', compact('member'));
    }

    public function update(Request $request, Member $member)
    {
        $data = $this->validerDonnees($request, $member->id);

        $member->update($data);

        ActivityLogger::log($request->user(), 'membre.modifie', $member, [
            'numero_membre' => $member->numero_membre,
            'nom'           => "{$member->prenom} {$member->nom}",
        ]);

        return redirect()
            ->route('members.index')
            ->with('success', "Membre {$member->prenom} {$member->nom} mis à jour.");
    }

    /** Suppression protégée : impossible si le compte du membre possède déjà des transactions ou du solde */
    public function destroy(Request $request, Member $member)
    {
        $account = $member->account;

        if ($account && ($account->solde > 0 || $account->transactions()->exists() || $account->loans()->exists())) {
            return back()->with('error', "Impossible de supprimer {$member->prenom} {$member->nom} : ce membre possède un historique financier ou un solde non nul.");
        }

        $nom = "{$member->prenom} {$member->nom}";
        $numero = $member->numero_membre;

        ActivityLogger::log($request->user(), 'membre.supprime', $member, [
            'numero_membre' => $numero,
            'nom'           => $nom,
        ]);

        $member->delete();

        return back()->with('success', "Membre {$nom} supprimé.");
    }

    protected function validerDonnees(Request $request, ?int $memberId = null): array
    {
        return $request->validate([
            'prenom'         => ['required', 'string', 'max:255'],
            'nom'            => ['required', 'string', 'max:255'],
            'nif_cin'        => ['nullable', 'string', 'max:50', Rule::unique('members', 'nif_cin')->ignore($memberId)],
            'telephone'      => ['nullable', 'string', 'max:50', Rule::unique('members', 'telephone')->ignore($memberId)],
            'email'          => ['nullable', 'email', 'max:255', Rule::unique('members', 'email')->ignore($memberId)],
            'adresse'        => ['nullable', 'string'],
            'date_naissance' => ['nullable', 'date'],
            'sexe'           => ['nullable', 'in:M,F,Autre'],
        ]);
    }

    protected function genererNumeroMembre(): string
    {
        do {
            $numero = 'MBR-' . strtoupper(Str::random(6));
        } while (Member::where('numero_membre', $numero)->exists());

        return $numero;
    }

    protected function genererNumeroCompte(): string
    {
        do {
            $numero = 'CP-' . date('Y') . '-' . strtoupper(Str::random(6));
        } while (Account::where('numero_compte', $numero)->exists());

        return $numero;
    }
}