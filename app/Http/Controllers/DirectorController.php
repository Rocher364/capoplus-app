<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DirectorController extends Controller
{
    public function __construct(protected ReportService $reportService)
    {
    }

    /** Rapport du jour : absolument tout ce qui s'est passe aujourd'hui, dans l'ordre. */
    public function dashboard()
    {
        $aujourdhui = now()->toDateString();

        $activites = AuditLog::with('user')
            ->whereDate('created_at', $aujourdhui)
            ->latest('created_at')
            ->get();

        $totalActions = $activites->count();
        $parAction = $activites->groupBy('action')->map->count()->sortDesc();
        $parAgent = $activites->groupBy(fn ($a) => $a->user->name ?? 'Systeme')->map->count()->sortDesc();

        return view('director.dashboard', compact('activites', 'totalActions', 'parAction', 'parAgent'));
    }

    /**
     * Rapports periodiques (semaine/mois/annee/personnalise) : exactement les
     * memes chiffres que ceux vus par l'Admin, generes par le meme ReportService.
     */
    public function rapports(Request $request)
    {
        $periode = $request->get('periode', 'semaine');

        if ($periode === 'personnalise' && $request->filled('debut') && $request->filled('fin')) {
            $debut = Carbon::parse($request->get('debut'))->startOfDay();
            $fin = Carbon::parse($request->get('fin'))->endOfDay();
            $rapport = $this->reportService->rapportPersonnalise($debut, $fin);
        } else {
            $periode = in_array($periode, ['semaine', 'mois', 'annee']) ? $periode : 'semaine';
            $rapport = $this->reportService->rapport($periode);
        }

        return view('director.rapports', compact('rapport', 'periode'));
    }

    /**
     * Historique complet, en lecture seule (aucune suppression possible, meme
     * pour le Directeur : la piste d'audit doit rester permanente et fiable).
     * Filtrable par date et par une recherche libre (action, agent, entite concernee).
     */
    public function historique(Request $request)
    {
        $query = AuditLog::with('user');

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date('date'));
        }

        if ($request->filled('q')) {
            $terme = $request->string('q');
            $query->where(function ($w) use ($terme) {
                $w->where('action', 'like', "%{$terme}%")
                    ->orWhere('auditable_type', 'like', "%{$terme}%")
                    ->orWhereHas('user', function ($u) use ($terme) {
                        $u->where('name', 'like', "%{$terme}%");
                    });
            });
        }

        $activites = $query->latest('created_at')->paginate(40)->withQueryString();

        return view('director.historique', compact('activites'));
    }

    /** Page de parametres personnels du Directeur : lui seul y accede, lui seul peut la modifier. */
    public function parametres()
    {
        return view('director.parametres');
    }

    /** Changement de mot de passe : le Directeur doit connaitre son mot de passe actuel. */
    public function changerMotDePasse(Request $request)
    {
        $data = $request->validate([
            'mot_de_passe_actuel' => ['required', 'string'],
            'nouveau_mot_de_passe' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $request->user();

        if (! Hash::check($data['mot_de_passe_actuel'], $user->password)) {
            return back()->withErrors(['mot_de_passe_actuel' => 'Le mot de passe actuel est incorrect.']);
        }

        $user->update(['password' => Hash::make($data['nouveau_mot_de_passe'])]);

        return back()->with('success', 'Mot de passe mis a jour avec succes.');
    }

    public function purgerDonnees(Request $request)
    {
        $data = $request->validate([
            'mot_de_passe_actuel' => ['required', 'string'],
            'confirmation' => ['required', 'in:VIDER LES DONNEES'],
        ], [
            'confirmation.in' => 'La phrase de confirmation est incorrecte.',
        ]);

        if (! Hash::check($data['mot_de_passe_actuel'], $request->user()->password)) {
            return back()->withErrors(['mot_de_passe_actuel' => 'Le mot de passe actuel est incorrect.']);
        }

        $supprimes = DB::transaction(function (): array {
            $tables = [
                'repayments',
                'loan_schedules',
                'loans',
                'transactions',
                'accounts',
                'members',
                'audit_logs',
            ];
            $supprimes = [];

            foreach ($tables as $table) {
                $supprimes[$table] = DB::table($table)->count();
                DB::table($table)->delete();
            }

            return $supprimes;
        });

        return back()->with('success', 'Toutes les donnees des membres, leurs donnees financieres et leur historique ont ete supprimees.');
    }

    /**
     * Liste des comptes Admin/Agent, geree par le Directeur uniquement.
     * Le Directeur peut reinitialiser leur mot de passe (autorite superieure) ;
     * l'inverse n'existe nulle part dans l'application.
     */
    public function utilisateurs()
    {
        $utilisateurs = User::where('role', '!=', 'auditeur')->orderBy('name')->get();

        return view('director.utilisateurs', compact('utilisateurs'));
    }

    public function reinitialiserMotDePasse(Request $request, User $utilisateur)
    {
        if ($utilisateur->isAuditeur()) {
            abort(403, "Impossible de modifier un compte Directeur depuis cette page.");
        }

        $data = $request->validate([
            'nouveau_mot_de_passe' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $utilisateur->update(['password' => Hash::make($data['nouveau_mot_de_passe'])]);

        ActivityLogger::log($request->user(), 'utilisateur.motdepasse_reinitialise', $utilisateur, [
            'utilisateur' => $utilisateur->name,
            'email' => $utilisateur->email,
        ]);

        return back()->with('success', "Mot de passe de {$utilisateur->name} reinitialise.");
    }

}
