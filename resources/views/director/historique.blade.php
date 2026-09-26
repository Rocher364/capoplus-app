@extends('layouts.director')
@section('title', 'Historique')
@section('content')

<div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
    <div>
        <p class="text-xs font-bold uppercase tracking-[0.2em] text-orange-600">Controle & transparence</p>
        <h2 class="mt-1 text-2xl font-extrabold tracking-tight text-slate-900">Historique des activites</h2>
        <p class="mt-1 text-sm font-medium text-slate-600">Suivez chaque action sensible enregistree dans CASH.</p>
    </div>
</div>

<details class="group mb-6">
    <summary class="inline-flex list-none cursor-pointer rounded-xl bg-amber-400 px-4 py-2.5 text-xs font-bold text-slate-950 shadow-sm transition hover:bg-amber-500">Effacer l'affichage</summary>
    <div class="mt-3 rounded-2xl border border-slate-300 bg-white p-5 shadow-md">
        <p class="text-sm font-bold text-slate-900">Effacer tout l'historique visible</p>
        <p class="mt-1 text-xs leading-5 text-slate-500">Une sauvegarde est creee avant l'operation. Les donnees restent conservees pour l'audit et la restauration.</p>
        <form action="{{ route('director.historique.effacer') }}" method="POST" class="mt-4 grid gap-3 md:grid-cols-[1fr_1fr_auto] md:items-end" onsubmit="return confirm('Cette action retirera tout l historique de l affichage. Continuer ?');">
            @csrf
            <label class="text-xs font-bold uppercase tracking-wider text-slate-700">
                Mot de passe actuel
                <input type="password" name="mot_de_passe_actuel" required autocomplete="current-password" placeholder="Mot de passe actuel" class="mt-1.5 w-full rounded-lg border border-slate-600 bg-slate-100 px-3 py-2 text-sm font-normal normal-case tracking-normal text-slate-950 placeholder-slate-600 outline-none focus:border-slate-900 focus:bg-white focus:ring-2 focus:ring-slate-500/20">
            </label>
            <label class="text-xs font-bold uppercase tracking-wider text-slate-700">
                Confirmation
                <input type="text" name="confirmation" required autocomplete="off" placeholder="EFFACER TOUT L'HISTORIQUE" class="mt-1.5 w-full rounded-lg border border-slate-600 bg-emerald-50 px-3 py-2 font-mono text-xs normal-case tracking-normal text-slate-950 placeholder-slate-600 outline-none focus:border-slate-900 focus:bg-white focus:ring-2 focus:ring-slate-500/20">
            </label>
            <button type="submit" class="rounded-lg bg-amber-400 px-4 py-2.5 text-xs font-bold text-slate-950 transition hover:bg-amber-500">Confirmer l'effacement</button>
        </form>
    </div>
</details>

<form method="GET" action="{{ route('director.historique') }}" class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
    <div class="mb-3 flex items-center gap-2 text-sm font-bold text-slate-800">
        <svg class="h-4 w-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35m1.35-5.65a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        Rechercher dans le journal
    </div>
    <div class="flex flex-wrap gap-2.5">
    <input type="text" name="q" value="{{ request('q') }}" placeholder="Rechercher : action, agent, ou type (membre, compte, pret...)"
           class="flex-1 min-w-[260px] rounded-xl border border-slate-300 bg-slate-50 text-slate-900 placeholder-slate-400 text-sm px-4 py-2.5 focus:bg-white focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 transition outline-none">
    <input type="date" name="date" value="{{ request('date') }}"
           class="rounded-xl border border-slate-300 bg-slate-50 text-slate-900 text-sm px-4 py-2.5 focus:bg-white focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 transition outline-none">
    <button class="px-5 py-2.5 rounded-xl bg-slate-900 text-white text-sm font-semibold hover:bg-slate-800 shadow-sm active:scale-[0.98] transition duration-150">Rechercher</button>
    @if (request('date') || request('q'))
        <a href="{{ route('director.historique') }}" class="px-5 py-2.5 rounded-xl border border-slate-300 bg-white text-slate-700 text-sm font-semibold hover:bg-slate-50 transition">Effacer</a>
    @endif
    </div>
</form>

<div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-md">
    <div class="flex items-center justify-between border-b border-slate-200 bg-slate-900 px-5 py-4 text-white sm:px-6">
        <div>
            <h3 class="text-sm font-bold uppercase tracking-wider">Journal des actions</h3>
            <p class="mt-0.5 text-xs text-slate-300">{{ $activites->total() }} evenement(s) trouve(s)</p>
        </div>
    </div>
    <div class="w-full overflow-x-auto"><table class="w-full text-left text-sm">
        <thead class="bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-600">
            <tr>
                <th class="px-6 py-3.5">Date</th>
                <th class="px-6 py-3.5">Heure</th>
                <th class="px-6 py-3.5">Action</th>
                <th class="px-6 py-3.5">Concerne</th>
                <th class="px-6 py-3.5">Effectue par</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($activites as $a)
                @php
                    $actionLabels = [
                        'auth.login' => 'Connexion',
                        'auth.logout' => 'Deconnexion',
                        'membre.cree' => 'Membre cree',
                        'membre.modifie' => 'Membre modifie',
                        'membre.supprime' => 'Membre supprime',
                        'transaction.depot' => 'Depot enregistre',
                        'transaction.retrait' => 'Retrait enregistre',
                        'pret.approuve' => 'Pret approuve',
                        'pret.rejete' => 'Pret rejete',
                        'sauvegarde.cree' => 'Sauvegarde creee',
                        'sauvegarde.supprimee' => 'Sauvegarde supprimee',
                        'donnees.purgees' => 'Donnees metier effacees',
                        'historique.affichage_efface' => 'Affichage historique efface',
                    ];
                    $actionLabel = $actionLabels[$a->action] ?? ucfirst(str_replace(['.', '_'], [' - ', ' '], $a->action));
                    $concerne = match (class_basename($a->auditable_type ?? '')) {
                        'User' => 'Utilisateur : ' . ($a->auditable?->name ?? '#' . $a->auditable_id),
                        'Member' => 'Membre : ' . ($a->auditable?->nom_complet ?? '#' . $a->auditable_id),
                        'Loan' => 'Pret : ' . ($a->auditable?->numero_pret ?? '#' . $a->auditable_id),
                        'Transaction' => 'Transaction : ' . ($a->auditable?->reference ?? '#' . $a->auditable_id),
                        'Account' => 'Compte : ' . ($a->auditable?->numero_compte ?? '#' . $a->auditable_id),
                        default => $a->auditable_type ? class_basename($a->auditable_type) . ' #' . $a->auditable_id : 'Systeme',
                    };
                @endphp
                <tr class="hover:bg-white transition duration-150">
                    <td class="px-6 py-4 text-xs font-semibold text-slate-700">{{ $a->created_at->format('d/m/Y') }}</td>
                    <td class="px-6 py-4 font-mono text-xs font-bold text-slate-500">{{ $a->created_at->format('H:i:s') }}</td>
                    <td class="px-6 py-3.5">
                        <span class="inline-block rounded-full border border-orange-200 bg-orange-50 px-2.5 py-1 text-xs font-bold text-orange-800">
                            {{ $actionLabel }}
                        </span>
                    </td>
                    <td class="px-6 py-4 font-semibold text-slate-700">
                        {{ $concerne }}
                    </td>
                    <td class="px-6 py-4 font-bold text-slate-900">{{ $a->user->name ?? 'Systeme' }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="bg-slate-50/50 px-6 py-12 text-center font-medium text-slate-500">Aucune activite trouvee.</td></tr>
            @endforelse
        </tbody>
    </table></div>
</div>

<div class="mt-6">{{ $activites->links() }}</div>
@endsection
