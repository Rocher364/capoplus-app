<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class DirectorController extends Controller
{
    public function __construct(protected ReportService $reportService)
    {
    }

    /** Rapport du jour et supervision globale pour le Directeur. */
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

        // Donnees membres et activites financieres pour le Directeur
        $membres = \App\Models\Member::with(['account.transactions', 'loans'])
            ->latest()
            ->get();

        $transactionsDuJour = \App\Models\Transaction::with(['account.member', 'user'])
            ->whereDate('effectuee_le', $aujourdhui)
            ->latest('effectuee_le')
            ->get();

        $kpis = [
            'totalMembres' => \App\Models\Member::count(),
            'totalSolde' => (float) \App\Models\Account::sum('solde'),
            'depotsJour' => (float) \App\Models\Transaction::whereDate('effectuee_le', $aujourdhui)->where('type', 'depot')->sum('montant'),
            'retraitsJour' => (float) \App\Models\Transaction::whereDate('effectuee_le', $aujourdhui)->where('type', 'retrait')->sum('montant'),
            'totalPretsActifs' => \App\Models\Loan::whereIn('statut', [\App\Enums\LoanStatus::Decaisse, \App\Enums\LoanStatus::EnRetard])->count(),
        ];

        return view('director.dashboard', compact(
            'activites',
            'totalActions',
            'parAction',
            'parAgent',
            'membres',
            'transactionsDuJour',
            'kpis'
        ));
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
        $query = AuditLog::with(['user', 'auditable']);
        $historiqueMasqueAvant = DB::table('director_audit_visibility')
            ->where('id', 1)
            ->value('hidden_before');

        if ($historiqueMasqueAvant) {
            $query->where('created_at', '>', $historiqueMasqueAvant);
        }

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date('date'));
        }

        if ($request->filled('q')) {
            $terme = mb_substr((string) $request->string('q'), 0, 100);
            $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $terme);

            $query->where(function ($w) use ($escaped) {
                $w->where('action', 'like', "%{$escaped}%")
                    ->orWhere('auditable_type', 'like', "%{$escaped}%")
                    ->orWhereHas('user', function ($u) use ($escaped) {
                        $u->where('name', 'like', "%{$escaped}%");
                    });
            });
        }

        $activites = $query->latest('created_at')->paginate(40)->withQueryString();

        return view('director.historique', compact('activites'));
    }

    /** Masque l'historique visible apres re-authentification et sauvegarde. */
    public function effacerHistorique(Request $request, \App\Services\BackupService $backupService)
    {
        $data = $request->validate([
            'mot_de_passe_actuel' => ['required', 'string'],
            'confirmation' => ['required', 'string', 'in:EFFACER TOUT L\'HISTORIQUE'],
        ]);

        $directeur = $request->user();

        if (! Hash::check($data['mot_de_passe_actuel'], $directeur->password)) {
            return back()->withErrors([
                'mot_de_passe_actuel' => 'Le mot de passe actuel est incorrect. Aucune donnee n\'a ete masquee.',
            ]);
        }

        $backupFilename = $backupService->createBackup('Sauvegarde automatique avant effacement de l affichage historique', $directeur);
        $dateEffacement = now();

        ActivityLogger::log($directeur, 'historique.affichage_efface', null, [
            'sauvegarde' => $backupFilename,
            'historique_conserve_en_base' => true,
            'affichage_masque_avant' => $dateEffacement->toIso8601String(),
        ]);

        DB::table('director_audit_visibility')->updateOrInsert(
            ['id' => 1],
            [
                'hidden_before' => $dateEffacement,
                'cleared_by' => $directeur->id,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        return back()->with('success', 'L historique a ete retire de l affichage. Une sauvegarde securisee a ete conservee.');
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

        ActivityLogger::log($user, 'auth.motdepasse_modifie', $user, [
            'email' => $user->email,
            'statut' => 'modifie',
        ]);

        return back()->with('success', 'Mot de passe mis a jour avec succes.');
    }

    /** Purge les donnees metier apres une re-authentification du Directeur. */
    public function purgerDonnees(Request $request, \App\Services\BackupService $backupService)
    {
        $data = $request->validate([
            'mot_de_passe_actuel' => ['required', 'string'],
            'confirmation' => ['required', 'string', 'in:EFFACER LES DONNEES'],
        ]);

        $directeur = $request->user();

        if (! Hash::check($data['mot_de_passe_actuel'], $directeur->password)) {
            return back()->withErrors([
                'mot_de_passe_actuel' => 'Le mot de passe actuel est incorrect. Aucune donnee n\'a ete effacee.',
            ]);
        }

        $backupFilename = $backupService->createBackup('Sauvegarde automatique avant purge des donnees', $directeur);
        $counts = [];

        DB::transaction(function () use (&$counts) {
            foreach (['repayments', 'loan_schedules', 'loans', 'transactions', 'accounts', 'members'] as $table) {
                $counts[$table] = DB::table($table)->count();
                DB::table($table)->delete();
            }
        });

        ActivityLogger::log($directeur, 'donnees.purgees', null, [
            'sauvegarde_avant_purge' => $backupFilename,
            'elements_effaces' => $counts,
            'audit_logs_conserves' => true,
            'utilisateurs_conserves' => true,
        ]);

        return back()->with('success', 'Les donnees metier ont ete effacees apres sauvegarde. La piste d audit et les utilisateurs ont ete conserves.');
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

    /**
     * Interface de gestion des sauvegardes et de la planification.
     */
    public function sauvegardes(\App\Services\BackupService $backupService)
    {
        $backups = $backupService->listBackups();
        $schedule = $backupService->getScheduleSettings();

        return view('director.sauvegardes', compact('backups', 'schedule'));
    }

    /**
     * Déclenche une sauvegarde manuelle instantanée.
     */
    public function creerSauvegarde(Request $request, \App\Services\BackupService $backupService)
    {
        $description = $request->input('description', 'Sauvegarde manuelle');
        $filename = $backupService->createBackup($description, $request->user());

        return back()->with('success', "Sauvegarde [{$filename}] générée avec succès !");
    }

    /**
     * Télécharge un fichier de sauvegarde.
     */
    public function telechargerSauvegarde(string $filename, \App\Services\BackupService $backupService)
    {
        return $backupService->downloadBackup($filename, auth()->user());
    }

    /**
     * Importe un fichier de sauvegarde externe.
     */
    public function importerSauvegarde(Request $request, \App\Services\BackupService $backupService)
    {
        $request->validate([
            'fichier_sauvegarde' => ['required', 'file', 'max:51200'], // 50MB max
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $filename = $backupService->importUploadedBackup(
                $request->file('fichier_sauvegarde'),
                $request->input('description'),
                $request->user()
            );

            return back()->with('success', "Sauvegarde importée avec succès sous le nom [{$filename}]. Vous pouvez la télécharger ou la conserver comme archive sécurisée.");
        } catch (\Throwable $e) {
            return back()->withErrors(['backup' => "Fichier de sauvegarde invalide : " . $e->getMessage()]);
        }
    }

    /**
     * Supprime un fichier de sauvegarde.
     */
    public function supprimerSauvegarde(string $filename, \App\Services\BackupService $backupService)
    {
        $backupService->deleteBackup($filename, auth()->user());

        return back()->with('success', "Sauvegarde supprimée avec succès.");
    }

    /**
     * Enregistre les paramètres de planification automatique.
     */
    public function sauvegarderPlanification(Request $request, \App\Services\BackupService $backupService)
    {
        $data = $request->validate([
            'enabled' => ['nullable'],
            'frequency' => ['required', 'in:daily,weekly,monthly'],
            'time' => ['required', 'date_format:H:i'],
            'day_of_week' => ['nullable', 'integer', 'between:1,7'],
            'day_of_month' => ['nullable', 'integer', 'between:1,28'],
            'keep_last' => ['required', 'integer', 'between:1,100'],
        ]);

        $data['enabled'] = $request->has('enabled');
        $backupService->saveScheduleSettings($data, $request->user());

        return back()->with('success', 'Planification des sauvegardes automatiques mise à jour avec succès.');
    }
}
