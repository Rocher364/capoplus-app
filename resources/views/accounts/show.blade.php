@extends('layouts.app')
@section('title', 'Compte ' . $account->numero_compte)
@section('content')

<div class="flex items-center justify-between mb-6 no-print">
    <div></div>
    <button onclick="window.print()" type="button"
            class="flex items-center gap-2 px-5 py-2.5 rounded-xl bg-slate-900 text-white text-sm font-semibold hover:bg-slate-800 shadow-sm active:scale-[0.98] transition duration-150">
        Imprimer le releve
    </button>
</div>

<div class="hidden print:flex items-center gap-3 mb-6">
    <x-eagle-logo class="w-7 h-7 text-orange-600" />
    <div>
        <h2 class="text-xl font-bold text-slate-900">CASH - Releve de compte</h2>
        <p class="text-sm text-slate-600">Imprime le {{ now()->format('d/m/Y H:i') }}</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white rounded-2xl border border-slate-300 shadow-sm p-6 print-card">
            <div class="flex items-start justify-between flex-wrap gap-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Compte</p>
                    <h2 class="text-2xl font-bold text-slate-900 font-mono mt-0.5">{{ $account->numero_compte }}</h2>
                    <p class="text-sm font-medium text-slate-600 mt-1">{{ $account->member->prenom }} {{ $account->member->nom }} &middot; <span class="font-mono text-xs">{{ $account->member->numero_membre }}</span></p>
                </div>
                <div class="text-right">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Solde actuel</p>
                    <p class="text-3xl font-extrabold text-orange-600 mt-0.5">{{ number_format($account->solde, 2) }} <span class="text-base font-semibold text-slate-500">HTG</span></p>
                </div>
            </div>

            @if ($account->statut === 'bloque')
                <div class="mt-6 rounded-xl bg-red-50 border border-red-200 p-4 text-sm text-red-800 no-print">
                    <strong class="font-bold">Compte bloque.</strong> {{ $account->raison_blocage }}
                    <form action="{{ route('accounts.debloquer', $account) }}" method="POST" class="mt-2">
                        @csrf
                        <button class="text-sm font-bold text-red-700 hover:text-red-900 underline transition">Debloquer ce compte</button>
                    </form>
                </div>
            @else
                <form action="{{ route('accounts.bloquer', $account) }}" method="POST" class="mt-6 flex gap-2 no-print">
                    @csrf
                    <input type="text" name="raison_blocage" placeholder="Raison du blocage" class="flex-1 rounded-xl border border-slate-300 bg-slate-50 text-slate-900 placeholder-slate-400 text-sm px-4 py-2.5 focus:bg-white focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 transition outline-none" required>
                    <button class="px-5 py-2.5 rounded-xl bg-red-50 text-red-700 text-sm font-semibold border border-red-200 hover:bg-red-100 hover:text-red-800 shrink-0 transition active:scale-[0.98]">Bloquer</button>
                </form>
            @endif
        </div>

        <div class="bg-slate-100 rounded-2xl border border-slate-300 shadow-md overflow-hidden print-card">
            <div class="px-6 py-4 bg-slate-200/80 border-b border-slate-300 flex items-center justify-between">
                <h3 class="font-bold text-slate-900 text-sm uppercase tracking-wider">Transactions recentes</h3>
                <a href="{{ route('accounts.transactions', $account) }}" class="text-xs font-bold text-orange-600 hover:text-orange-700 hover:underline transition no-print">Historique complet &rarr;</a>
            </div>
            <div class="w-full overflow-x-auto my-2"><table class="w-full text-sm text-left">
                <thead class="bg-slate-200/50 text-slate-700 uppercase text-xs font-bold tracking-wider border-b border-slate-300">
                    <tr>
                        <th class="px-6 py-3.5">Reference</th>
                        <th class="px-6 py-3.5">Type</th>
                        <th class="text-right px-6 py-3.5">Montant</th>
                        <th class="text-right px-6 py-3.5">Solde apres</th>
                        <th class="px-6 py-3.5">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-300 bg-slate-50/50">
                    @forelse ($account->transactions as $t)
                        <tr class="hover:bg-white transition duration-150">
                            <td class="px-6 py-3.5 font-mono text-xs font-bold text-slate-600">{{ $t->reference }}</td>
                            <td class="px-6 py-3.5">
                                <span class="px-2.5 py-1 rounded-full text-xs font-bold border {{ $t->type === 'depot' ? 'bg-emerald-100 text-emerald-800 border-emerald-300' : 'bg-amber-100 text-amber-800 border-amber-300' }}">
                                    {{ $t->type === 'depot' ? 'Depot' : 'Retrait' }}
                                </span>
                            </td>
                            <td class="px-6 py-3.5 text-right font-bold text-slate-900">{{ number_format($t->montant, 2) }}</td>
                            <td class="px-6 py-3.5 text-right font-medium text-slate-600">{{ number_format($t->solde_apres, 2) }}</td>
                            <td class="px-6 py-3.5 text-slate-600 font-medium text-xs">{{ $t->effectuee_le->format('d/m/Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-6 py-10 text-center text-slate-500 font-medium bg-slate-50/50">Aucune transaction.</td></tr>
                    @endforelse
                </tbody>
            </table></div>
        </div>
    </div>

    <div class="space-y-6 no-print">
        <div class="bg-white rounded-2xl border border-slate-300 shadow-md overflow-hidden">
            <div class="bg-emerald-50 border-b border-emerald-200 px-5 py-3.5">
                <h3 class="font-bold text-emerald-900 text-sm uppercase tracking-wider">Nouveau depot</h3>
            </div>
            <form action="{{ route('accounts.depot', $account) }}" method="POST" class="p-5 space-y-4">
                @csrf
                <div>
                    <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Montant (HTG)</label>
                    <input type="number" step="0.01" min="0.01" name="montant" required
                           class="mt-1.5 w-full rounded-xl border border-slate-300 bg-slate-50 text-slate-900 text-sm px-4 py-2.5 focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition outline-none">
                </div>
                <div>
                    <label class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-2 block">Moyen de paiement</label>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="flex items-center justify-center gap-1.5 border border-slate-300 bg-slate-50 rounded-xl py-2.5 text-xs font-semibold text-slate-700 cursor-pointer transition hover:bg-slate-100 has-[:checked]:bg-emerald-100 has-[:checked]:border-emerald-500 has-[:checked]:text-emerald-900">
                            <input type="radio" name="moyen" value="especes" class="hidden" checked>
                            Especes
                        </label>
                        <label class="flex items-center justify-center gap-1.5 border border-slate-300 bg-slate-50 rounded-xl py-2.5 text-xs font-semibold text-slate-700 cursor-pointer transition hover:bg-slate-100 has-[:checked]:bg-emerald-100 has-[:checked]:border-emerald-500 has-[:checked]:text-emerald-900">
                            <input type="radio" name="moyen" value="cheque" class="hidden">
                            Cheque
                        </label>
                        <label class="flex items-center justify-center gap-1.5 border border-slate-300 bg-slate-50 rounded-xl py-2.5 text-xs font-semibold text-slate-700 cursor-pointer transition hover:bg-slate-100 has-[:checked]:bg-emerald-100 has-[:checked]:border-emerald-500 has-[:checked]:text-emerald-900">
                            <input type="radio" name="moyen" value="virement" class="hidden">
                            Virement
                        </label>
                        <label class="flex items-center justify-center gap-1.5 border border-slate-300 bg-slate-50 rounded-xl py-2.5 text-xs font-semibold text-slate-700 cursor-pointer transition hover:bg-slate-100 has-[:checked]:bg-emerald-100 has-[:checked]:border-emerald-500 has-[:checked]:text-emerald-900">
                            <input type="radio" name="moyen" value="autre" class="hidden">
                            Autre
                        </label>
                    </div>
                </div>
                <div>
                    <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Note (optionnel)</label>
                    <input type="text" name="description" maxlength="255"
                           class="mt-1.5 w-full rounded-xl border border-slate-300 bg-slate-50 text-slate-900 text-sm px-4 py-2.5 focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition outline-none">
                </div>
                <button class="w-full py-3 rounded-xl bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700 shadow-sm active:scale-[0.98] transition duration-150">
                    Enregistrer le depot
                </button>
            </form>
        </div>

        <div class="bg-white rounded-2xl border border-slate-300 shadow-md overflow-hidden">
            <div class="bg-amber-50 border-b border-amber-200 px-5 py-3.5">
                <h3 class="font-bold text-amber-900 text-sm uppercase tracking-wider">Nouveau retrait</h3>
            </div>
            <form action="{{ route('accounts.retrait', $account) }}" method="POST" class="p-5 space-y-4">
                @csrf
                <div>
                    <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Montant (HTG)</label>
                    <input type="number" step="0.01" min="0.01" name="montant" required
                           class="mt-1.5 w-full rounded-xl border border-slate-300 bg-slate-50 text-slate-900 text-sm px-4 py-2.5 focus:bg-white focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 transition outline-none">
                </div>
                <div>
                    <label class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-2 block">Moyen de paiement</label>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="flex items-center justify-center gap-1.5 border border-slate-300 bg-slate-50 rounded-xl py-2.5 text-xs font-semibold text-slate-700 cursor-pointer transition hover:bg-slate-100 has-[:checked]:bg-amber-100 has-[:checked]:border-amber-500 has-[:checked]:text-amber-900">
                            <input type="radio" name="moyen" value="especes" class="hidden" checked>
                            Especes
                        </label>
                        <label class="flex items-center justify-center gap-1.5 border border-slate-300 bg-slate-50 rounded-xl py-2.5 text-xs font-semibold text-slate-700 cursor-pointer transition hover:bg-slate-100 has-[:checked]:bg-amber-100 has-[:checked]:border-amber-500 has-[:checked]:text-amber-900">
                            <input type="radio" name="moyen" value="cheque" class="hidden">
                            Cheque
                        </label>
                        <label class="flex items-center justify-center gap-1.5 border border-slate-300 bg-slate-50 rounded-xl py-2.5 text-xs font-semibold text-slate-700 cursor-pointer transition hover:bg-slate-100 has-[:checked]:bg-amber-100 has-[:checked]:border-amber-500 has-[:checked]:text-amber-900">
                            <input type="radio" name="moyen" value="virement" class="hidden">
                            Virement
                        </label>
                        <label class="flex items-center justify-center gap-1.5 border border-slate-300 bg-slate-50 rounded-xl py-2.5 text-xs font-semibold text-slate-700 cursor-pointer transition hover:bg-slate-100 has-[:checked]:bg-amber-100 has-[:checked]:border-amber-500 has-[:checked]:text-amber-900">
                            <input type="radio" name="moyen" value="autre" class="hidden">
                            Autre
                        </label>
                    </div>
                </div>
                <div>
                    <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Note (optionnel)</label>
                    <input type="text" name="description" maxlength="255"
                           class="mt-1.5 w-full rounded-xl border border-slate-300 bg-slate-50 text-slate-900 text-sm px-4 py-2.5 focus:bg-white focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 transition outline-none">
                </div>
                <button class="w-full py-3 rounded-xl bg-amber-600 text-white text-sm font-semibold hover:bg-amber-700 shadow-sm active:scale-[0.98] transition duration-150">
                    Enregistrer le retrait
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
