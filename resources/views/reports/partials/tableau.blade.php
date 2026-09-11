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

<div class="bg-white rounded-2xl border border-slate-300 shadow-md overflow-hidden print-card">
    <div class="px-6 py-4 bg-slate-100 border-b border-slate-300">
        <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider">
            Detail {{ $rapport['granularite'] === 'mois' ? 'par mois' : 'par jour' }}
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
