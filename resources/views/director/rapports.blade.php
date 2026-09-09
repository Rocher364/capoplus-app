@extends('layouts.director')
@section('title', 'Rapports')
@section('content')

<div class="flex items-center justify-between mb-6 no-print flex-wrap gap-4">
    <div>
        <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Rapports</h2>
        <p class="text-sm font-medium text-slate-600 mt-0.5">Memes chiffres exacts que ceux vus par l'Administrateur.</p>
    </div>
    <button onclick="window.print()" type="button"
            class="flex items-center gap-2 px-5 py-2.5 rounded-xl bg-slate-900 text-white text-sm font-semibold hover:bg-slate-800 shadow-sm active:scale-[0.98] transition duration-150">
        Imprimer
    </button>
</div>

<div class="flex flex-wrap gap-2.5 mb-6 no-print">
    <a href="{{ route('director.rapports', ['periode' => 'semaine']) }}"
       class="px-5 py-2.5 rounded-xl text-sm font-semibold transition duration-150 shadow-sm active:scale-[0.98] {{ $periode === 'semaine' ? 'bg-orange-600 text-white hover:bg-orange-700' : 'bg-white border border-slate-300 text-slate-700 hover:bg-slate-50' }}">
        Cette semaine
    </a>
    <a href="{{ route('director.rapports', ['periode' => 'mois']) }}"
       class="px-5 py-2.5 rounded-xl text-sm font-semibold transition duration-150 shadow-sm active:scale-[0.98] {{ $periode === 'mois' ? 'bg-orange-600 text-white hover:bg-orange-700' : 'bg-white border border-slate-300 text-slate-700 hover:bg-slate-50' }}">
        Ce mois
    </a>
    <a href="{{ route('director.rapports', ['periode' => 'annee']) }}"
       class="px-5 py-2.5 rounded-xl text-sm font-semibold transition duration-150 shadow-sm active:scale-[0.98] {{ $periode === 'annee' ? 'bg-orange-600 text-white hover:bg-orange-700' : 'bg-white border border-slate-300 text-slate-700 hover:bg-slate-50' }}">
        Cette annee
    </a>
</div>

@include('reports.partials.recherche', ['action' => route('director.rapports'), 'periode' => $periode])

@include('reports.partials.tableau', ['rapport' => $rapport, 'periode' => $periode])
@endsection