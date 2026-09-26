@extends('layouts.director')
@section('title', 'Tableau de bord Directeur')
@section('content')

<div class="flex items-center justify-between mb-6 flex-wrap gap-4 no-print">
    <div>
        <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Supervision & Rapport du jour</h2>
        <p class="text-sm font-medium text-slate-600 mt-0.5">{{ now()->translatedFormat('l d F Y') }} - Espace Direction (Lecture seule)</p>
    </div>
    <div class="flex items-center gap-3">
        <a href="{{ route('director.historique') }}"
           class="px-4 py-2.5 rounded-xl border border-slate-300 bg-white text-slate-700 text-sm font-semibold hover:bg-slate-50 shadow-sm transition">
            Consulter l'historique d'audit
        </a>
        <button data-action="print" type="button"
                class="flex items-center gap-2 px-5 py-2.5 rounded-xl bg-slate-900 text-white text-sm font-semibold hover:bg-slate-800 shadow-sm active:scale-[0.98] transition duration-150 cursor-pointer">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Imprimer le rapport
        </button>
    </div>
</div>

{{-- KPI Summary Cards --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-2xl border border-slate-300 shadow-sm p-5 print-card">
        <div class="flex items-center justify-between">
            <p class="text-xs font-bold text-slate-600 uppercase tracking-wider">Membres enregistrés</p>
            <span class="w-8 h-8 rounded-lg bg-orange-50 text-orange-600 flex items-center justify-center font-bold text-sm">👥</span>
        </div>
        <p class="text-2xl font-extrabold text-slate-900 mt-2 font-mono">{{ $kpis['totalMembres'] }}</p>
        <p class="text-xs text-slate-500 font-medium mt-1">Clients actifs de la plateforme</p>
    </div>

    <div class="bg-white rounded-2xl border border-slate-300 shadow-sm p-5 print-card">
        <div class="flex items-center justify-between">
            <p class="text-xs font-bold text-slate-600 uppercase tracking-wider">Épargne totale en caisse</p>
            <span class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-sm">💰</span>
        </div>
        <p class="text-2xl font-extrabold text-emerald-600 mt-2 font-mono">{{ number_format($kpis['totalSolde'], 2) }} <span class="text-xs text-slate-500 font-normal">HTG</span></p>
        <p class="text-xs text-slate-500 font-medium mt-1">Cumul des soldes des comptes</p>
    </div>

    <div class="bg-white rounded-2xl border border-slate-300 shadow-sm p-5 print-card">
        <div class="flex items-center justify-between">
            <p class="text-xs font-bold text-slate-600 uppercase tracking-wider">Dépôts du jour</p>
            <span class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-sm">📥</span>
        </div>
        <p class="text-2xl font-extrabold text-blue-600 mt-2 font-mono">+{{ number_format($kpis['depotsJour'], 2) }} <span class="text-xs text-slate-500 font-normal">HTG</span></p>
        <p class="text-xs text-slate-500 font-medium mt-1">Entrées de fonds aujourd'hui</p>
    </div>

    <div class="bg-white rounded-2xl border border-slate-300 shadow-sm p-5 print-card">
        <div class="flex items-center justify-between">
            <p class="text-xs font-bold text-slate-600 uppercase tracking-wider">Retraits du jour</p>
            <span class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center font-bold text-sm">📤</span>
        </div>
        <p class="text-2xl font-extrabold text-amber-600 mt-2 font-mono">-{{ number_format($kpis['retraitsJour'], 2) }} <span class="text-xs text-slate-500 font-normal">HTG</span></p>
        <p class="text-xs text-slate-500 font-medium mt-1">Sorties de fonds aujourd'hui</p>
    </div>
</div>

{{-- SECTION 1 : LISTE DES MEMBRES ET LEURS ACTIVITES FINANCIERES --}}
<div class="bg-white rounded-2xl border border-slate-300 shadow-md overflow-hidden mb-6 print-card">
    <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between">
        <div>
            <h3 class="font-bold text-sm uppercase tracking-wider">Membres enregistrés & Portefeuilles</h3>
            <p class="text-xs text-slate-300 mt-0.5">Vue complète des membres, de leurs comptes et des volumes de dépôts / retraits</p>
        </div>
        <span class="text-xs font-bold bg-orange-600 px-3 py-1 rounded-full text-white">{{ $membres->count() }} membre(s)</span>
    </div>

    <div class="report-table-wrap print-table-wrap w-full overflow-x-auto">
        <table class="report-table print-table w-full text-sm text-left border-collapse">
            <thead class="bg-slate-100 text-slate-700 uppercase text-[11px] font-bold tracking-wider border-b border-slate-200">
                <tr>
                    <th class="px-5 py-3.5 whitespace-nowrap">N° Membre</th>
                    <th class="px-5 py-3.5 whitespace-nowrap">Nom & Prénom</th>
                    <th class="px-5 py-3.5 whitespace-nowrap">Contact / NIF-CIN</th>
                    <th class="px-5 py-3.5 whitespace-nowrap">N° Compte</th>
                    <th class="px-5 py-3.5 text-right whitespace-nowrap">Solde Actuel</th>
                    <th class="px-5 py-3.5 text-right whitespace-nowrap">Cumul Dépôts</th>
                    <th class="px-5 py-3.5 text-right whitespace-nowrap">Cumul Retraits</th>
                    <th class="px-5 py-3.5 text-center whitespace-nowrap">Statut</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-xs text-slate-800">
                @forelse ($membres as $m)
                    @php
                        $compte = $m->account;
                        $depotsTotal = $compte ? $compte->transactions->where('type', 'depot')->sum('montant') : 0;
                        $retraitsTotal = $compte ? $compte->transactions->where('type', 'retrait')->sum('montant') : 0;
                    @endphp
                    <tr class="hover:bg-slate-50/80 transition duration-150">
                        <td class="px-5 py-3.5 font-mono font-bold text-slate-700 whitespace-nowrap">{{ $m->numero_membre }}</td>
                        <td class="px-5 py-3.5 font-bold text-slate-900 whitespace-nowrap">{{ $m->nom_complet }}</td>
                        <td class="px-5 py-3.5 text-slate-600 whitespace-nowrap">
                            <div>{{ $m->telephone ?: '-' }}</div>
                            <div class="text-[10px] text-slate-400 font-mono">{{ $m->nif_cin ?: 'N/A' }}</div>
                        </td>
                        <td class="px-5 py-3.5 font-mono text-slate-600 whitespace-nowrap">
                            {{ $compte?->numero_compte ?? 'Aucun compte' }}
                        </td>
                        <td class="px-5 py-3.5 text-right font-mono font-bold text-emerald-700 whitespace-nowrap">
                            {{ $compte ? number_format((float) $compte->solde, 2) . ' HTG' : '0.00 HTG' }}
                        </td>
                        <td class="px-5 py-3.5 text-right font-mono font-semibold text-blue-600 whitespace-nowrap">
                            +{{ number_format((float) $depotsTotal, 2) }}
                        </td>
                        <td class="px-5 py-3.5 text-right font-mono font-semibold text-amber-600 whitespace-nowrap">
                            -{{ number_format((float) $retraitsTotal, 2) }}
                        </td>
                        <td class="px-5 py-3.5 text-center whitespace-nowrap">
                            @if ($compte && $compte->estActif())
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">Actif</span>
                            @elseif ($compte)
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-200">Bloqué</span>
                            @else
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200">Sans compte</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center text-slate-500 font-medium bg-slate-50/50">
                            Aucun membre enregistré sur la plateforme pour le moment.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- SECTION 2 : OPERATIONS FINANCIERES DU JOUR (DEPOTS & RETRAITS) --}}
<div class="bg-white rounded-2xl border border-slate-300 shadow-md overflow-hidden mb-6 print-card">
    <div class="px-6 py-4 bg-slate-100 border-b border-slate-200 flex items-center justify-between">
        <div>
            <h3 class="font-bold text-slate-900 text-sm uppercase tracking-wider">Opérations financières du jour</h3>
            <p class="text-xs text-slate-500 mt-0.5">Dépôts, retraits et mouvements de fonds enregistrés aujourd'hui</p>
        </div>
        <span class="text-xs font-bold bg-slate-900 px-3 py-1 rounded-full text-white">{{ $transactionsDuJour->count() }} transaction(s)</span>
    </div>

    <div class="report-table-wrap print-table-wrap w-full overflow-x-auto">
        <table class="report-table print-table w-full text-sm text-left border-collapse">
            <thead class="bg-slate-50 text-slate-700 uppercase text-[11px] font-bold tracking-wider border-b border-slate-200">
                <tr>
                    <th class="px-5 py-3 whitespace-nowrap">Heure</th>
                    <th class="px-5 py-3 whitespace-nowrap">Référence</th>
                    <th class="px-5 py-3 whitespace-nowrap">Membre / Client</th>
                    <th class="px-5 py-3 whitespace-nowrap">N° Compte</th>
                    <th class="px-5 py-3 text-center whitespace-nowrap">Type d'opération</th>
                    <th class="px-5 py-3 text-right whitespace-nowrap">Montant</th>
                    <th class="px-5 py-3 text-right whitespace-nowrap">Solde Après</th>
                    <th class="px-5 py-3 whitespace-nowrap">Caissier / Agent</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-xs text-slate-800">
                @forelse ($transactionsDuJour as $t)
                    @php
                        $membre = $t->account?->member;
                    @endphp
                    <tr class="hover:bg-slate-50/80 transition duration-150">
                        <td class="px-5 py-3.5 font-mono font-bold text-slate-600 whitespace-nowrap">{{ $t->effectuee_le->format('H:i:s') }}</td>
                        <td class="px-5 py-3.5 font-mono text-[11px] text-slate-500 whitespace-nowrap">{{ $t->reference }}</td>
                        <td class="px-5 py-3.5 font-bold text-slate-900 whitespace-nowrap">
                            {{ $membre?->nom_complet ?? 'Non spécifié' }}
                        </td>
                        <td class="px-5 py-3.5 font-mono text-slate-600 whitespace-nowrap">
                            {{ $t->account?->numero_compte ?? '-' }}
                        </td>
                        <td class="px-5 py-3.5 text-center whitespace-nowrap">
                            @if ($t->type === 'depot')
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-blue-100 text-blue-800 border border-blue-200 uppercase">Dépôt</span>
                            @elseif ($t->type === 'retrait')
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-800 border border-amber-200 uppercase">Retrait</span>
                            @else
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-slate-100 text-slate-800 border border-slate-200 uppercase">{{ $t->type }}</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5 text-right font-mono font-extrabold whitespace-nowrap {{ $t->type === 'depot' ? 'text-blue-600' : 'text-amber-600' }}">
                            {{ $t->type === 'depot' ? '+' : '-' }}{{ number_format((float) $t->montant, 2) }} HTG
                        </td>
                        <td class="px-5 py-3.5 text-right font-mono font-bold text-emerald-700 whitespace-nowrap">
                            {{ number_format((float) $t->solde_apres, 2) }} HTG
                        </td>
                        <td class="px-5 py-3.5 text-slate-700 font-semibold whitespace-nowrap">
                            {{ $t->user?->name ?? 'Système' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-10 text-center text-slate-500 font-medium bg-slate-50/50">
                            Aucune opération financière (dépôt ou retrait) effectuée aujourd'hui.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
