<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Loan;
use App\Models\LoanSchedule;
use App\Models\Member;
use App\Services\ActivityLogger;
use App\Services\DepotRetraitService;
use App\Services\LoanService;
use Illuminate\Http\Request;
use InvalidArgumentException;

class LoanController extends Controller
{
    public function __construct(protected LoanService $loanService)
    {
    }

    public function index()
    {
        $loans = Loan::with('member')->latest('date_demande')->paginate(20);

        return view('loans.index', compact('loans'));
    }

    public function create()
    {
        $membres = Member::whereHas('account', fn ($q) => $q->where('statut', 'actif'))
            ->with('account')
            ->orderBy('nom')
            ->get();

        return view('loans.create', compact('membres'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'member_id' => ['required', 'exists:members,id'],
            'montant_demande' => ['required', 'numeric', 'min:1'],
            'duree_mois' => ['required', 'integer', 'min:1', 'max:120'],
            'taux_interet' => ['required', 'numeric', 'min:0', 'max:100'],
            'type_taux' => ['required', 'in:fixe,degressif,palier'],
            'methode_calcul' => ['required', 'in:simple,degressif,constant'],
            'motif' => ['required', 'string', 'max:1000'],
        ]);

        $member = Member::findOrFail($data['member_id']);
        $account = $member->account;

        if (! $account) {
            return back()->withErrors(['member_id' => 'Ce membre ne possede pas de compte actif.']);
        }

        $loan = $this->loanService->demander($member, $account, $data, $request->user());

        ActivityLogger::log($request->user(), 'pret.demande', $loan, [
            'numero_pret' => $loan->numero_pret,
            'montant' => $loan->montant_demande,
            'membre' => "{$member->prenom} {$member->nom}",
        ]);

        return redirect()
            ->route('loans.show', $loan)
            ->with('success', "Demande de pret {$loan->numero_pret} enregistree.");
    }

    public function show(Loan $loan)
    {
        $loan->load(['member', 'account', 'schedules.repayments', 'demandePar', 'approuvePar']);

        return view('loans.show', compact('loan'));
    }

    public function approuver(Request $request, Loan $loan)
    {
        $data = $request->validate([
            'montant_approuve' => ['nullable', 'numeric', 'min:1'],
        ]);

        $this->loanService->approuver($loan, $request->user(), $data['montant_approuve'] ?? null);

        ActivityLogger::log($request->user(), 'pret.approuve', $loan, [
            'numero_pret' => $loan->numero_pret,
            'montant_approuve' => $data['montant_approuve'] ?? $loan->montant_demande,
        ]);

        return back()->with('success', "Pret {$loan->numero_pret} approuve.");
    }

    public function rejeter(Request $request, Loan $loan)
    {
        $data = $request->validate([
            'justification_decision' => ['required', 'string', 'max:1000'],
        ]);

        $this->loanService->rejeter($loan, $request->user(), $data['justification_decision']);

        ActivityLogger::log($request->user(), 'pret.rejete', $loan, [
            'numero_pret' => $loan->numero_pret,
            'justification' => $data['justification_decision'],
        ]);

        return back()->with('success', "Pret {$loan->numero_pret} rejete.");
    }

    public function decaisser(Request $request, Loan $loan, DepotRetraitService $depotService)
    {
        $this->loanService->decaisser($loan, $request->user(), $depotService);

        ActivityLogger::log($request->user(), 'pret.decaisse', $loan, [
            'numero_pret' => $loan->numero_pret,
            'montant' => $loan->montant_approuve,
        ]);

        return back()->with('success', "Pret {$loan->numero_pret} decaisse, echeancier genere.");
    }

    public function rembourser(Request $request, Loan $loan, LoanSchedule $echeance)
    {
        $data = $request->validate([
            'montant' => ['required', 'numeric', 'decimal:2', 'min:0.01'],
            'moyen' => ['nullable', 'in:especes,cheque,virement,autre'],
        ]);

        if ($echeance->loan_id !== $loan->id) {
            abort(404);
        }

        try {
            $repayment = $this->loanService->enregistrerRemboursement($echeance, (float) $data['montant'], $request->user(), $data);
        } catch (InvalidArgumentException|\App\Exceptions\CompteBloqueException|\App\Exceptions\SoldeInsuffisantException $e) {
            return back()->withErrors(['montant' => $e->getMessage()]);
        }

        ActivityLogger::log($request->user(), 'pret.remboursement', $loan, [
            'numero_pret' => $loan->numero_pret,
            'echeance' => $echeance->numero_echeance,
            'montant' => $data['montant'],
            'reference' => $repayment->reference,
        ]);

        return back()->with('success', "Remboursement de {$data['montant']} enregistre.");
    }
}
