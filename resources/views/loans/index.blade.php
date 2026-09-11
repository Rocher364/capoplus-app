@extends('layouts.app')
@section('title', 'Prets')
@section('content')

<div class="flex items-center justify-between mb-6 flex-wrap gap-4">
    <div>
        <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Prets</h2>
        <p class="text-sm font-medium text-slate-600 mt-0.5"><span class="font-bold text-slate-800">{{ $loans->total() }}</span> pret(s) au total</p>
    </div>
    <a href="{{ route('loans.create') }}" class="px-5 py-2.5 rounded-xl bg-orange-600 text-white text-sm font-semibold hover:bg-orange-700 shadow-sm active:scale-[0.98] transition duration-150">
        Nouvelle demande de pret
    </a>
</div>

<div class="bg-slate-100 rounded-2xl border border-slate-300 shadow-md overflow-hidden">
    <div class="w-full overflow-x-auto my-2"><table class="w-full text-sm text-left">
        <thead class="bg-slate-200/80 text-slate-800 uppercase text-xs font-bold tracking-wider border-b border-slate-300">
            <tr>
                <th class="px-6 py-3.5">N&deg; pret</th>
                <th class="px-6 py-3.5">Membre</th>
                <th class="px-6 py-3.5 text-right">Montant</th>
                <th class="px-6 py-3.5">Statut</th>
                <th class="px-6 py-3.5">Demande le</th>
                <th class="px-6 py-3.5 text-right">Action</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-300 bg-slate-50/50">
            @forelse ($loans as $loan)
                <tr class="hover:bg-white transition duration-150">
                    <td class="px-6 py-4 font-mono text-xs font-bold text-slate-600 whitespace-nowrap">{{ $loan->numero_pret }}</td>
                    <td class="px-6 py-4 font-bold text-slate-900">{{ $loan->member->prenom }} {{ $loan->member->nom }}</td>
                    <td class="px-6 py-4 text-right font-bold text-slate-900 whitespace-nowrap">{{ number_format($loan->montant_demande, 2) }} <span class="text-xs text-slate-500 font-semibold">HTG</span></td>
                    <td class="px-6 py-4">
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
                        <span class="px-3 py-1 rounded-full text-xs font-bold border inline-block {{ $badge }}">
                            {{ $loan->statut->label() ?? $loan->statut }}
                        </span>
                    </td>
                    <td class="px-6 py-4 font-medium text-slate-600 text-xs whitespace-nowrap">{{ $loan->date_demande->format('d/m/Y') }}</td>
                    <td class="px-6 py-4 text-right whitespace-nowrap">
                        <a href="{{ route('loans.show', $loan) }}" class="inline-flex items-center gap-1 text-orange-600 font-bold hover:text-orange-700 transition">
                            Voir <span>&rarr;</span>
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-slate-500 font-medium bg-slate-50/50">Aucun pret pour le moment.</td>
                </tr>
            @endforelse
        </tbody>
    </table></div>
</div>

<div class="mt-6">{{ $loans->links() }}</div>
@endsection

