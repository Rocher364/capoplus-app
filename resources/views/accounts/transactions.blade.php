@extends('layouts.app')
@section('title', 'Historique - ' . $account->numero_compte)
@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-2xl font-bold text-slate-900">Historique du compte {{ $account->numero_compte }}</h2>
        <p class="text-sm text-slate-500">{{ $account->member->prenom }} {{ $account->member->nom }}</p>
    </div>
    <a href="{{ route('accounts.show', $account) }}" class="text-sm text-orange-600 hover:underline">&larr; Retour au compte</a>
</div>

<div class="bg-white rounded-xl border shadow-sm overflow-hidden">
    <div class="w-full overflow-x-auto my-2"><table class="w-full text-sm">
        <thead class="bg-slate-50 text-slate-500 uppercase text-xs">
            <tr>
                <th class="text-left px-6 py-3">Reference</th>
                <th class="text-left px-6 py-3">Type</th>
                <th class="text-right px-6 py-3">Montant</th>
                <th class="text-right px-6 py-3">Solde apres</th>
                <th class="text-left px-6 py-3">Agent</th>
                <th class="text-left px-6 py-3">Date</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($transactions as $t)
                <tr>
                    <td class="px-6 py-3 font-mono text-xs text-slate-500">{{ $t->reference }}</td>
                    <td class="px-6 py-3">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $t->type === 'depot' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                            {{ $t->type === 'depot' ? 'Depot' : 'Retrait' }}
                        </span>
                    </td>
                    <td class="px-6 py-3 text-right font-medium">{{ number_format($t->montant, 2) }}</td>
                    <td class="px-6 py-3 text-right text-slate-500">{{ number_format($t->solde_apres, 2) }}</td>
                    <td class="px-6 py-3 text-slate-500">{{ $t->user->name ?? '-' }}</td>
                    <td class="px-6 py-3 text-slate-500">{{ $t->effectuee_le->format('d/m/Y H:i') }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-6 py-10 text-center text-slate-400">Aucune transaction.</td></tr>
            @endforelse
        </tbody>
    </table></div>
</div>

<div class="mt-4">{{ $transactions->links() }}</div>
@endsection

