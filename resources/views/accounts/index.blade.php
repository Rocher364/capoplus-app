@extends('layouts.app')
@section('title', 'Comptes')
@section('content')

<div class="flex items-center justify-between mb-6 flex-wrap gap-4">
    <div>
        <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Comptes membres</h2>
        <p class="text-sm text-slate-600 mt-0.5">{{ $accounts->total() }} compte(s) au total</p>
    </div>
    <a href="{{ route('members.index') }}" class="px-5 py-2.5 rounded-xl bg-orange-600 text-white text-sm font-semibold hover:bg-orange-700 shadow-sm hover:shadow-orange-600/20 active:scale-[0.98] transition duration-200 inline-flex items-center gap-2">
        Ouvrir un nouveau compte
    </a>
</div>

<form method="GET" action="{{ route('accounts.index') }}" class="mb-6 flex gap-3">
    <input type="text" name="q" value="{{ request('q') }}" placeholder="Rechercher par numero de compte ou nom du membre..."
           class="flex-1 rounded-xl border border-slate-300 bg-white text-slate-900 placeholder-slate-400 text-sm px-4 py-3 focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 shadow-sm transition duration-150 outline-none">
    <button type="submit" class="px-6 py-3 rounded-xl bg-slate-900 text-white text-sm font-semibold hover:bg-slate-800 shadow-sm active:scale-[0.98] transition duration-150">
        Rechercher
    </button>
    @if (request('q'))
        <a href="{{ route('accounts.index') }}" class="px-5 py-3 rounded-xl border border-slate-300 bg-slate-100 text-slate-700 text-sm font-medium hover:bg-slate-200 transition duration-150 flex items-center justify-center">
            Effacer
        </a>
    @endif
</form>

<div class="bg-slate-100 rounded-2xl border border-slate-300 shadow-md overflow-hidden">
    <table class="w-full text-sm text-left">
        <thead class="bg-slate-200/80 text-slate-800 uppercase text-xs font-bold tracking-wider border-b border-slate-300">
            <tr>
                <th class="px-6 py-4">N&deg; compte</th>
                <th class="px-6 py-4">Membre</th>
                <th class="text-right px-6 py-4">Solde</th>
                <th class="px-6 py-4">Statut</th>
                <th class="px-6 py-4 text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-300 bg-slate-50/50">
            @forelse ($accounts as $account)
                <tr class="hover:bg-white transition duration-150">
                    <td class="px-6 py-4 font-mono text-xs font-bold text-slate-700">{{ $account->numero_compte }}</td>
                    <td class="px-6 py-4 font-semibold text-slate-900">{{ $account->member->prenom }} {{ $account->member->nom }}</td>
                    <td class="px-6 py-4 text-right font-bold text-slate-900">{{ number_format($account->solde, 2) }} HTG</td>
                    <td class="px-6 py-4">
                        @php
                            $badge = match ($account->statut) {
                                'actif' => 'bg-emerald-100 text-emerald-800 border-emerald-300',
                                'bloque' => 'bg-red-100 text-red-800 border-red-300',
                                default => 'bg-slate-200 text-slate-800 border-slate-300',
                            };
                        @endphp
                        <span class="px-3 py-1 rounded-full text-xs font-bold border {{ $badge }}">
                            {{ ucfirst($account->statut) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <a href="{{ route('accounts.show', $account) }}" class="inline-flex items-center gap-1 text-orange-600 font-bold hover:text-orange-700 hover:underline transition">
                            Voir &rarr;
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-6 py-12 text-center text-slate-500 font-medium bg-slate-50/50">
                        Aucun compte trouve.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-6">{{ $accounts->links() }}</div>
@endsection