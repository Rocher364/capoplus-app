@extends('layouts.director')
@section('title', 'Rapport du jour')
@section('content')

<div class="flex items-center justify-between mb-6 flex-wrap gap-4 no-print">
    <div>
        <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Rapport du jour</h2>
        <p class="text-sm font-medium text-slate-600 mt-0.5">{{ now()->translatedFormat('l d F Y') }} - <span class="font-bold text-slate-800">{{ $totalActions }}</span> action(s) enregistree(s)</p>
    </div>
    <button onclick="window.print()" type="button"
            class="flex items-center gap-2 px-5 py-2.5 rounded-xl bg-slate-900 text-white text-sm font-semibold hover:bg-slate-800 shadow-sm active:scale-[0.98] transition duration-150">
        Imprimer le rapport
    </button>
</div>

<section class="mb-6 bg-rose-50 border border-rose-300 rounded-2xl shadow-sm p-5 no-print">
    <div class="flex items-start gap-3 mb-4">
        <div class="mt-0.5 text-rose-600" aria-hidden="true">!</div>
        <div>
            <h2 class="text-sm font-bold text-rose-900 uppercase tracking-wider">Zone dangereuse</h2>
            <p class="text-sm text-rose-800 mt-1">Cette action supprime définitivement tous les membres, leurs données financières et l'historique. Les utilisateurs seront conservés.</p>
        </div>
    </div>
    <form method="POST" action="{{ route('director.purger-donnees') }}" class="grid grid-cols-1 md:grid-cols-3 gap-3" onsubmit="return confirm('Derniere confirmation : toutes les donnees des membres seront supprimees definitivement. Continuer ?');">
        @csrf
        <label class="block">
            <span class="block text-xs font-bold text-rose-900 mb-1">Mot de passe actuel</span>
            <input type="password" name="mot_de_passe_actuel" required autocomplete="current-password"
                   class="w-full rounded-lg border border-rose-300 bg-white px-3 py-2 text-sm focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20 outline-none">
        </label>
        <label class="block">
            <span class="block text-xs font-bold text-rose-900 mb-1">Ecrire: VIDER LES DONNEES</span>
            <input type="text" name="confirmation" required autocomplete="off" spellcheck="false"
                   class="w-full rounded-lg border border-rose-300 bg-white px-3 py-2 text-sm focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20 outline-none">
        </label>
        <div class="flex items-end">
            <button type="submit" class="w-full rounded-lg bg-rose-700 px-4 py-2 text-sm font-bold text-white hover:bg-rose-800 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2">
                Effacer toutes les donnees
            </button>
        </div>
    </form>
</section>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <div class="bg-white rounded-2xl border border-slate-300 shadow-md p-5 lg:col-span-1">
        <p class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-4 border-b border-slate-200 pb-2">Repartition par type d'action</p>
        @forelse ($parAction as $action => $count)
            <div class="flex items-center justify-between py-2 text-sm border-b border-slate-100 last:border-0">
                <span class="text-slate-600 font-medium">{{ str_replace('.', ' - ', $action) }}</span>
                <span class="font-bold text-slate-900 bg-slate-100 px-2.5 py-0.5 rounded-md border border-slate-200 text-xs">{{ $count }}</span>
            </div>
        @empty
            <p class="text-sm text-slate-500 font-medium py-2">Aucune activite aujourd'hui.</p>
        @endforelse
    </div>

    <div class="bg-white rounded-2xl border border-slate-300 shadow-md p-5 lg:col-span-1">
        <p class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-4 border-b border-slate-200 pb-2">Repartition par agent</p>
        @forelse ($parAgent as $agent => $count)
            <div class="flex items-center justify-between py-2 text-sm border-b border-slate-100 last:border-0">
                <span class="text-slate-600 font-medium">{{ $agent }}</span>
                <span class="font-bold text-slate-900 bg-slate-100 px-2.5 py-0.5 rounded-md border border-slate-200 text-xs">{{ $count }}</span>
            </div>
        @empty
            <p class="text-sm text-slate-500 font-medium py-2">Aucune activite aujourd'hui.</p>
        @endforelse
    </div>

    <div class="bg-orange-50/80 border border-orange-200 rounded-2xl p-5 lg:col-span-1 shadow-sm">
        <p class="text-xs font-bold text-orange-800 uppercase tracking-wider mb-2">A propos de ce rapport</p>
        <p class="text-sm text-orange-950 font-medium leading-relaxed">
            Chaque action posee dans CASH aujourd'hui (creation, modification,
            suppression, depot, retrait, decision sur un pret...) apparait
            automatiquement ci-dessous, sans intervention de personne.
        </p>
    </div>
</div>

<div class="bg-slate-100 rounded-2xl border border-slate-300 shadow-md overflow-hidden">
    <div class="px-6 py-4 bg-slate-200/80 border-b border-slate-300">
        <h3 class="font-bold text-slate-900 text-sm uppercase tracking-wider">Journal complet du jour</h3>
    </div>
    <div class="w-full overflow-x-auto my-2"><table class="w-full text-sm text-left">
        <thead class="bg-slate-200/50 text-slate-800 uppercase text-xs font-bold tracking-wider border-b border-slate-300">
            <tr>
                <th class="px-6 py-3.5">Heure</th>
                <th class="px-6 py-3.5">Action</th>
                <th class="px-6 py-3.5">Concerne</th>
                <th class="px-6 py-3.5">Effectue par</th>
                <th class="px-6 py-3.5 no-print">Adresse IP</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-300 bg-slate-50/50">
            @forelse ($activites as $a)
                <tr class="hover:bg-white transition duration-150">
                    <td class="px-6 py-3.5 font-mono text-xs font-bold text-slate-600 whitespace-nowrap">{{ $a->created_at->format('H:i:s') }}</td>
                    <td class="px-6 py-3.5">
                        <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-slate-200 text-slate-800 border border-slate-300 inline-block">
                            {{ str_replace('.', ' - ', $a->action) }}
                        </span>
                    </td>
                    <td class="px-6 py-3.5 text-slate-700 font-semibold">
                        {{ class_basename($a->auditable_type ?? '') ?: '-' }}
                        @if ($a->auditable_id) <span class="text-slate-500 font-mono text-xs">#{{ $a->auditable_id }}</span> @endif
                    </td>
                    <td class="px-6 py-3.5 text-slate-900 font-bold">{{ $a->user->name ?? 'Systeme' }}</td>
                    <td class="px-6 py-3.5 text-slate-500 font-mono text-xs no-print">{{ $a->ip_address }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-6 py-12 text-center text-slate-500 font-medium bg-slate-50/50">Rien ne s'est encore passe aujourd'hui.</td></tr>
            @endforelse
        </tbody>
    </table></div>
</div>
@endsection
