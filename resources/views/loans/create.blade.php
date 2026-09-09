@extends('layouts.app')
@section('title', 'Nouvelle demande de pret')
@section('content')

<div class="max-w-2xl">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Nouvelle demande de pret</h2>
        <p class="text-sm font-medium text-slate-600 mt-0.5">Un numero de pret unique sera genere automatiquement.</p>
    </div>

    <form action="{{ route('loans.store') }}" method="POST" class="bg-white rounded-2xl border border-slate-300 shadow-md overflow-hidden">
        @csrf

        <div class="px-6 py-4 bg-slate-100 border-b border-slate-300">
            <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Membre emprunteur</h3>
        </div>
        <div class="p-6 border-b border-slate-200">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Membre (doit avoir un compte actif)</label>
            <select name="member_id" required class="mt-1.5 w-full rounded-xl border border-slate-300 bg-slate-50 text-slate-900 text-sm px-4 py-2.5 focus:bg-white focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 transition outline-none">
                <option value="">-- Selectionner --</option>
                @foreach ($membres as $membre)
                    <option value="{{ $membre->id }}" @selected(old('member_id') == $membre->id)>
                        {{ $membre->prenom }} {{ $membre->nom }} ({{ $membre->numero_membre }})
                    </option>
                @endforeach
            </select>
            @if ($membres->isEmpty())
                <p class="text-xs font-bold text-amber-700 mt-2">Aucun membre avec un compte actif pour le moment.</p>
            @endif
        </div>

        <div class="px-6 py-4 bg-slate-100 border-b border-slate-300">
            <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Conditions du pret</h3>
        </div>
        <div class="p-6 space-y-5 border-b border-slate-200">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Montant demande (HTG)</label>
                    <input type="number" step="0.01" min="1" name="montant_demande" value="{{ old('montant_demande') }}" required
                           class="mt-1.5 w-full rounded-xl border border-slate-300 bg-slate-50 text-slate-900 text-sm px-4 py-2.5 focus:bg-white focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 transition outline-none">
                </div>
                <div>
                    <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Duree (mois)</label>
                    <input type="number" min="1" max="120" name="duree_mois" value="{{ old('duree_mois', 12) }}" required
                           class="mt-1.5 w-full rounded-xl border border-slate-300 bg-slate-50 text-slate-900 text-sm px-4 py-2.5 focus:bg-white focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 transition outline-none">
                </div>
            </div>

            <div>
                <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Taux d'interet annuel (%)</label>
                <input type="number" step="0.001" min="0" max="100" name="taux_interet" value="{{ old('taux_interet', 12) }}" required
                       class="mt-1.5 w-full rounded-xl border border-slate-300 bg-slate-50 text-slate-900 text-sm px-4 py-2.5 focus:bg-white focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 transition outline-none">
            </div>

            <div>
                <label class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-2 block">Methode de calcul de l'echeancier</label>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <label class="border border-slate-300 bg-slate-50 rounded-xl p-3.5 cursor-pointer has-[:checked]:bg-orange-50/80 has-[:checked]:border-orange-500 transition duration-150">
                        <input type="radio" name="methode_calcul" value="constant" class="hidden" checked>
                        <span class="block text-sm font-bold text-slate-900">Constant</span>
                        <span class="block text-xs font-medium text-slate-500 mt-0.5">Mensualite fixe (annuite)</span>
                    </label>
                    <label class="border border-slate-300 bg-slate-50 rounded-xl p-3.5 cursor-pointer has-[:checked]:bg-orange-50/80 has-[:checked]:border-orange-500 transition duration-150">
                        <input type="radio" name="methode_calcul" value="degressif" class="hidden">
                        <span class="block text-sm font-bold text-slate-900">Degressif</span>
                        <span class="block text-xs font-medium text-slate-500 mt-0.5">Capital constant</span>
                    </label>
                    <label class="border border-slate-300 bg-slate-50 rounded-xl p-3.5 cursor-pointer has-[:checked]:bg-orange-50/80 has-[:checked]:border-orange-500 transition duration-150">
                        <input type="radio" name="methode_calcul" value="simple" class="hidden">
                        <span class="block text-sm font-bold text-slate-900">Simple</span>
                        <span class="block text-xs font-medium text-slate-500 mt-0.5">Interet flat</span>
                    </label>
                </div>
            </div>

            <div>
                <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Type de taux</label>
                <select name="type_taux" class="mt-1.5 w-full rounded-xl border border-slate-300 bg-slate-50 text-slate-900 text-sm px-4 py-2.5 focus:bg-white focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 transition outline-none">
                    <option value="fixe" selected>Fixe</option>
                    <option value="degressif">Degressif</option>
                    <option value="palier">Palier</option>
                </select>
            </div>
        </div>

        <div class="px-6 py-4 bg-slate-100 border-b border-slate-300">
            <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Motif de la demande</h3>
        </div>
        <div class="p-6">
            <textarea name="motif" rows="3" required placeholder="Ex: fonds de roulement pour petit commerce..."
                      class="w-full rounded-xl border border-slate-300 bg-slate-50 text-slate-900 text-sm px-4 py-3 focus:bg-white focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 transition outline-none">{{ old('motif') }}</textarea>
        </div>

        <div class="px-6 py-4 bg-slate-50/80 border-t border-slate-300 flex items-center gap-4">
            <button class="px-5 py-2.5 rounded-xl bg-orange-600 text-white text-sm font-semibold hover:bg-orange-700 shadow-sm active:scale-[0.98] transition duration-150">
                Soumettre la demande
            </button>
            <a href="{{ route('loans.index') }}" class="text-sm font-semibold text-slate-600 hover:text-slate-900 transition">Annuler</a>
        </div>
    </form>
</div>
@endsection