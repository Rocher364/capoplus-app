@extends('layouts.app')
@section('title', 'Pret ' . $loan->numero_pret)
@section('content')

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white rounded-2xl border border-slate-300 shadow-md p-6">
            <div class="flex items-start justify-between flex-wrap gap-4">
                <div>
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Pret</p>
                    <h2 class="text-2xl font-bold text-slate-900 font-mono tracking-tight">{{ $loan->numero_pret }}</h2>
                    <p class="text-sm font-semibold text-slate-600 mt-1">{{ $loan->member->prenom }} {{ $loan->member->nom }} &middot; <span class="font-mono text-xs text-slate-500">{{ $loan->member->numero_membre }}</span></p>
                </div>
                @php
                    $badge = match ($loan->statut->value ?? $loan->statut) {
                        'demande' => 'bg-amber-100 text-amber-900 border-amber-300',
                        'approuve' => 'bg-blue-100 text-blue-900 border-blue-300',
                        'decaisse' => 'bg-emerald-100 text-emerald-900 border-emerald-300',
                        'en_retard' => 'bg-rose-100 text-rose-900 border-rose-300',
                        'solde' => 'bg-slate-200 text-slate-800 border-slate-300',
                        'rejete', 'annule' => 'bg-slate-200/80 text-slate-600 border-slate-300',
                        default => 'bg-slate-200 text-slate-800 border-slate-300',
                    };
                @endphp
                <span class="px-3.5 py-1.5 rounded-full text-xs font-bold border inline-block {{ $badge }}">
                    {{ $loan->statut->label() ?? $loan->statut }}
                </span>
            </div>

            <div class="grid grid-cols-3 gap-4 mt-6 pt-6 border-t border-slate-200">
                <div>
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Montant demande</p>
                    <p class="text-xl font-bold text-slate-900 mt-0.5">{{ number_format($loan->montant_demande, 2) }} <span class="text-xs text-slate-500 font-medium">HTG</span></p>
                </div>
                <div>
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Duree</p>
                    <p class="text-xl font-bold text-slate-900 mt-0.5">{{ $loan->duree_mois }} <span class="text-xs text-slate-500 font-medium">mois</span></p>
                </div>
                <div>
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Taux annuel</p>
                    <p class="text-xl font-bold text-slate-900 mt-0.5">{{ $loan->taux_interet }}%</p>
                </div>
            </div>

            <div class="mt-5 pt-5 border-t border-slate-200">
                <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Motif</p>
                <p class="text-sm font-medium text-slate-800 bg-slate-50 p-3.5 rounded-xl border border-slate-200/80">{{ $loan->motif }}</p>
            </div>
        </div>

        @if ($loan->schedules->isNotEmpty())
            <div class="bg-slate-100 rounded-2xl border border-slate-300 shadow-md overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-300 bg-slate-200/50">
                    <h3 class="font-bold text-slate-900 tracking-tight">Echeancier</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-slate-200/80 text-slate-800 uppercase text-xs font-bold tracking-wider border-b border-slate-300">
                            <tr>
                                <th class="px-4 py-3.5">#</th>
                                <th class="px-4 py-3.5">Echeance</th>
                                <th class="px-4 py-3.5 text-right">Capital</th>
                                <th class="px-4 py-3.5 text-right">Interet</th>
                                <th class="px-4 py-3.5 text-right">Total</th>
                                <th class="px-4 py-3.5 text-right">Restant</th>
                                <th class="px-4 py-3.5">Statut</th>
                                <th class="px-4 py-3.5 text-right">Paiement</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-300 bg-slate-50/50">
                            @foreach ($loan->schedules as $echeance)
                                <tr class="hover:bg-white transition duration-150">
                                    <td class="px-4 py-3.5 font-bold text-slate-600 text-xs">{{ $echeance->numero_echeance }}</td>
                                    <td class="px-4 py-3.5 font-medium text-slate-700 text-xs whitespace-nowrap">{{ $echeance->date_echeance->format('d/m/Y') }}</td>
                                    <td class="px-4 py-3.5 text-right font-medium text-slate-800 whitespace-nowrap">{{ number_format($echeance->capital, 2) }}</td>
                                    <td class="px-4 py-3.5 text-right font-medium text-slate-800 whitespace-nowrap">{{ number_format($echeance->interet, 2) }}</td>
                                    <td class="px-4 py-3.5 text-right font-bold text-slate-900 whitespace-nowrap">{{ number_format($echeance->montant_total, 2) }}</td>
                                    <td class="px-4 py-3.5 text-right font-medium text-slate-500 whitespace-nowrap">{{ number_format($echeance->solde_restant, 2) }}</td>
                                    <td class="px-4 py-3.5 whitespace-nowrap">
                                        @php
                                            $sbadge = match ($echeance->statut) {
                                                'payee' => 'bg-emerald-100 text-emerald-900 border-emerald-300',
                                                'partiellement_payee' => 'bg-amber-100 text-amber-900 border-amber-300',
                                                'en_retard' => 'bg-rose-100 text-rose-900 border-rose-300',
                                                default => 'bg-slate-200 text-slate-800 border-slate-300',
                                            };
                                        @endphp
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold border inline-block {{ $sbadge }}">
                                            {{ str_replace('_', ' ', $echeance->statut) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3.5 text-right whitespace-nowrap">
                                        @if ($echeance->statut !== 'payee')
                                            <form action="{{ route('loans.rembourser', [$loan, $echeance]) }}" method="POST" class="flex items-center justify-end gap-1.5">
                                                @csrf
                                                <input type="number" step="0.01" min="0.01" max="{{ $echeance->solde_restant }}" name="montant"
                                                       placeholder="Montant" required
                                                       class="w-24 rounded-xl border border-slate-300 bg-white text-slate-900 text-xs px-2.5 py-1.5 focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 transition outline-none">
                                                <button class="px-3 py-1.5 rounded-xl bg-orange-600 text-white text-xs font-semibold hover:bg-orange-700 shadow-sm active:scale-[0.98] transition duration-150">
                                                    Payer
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>

    <div class="space-y-6">
        @if ($loan->statut->value === 'demande')
            <div class="bg-white rounded-2xl border border-slate-300 shadow-md p-6">
                <h3 class="font-bold text-slate-900 mb-4 text-base border-b border-slate-200 pb-2">Decision</h3>
                <form action="{{ route('loans.approuver', $loan) }}" method="POST" class="space-y-3 mb-5">
                    @csrf
                    <div>
                        <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Montant approuve (vide = montant demande)</label>
                        <input type="number" step="0.01" name="montant_approuve" class="mt-1.5 w-full rounded-xl border border-slate-300 bg-slate-50 text-slate-900 text-sm px-4 py-2.5 focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition outline-none">
                    </div>
                    <button class="w-full py-2.5 rounded-xl bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700 shadow-sm active:scale-[0.98] transition duration-150">
                        Approuver
                    </button>
                </form>
                <form action="{{ route('loans.rejeter', $loan) }}" method="POST" class="space-y-3 pt-3 border-t border-slate-200">
                    @csrf
                    <div>
                        <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Justification du rejet</label>
                        <input type="text" name="justification_decision" required class="mt-1.5 w-full rounded-xl border border-slate-300 bg-slate-50 text-slate-900 text-sm px-4 py-2.5 focus:bg-white focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20 transition outline-none">
                    </div>
                    <button class="w-full py-2.5 rounded-xl bg-rose-50 text-rose-700 border border-rose-300 text-sm font-semibold hover:bg-rose-100 active:scale-[0.98] transition duration-150">
                        Rejeter
                    </button>
                </form>
            </div>
        @endif

        @if ($loan->statut->value === 'approuve')
            <div class="bg-white rounded-2xl border border-slate-300 shadow-md p-6">
                <h3 class="font-bold text-slate-900 mb-2 text-base border-b border-slate-200 pb-2">Decaissement</h3>
                <p class="text-xs font-medium text-slate-600 mb-4 leading-relaxed">
                    Verse <span class="font-bold text-slate-900">{{ number_format($loan->montant_approuve, 2) }} HTG</span> sur le compte du membre
                    et genere l'echeancier automatiquement.
                </p>
                <form action="{{ route('loans.decaisser', $loan) }}" method="POST">
                    @csrf
                    <button class="w-full py-2.5 rounded-xl bg-orange-600 text-white text-sm font-semibold hover:bg-orange-700 shadow-sm active:scale-[0.98] transition duration-150">
                        Decaisser le pret
                    </button>
                </form>
            </div>
        @endif

        <div class="bg-white rounded-2xl border border-slate-300 shadow-md p-6">
            <h3 class="font-bold text-slate-900 mb-4 text-xs uppercase tracking-wider border-b border-slate-200 pb-2">Details administratifs</h3>
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between items-center">
                    <dt class="text-xs font-bold text-slate-500 uppercase tracking-wider">Demande par</dt>
                    <dd class="font-semibold text-slate-900">{{ $loan->demandePar->name ?? '-' }}</dd>
                </div>
                <div class="flex justify-between items-center">
                    <dt class="text-xs font-bold text-slate-500 uppercase tracking-wider">Date demande</dt>
                    <dd class="font-semibold text-slate-900">{{ $loan->date_demande->format('d/m/Y') }}</dd>
                </div>
                @if ($loan->approuvePar)
                    <div class="flex justify-between items-center">
                        <dt class="text-xs font-bold text-slate-500 uppercase tracking-wider">Decide par</dt>
                        <dd class="font-semibold text-slate-900">{{ $loan->approuvePar->name }}</dd>
                    </div>
                @endif
                @if ($loan->date_decaissement)
                    <div class="flex justify-between items-center">
                        <dt class="text-xs font-bold text-slate-500 uppercase tracking-wider">Decaisse le</dt>
                        <dd class="font-semibold text-slate-900">{{ $loan->date_decaissement->format('d/m/Y') }}</dd>
                    </div>
                @endif
            </dl>
        </div>
    </div>
</div>
@endsection