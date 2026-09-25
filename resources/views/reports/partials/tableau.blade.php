<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-2xl border border-slate-300 shadow-sm p-5 print-card">
        <p class="text-xs font-bold text-slate-700 uppercase tracking-wider">Total depots</p>
        <p class="text-2xl font-bold text-emerald-600 mt-1.5 font-mono">{{ number_format($rapport['totalDepots'], 2) }}</p>
    </div>
    <div class="bg-white rounded-2xl border border-slate-300 shadow-sm p-5 print-card">
        <p class="text-xs font-bold text-slate-700 uppercase tracking-wider">Total retraits</p>
        <p class="text-2xl font-bold text-amber-600 mt-1.5 font-mono">{{ number_format($rapport['totalRetraits'], 2) }}</p>
    </div>
    <div class="bg-white rounded-2xl border border-slate-300 shadow-sm p-5 print-card">
        <p class="text-xs font-bold text-slate-700 uppercase tracking-wider">Total remboursements</p>
        <p class="text-2xl font-bold text-orange-600 mt-1.5 font-mono">{{ number_format($rapport['totalRemboursements'], 2) }}</p>
    </div>
    <div class="bg-white rounded-2xl border border-slate-300 shadow-sm p-5 print-card">
        <p class="text-xs font-bold text-slate-700 uppercase tracking-wider">Operations enregistrees</p>
        <p class="text-2xl font-bold text-slate-900 mt-1.5 font-mono">{{ $rapport['totalOperations'] }}</p>
    </div>
</div>

{{-- SYNTHESE PAR PERIODE --}}
<div class="bg-white rounded-2xl border border-slate-300 shadow-md overflow-hidden print-card mb-6">
    <div class="px-6 py-4 bg-slate-100 border-b border-slate-300">
        <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider">
            Synthese {{ $rapport['granularite'] === 'mois' ? 'par mois' : 'par jour' }}
        </h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="bg-slate-100 border-b border-slate-300 text-slate-700 uppercase text-xs tracking-wider">
                <tr>
                    <th class="px-6 py-3.5 font-bold">{{ $rapport['granularite'] === 'mois' ? 'Mois' : 'Jour' }}</th>
                    <th class="px-6 py-3.5 text-right font-bold">Depots</th>
                    <th class="px-6 py-3.5 text-right font-bold">Retraits</th>
                    <th class="px-6 py-3.5 text-right font-bold">Remboursements</th>
                    <th class="px-6 py-3.5 text-right font-bold">Net</th>
                    <th class="px-6 py-3.5 text-right font-bold">Operations</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @forelse ($rapport['lignes'] as $ligne)
                    <tr class="{{ $ligne['est_aujourdhui'] ? 'bg-orange-50/60 font-semibold' : 'hover:bg-slate-50 transition duration-100' }}">
                        <td class="px-6 py-4 text-slate-900">
                            {{ ucfirst($ligne['label']) }}
                            @if ($ligne['est_aujourdhui'])
                                <span class="ml-1 text-xs text-orange-600 font-bold uppercase tracking-wide">(aujourd'hui)</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right font-mono text-emerald-700 font-semibold">{{ number_format($ligne['depots'], 2) }}</td>
                        <td class="px-6 py-4 text-right font-mono text-amber-700 font-semibold">{{ number_format($ligne['retraits'], 2) }}</td>
                        <td class="px-6 py-4 text-right font-mono text-orange-700 font-semibold">{{ number_format($ligne['remboursements'], 2) }}</td>
                        <td class="px-6 py-4 text-right font-mono font-bold {{ ($ligne['depots'] - $ligne['retraits']) >= 0 ? 'text-slate-900' : 'text-rose-600' }}">
                            {{ number_format($ligne['depots'] - $ligne['retraits'], 2) }}
                        </td>
                        <td class="px-6 py-4 text-right font-mono text-slate-600 font-medium">{{ $ligne['nb'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-slate-500 font-medium bg-slate-50/50">
                            Aucune donnee pour cette periode.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- DETAIL DES OPERATIONS PAR MEMBRE POUR LA PERIODE --}}
@if (isset($rapport['operations']) && $rapport['operations']->isNotEmpty())
<div class="bg-white rounded-2xl border border-slate-300 shadow-md overflow-hidden print-card">
    <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between">
        <div>
            <h3 class="text-xs font-bold uppercase tracking-wider">
                Detail nominatif des operations membres ({{ $rapport['operations']->count() }} transactions)
            </h3>
            <p class="text-[11px] text-slate-300 mt-0.5">Dépôts, retraits et mouvements financiers avec identification des membres</p>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="bg-slate-100 border-b border-slate-300 text-slate-700 uppercase text-[11px] font-bold tracking-wider">
                <tr>
                    <th class="px-5 py-3.5 whitespace-nowrap">Date & Heure</th>
                    <th class="px-5 py-3.5 whitespace-nowrap">Reference</th>
                    <th class="px-5 py-3.5 whitespace-nowrap">Membre</th>
                    <th class="px-5 py-3.5 whitespace-nowrap">N° Compte</th>
                    <th class="px-5 py-3.5 text-center whitespace-nowrap">Type</th>
                    <th class="px-5 py-3.5 text-right whitespace-nowrap">Montant</th>
                    <th class="px-5 py-3.5 text-right whitespace-nowrap">Solde apres</th>
                    <th class="px-5 py-3.5 whitespace-nowrap">Agent / Caissier</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 text-xs">
                @foreach ($rapport['operations'] as $op)
                    @php
                        $membre = $op->account?->member;
                    @endphp
                    <tr class="hover:bg-slate-50 transition duration-100">
                        <td class="px-5 py-3.5 font-mono text-slate-600 whitespace-nowrap">
                            {{ $op->effectuee_le->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-5 py-3.5 font-mono text-slate-500 whitespace-nowrap text-[11px]">
                            {{ $op->reference }}
                        </td>
                        <td class="px-5 py-3.5 font-bold text-slate-900 whitespace-nowrap">
                            {{ $membre?->nom_complet ?? 'Client non spécifié' }}
                        </td>
                        <td class="px-5 py-3.5 font-mono text-slate-600 whitespace-nowrap">
                            {{ $op->account?->numero_compte ?? '-' }}
                        </td>
                        <td class="px-5 py-3.5 text-center whitespace-nowrap">
                            @if ($op->type === 'depot')
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800 border border-blue-200 uppercase">Depot</span>
                            @elseif ($op->type === 'retrait')
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200 uppercase">Retrait</span>
                            @else
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-800 border border-slate-200 uppercase">{{ $op->type }}</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5 text-right font-mono font-bold whitespace-nowrap {{ $op->type === 'depot' ? 'text-blue-600' : 'text-amber-600' }}">
                            {{ $op->type === 'depot' ? '+' : '-' }}{{ number_format((float) $op->montant, 2) }}
                        </td>
                        <td class="px-5 py-3.5 text-right font-mono text-emerald-700 font-semibold whitespace-nowrap">
                            {{ number_format((float) $op->solde_apres, 2) }}
                        </td>
                        <td class="px-5 py-3.5 text-slate-700 whitespace-nowrap">
                            {{ $op->user?->name ?? 'Systeme' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
