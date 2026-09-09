<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Loan;
use App\Models\Member;
use App\Models\Repayment;
use App\Models\Transaction;

class DashboardController extends Controller
{
    /** Bilan journalier operationnel. Le Directeur est redirige vers son propre rapport. */
    public function index()
    {
        if (auth()->user()->isAuditeur()) {
            return redirect()->route('director.dashboard');
        }

        $aujourdhui = now()->toDateString();

        $depots = Transaction::where('type', 'depot')->whereDate('effectuee_le', $aujourdhui);
        $totalDepots = (clone $depots)->sum('montant');
        $nbDepots = (clone $depots)->count();

        $retraits = Transaction::where('type', 'retrait')->whereDate('effectuee_le', $aujourdhui);
        $totalRetraits = (clone $retraits)->sum('montant');
        $nbRetraits = (clone $retraits)->count();

        $remboursements = Repayment::whereDate('effectue_le', $aujourdhui);
        $totalRemboursements = (clone $remboursements)->sum('montant');
        $nbRemboursements = (clone $remboursements)->count();

        $nouveauxMembres = Member::whereDate('created_at', $aujourdhui)->count();
        $nouveauxComptes = Account::whereDate('created_at', $aujourdhui)->count();

        $pretsDecaissesAujourdhui = Loan::whereDate('date_decaissement', $aujourdhui);
        $nbPretsDecaisses = (clone $pretsDecaissesAujourdhui)->count();
        $montantPretsDecaisses = (clone $pretsDecaissesAujourdhui)->sum('montant_approuve');

        $transactionsDuJour = Transaction::with(['account.member', 'user'])
            ->whereDate('effectuee_le', $aujourdhui)
            ->latest('effectuee_le')
            ->get();

        $soldeTotalReseau = Account::sum('solde');
        $nbComptesActifs = Account::where('statut', 'actif')->count();
        $nbComptesBloques = Account::where('statut', 'bloque')->count();
        $pretsEnCours = Loan::whereIn('statut', ['decaisse', 'en_retard'])->count();

        return view('dashboard', [
            'totalDepots' => $totalDepots,
            'nbDepots' => $nbDepots,
            'totalRetraits' => $totalRetraits,
            'nbRetraits' => $nbRetraits,
            'totalRemboursements' => $totalRemboursements,
            'nbRemboursements' => $nbRemboursements,
            'nouveauxMembres' => $nouveauxMembres,
            'nouveauxComptes' => $nouveauxComptes,
            'nbPretsDecaisses' => $nbPretsDecaisses,
            'montantPretsDecaisses' => $montantPretsDecaisses,
            'transactionsDuJour' => $transactionsDuJour,
            'soldeTotalReseau' => $soldeTotalReseau,
            'nbComptesActifs' => $nbComptesActifs,
            'nbComptesBloques' => $nbComptesBloques,
            'pretsEnCours' => $pretsEnCours,
        ]);
    }
}