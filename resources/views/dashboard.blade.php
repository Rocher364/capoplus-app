@extends('layouts.app')
@section('title', 'Tableau de bord')
@section('content')

<div class="flex items-center justify-between mb-6 no-print">
    <div>
        <h2 class="text-2xl font-bold text-slate-900">Bilan du jour</h2>
        <p class="text-sm text-slate-500">{{ now()->translatedFormat('l d F Y') }}</p>
    </div>
    <button onclick="window.print()" type="button"
            class="flex items-center gap-2 px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold hover:bg-slate-800">
        Imprimer le bilan
    </button>
</div>

<div class="hidden print:flex items-center gap-3 mb-6">
    <x-eagle-logo class="w-8 h-8 text-orange-600" />
    <div>
        <h2 class="text-xl font-bold">CASH - Bilan journalier</h2>
        <p class="text-sm text-slate-500">{{ now()->translatedFormat('l d F Y') }} - genere par {{ auth()->user()->name }}</p>
    </div>
</div>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl border shadow-sm p-5 print-card">
        <p class="text-xs font-medium text-slate-500 uppercase">Depots aujourd'hui</p>
        <p class="text-2xl font-bold text-emerald-600 mt-1">{{ number_format($totalDepots, 2) }}</p>
        <p class="text-xs text-slate-400 mt-1">{{ $nbDepots }} operation(s)</p>
    </div>
    <div class="bg-white rounded-xl border shadow-sm p-5 print-card">
        <p class="text-xs font-medium text-slate-500 uppercase">Retraits aujourd'hui</p>
        <p class="text-2xl font-bold text-amber-600 mt-1">{{ number_format($totalRetraits, 2) }}</p>
        <p class="text-xs text-slate-400 mt-1">{{ $nbRetraits }} operation(s)</p>
    </div>
    <div class="bg-white rounded-xl border shadow-sm p-5 print-card">
        <p class="text-xs font-medium text-slate-500 uppercase">Remboursements</p>
        <p class="text-2xl font-bold text-orange-600 mt-1">{{ number_format($totalRemboursements, 2) }}</p>
        <p class="text-xs text-slate-400 mt-1">{{ $nbRemboursements }} operation(s)</p>
    </div>
    <div class="bg-white rounded-xl border shadow-sm p-5 print-card">
        <p class="text-xs font-medium text-slate-500 uppercase">Mouvement net du jour</p>
        <p class="text-2xl font-bold {{ ($totalDepots - $totalRetraits) >= 0 ? 'text-emerald-700' : 'text-red-600' }} mt-1">
            {{ number_format($totalDepots - $totalRetraits, 2) }}
        </p>
        <p class="text-xs text-slate-400 mt-1">Depots - retraits</p>
    </div>
</div>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl border shadow-sm p-5 print-card">
        <p class="text-xs font-medium text-slate-500 uppercase">Nouveaux membres</p>
        <p class="text-xl font-bold text-slate-800 mt-1">{{ $nouveauxMembres }}</p>
    </div>
    <div class="bg-white rounded-xl border shadow-sm p-5 print-card">
        <p class="text-xs font-medium text-slate-500 uppercase">Nouveaux comptes</p>
        <p class="text-xl font-bold text-slate-800 mt-1">{{ $nouveauxComptes }}</p>
    </div>
    <div class="bg-white rounded-xl border shadow-sm p-5 print-card">
        <p class="text-xs font-medium text-slate-500 uppercase">Prets decaisses</p>
        <p class="text-xl font-bold text-slate-800 mt-1">{{ $nbPretsDecaisses }}</p>
        <p class="text-xs text-slate-400 mt-1">{{ number_format($montantPretsDecaisses, 2) }} HTG</p>
    </div>
    <div class="bg-white rounded-xl border shadow-sm p-5 print-card">
        <p class="text-xs font-medium text-slate-500 uppercase">Prets actifs (reseau)</p>
        <p class="text-xl font-bold text-slate-800 mt-1">{{ $pretsEnCours }}</p>
    </div>
</div>

<div class="bg-white rounded-xl border shadow-sm p-5 mb-6 print-card">
    <p class="text-xs font-medium text-slate-500 uppercase mb-3">Etat global du reseau</p>
    <div class="grid grid-cols-3 gap-4 text-sm">
        <div>
            <p class="text-slate-400">Solde total tous comptes</p>
            <p class="text-lg font-bold text-slate-900">{{ number_format($soldeTotalReseau, 2) }} HTG</p>
        </div>
        <div>
            <p class="text-slate-400">Comptes actifs</p>
            <p class="text-lg font-bold text-emerald-700">{{ $nbComptesActifs }}</p>
        </div>
        <div>
            <p class="text-slate-400">Comptes bloques</p>
            <p class="text-lg font-bold text-red-600">{{ $nbComptesBloques }}</p>
        </div>
    </div>
</div>

<div class="bg-white rounded-xl border shadow-sm print-card">
    <div class="px-6 py-4 border-b">
        <h3 class="font-semibold text-slate-800">Toutes les operations d'aujourd'hui</h3>
    </div>
    <div class="w-full overflow-x-auto my-2"><table class="w-full text-sm">
        <thead class="bg-slate-50 text-slate-500 uppercase text-xs">
            <tr>
                <th class="text-left px-6 py-3">Heure</th>
                <th class="text-left px-6 py-3">Reference</th>
                <th class="text-left px-6 py-3">Membre</th>
                <th class="text-left px-6 py-3">Type</th>
                <th class="text-right px-6 py-3">Montant</th>
                <th class="text-left px-6 py-3 no-print">Agent</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($transactionsDuJour as $t)
                <tr>
                    <td class="px-6 py-3 text-slate-500">{{ $t->effectuee_le->format('H:i') }}</td>
                    <td class="px-6 py-3 font-mono text-xs text-slate-500">{{ $t->reference }}</td>
                    <td class="px-6 py-3">{{ $t->account->member->prenom ?? '-' }} {{ $t->account->member->nom ?? '' }}</td>
                    <td class="px-6 py-3">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $t->type === 'depot' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                            {{ $t->type === 'depot' ? 'Depot' : 'Retrait' }}
                        </span>
                    </td>
                    <td class="px-6 py-3 text-right font-medium">{{ number_format($t->montant, 2) }}</td>
                    <td class="px-6 py-3 text-slate-500 no-print">{{ $t->user->name ?? '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-6 py-10 text-center text-slate-400">Aucune operation aujourd'hui pour le moment.</td></tr>
            @endforelse
        </tbody>
    </table></div>
</div>
@endsection


