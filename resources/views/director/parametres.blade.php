@extends('layouts.director')
@section('title', 'Parametres')
@section('content')

<div class="max-w-xl">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Parametres du compte</h2>
        <p class="text-sm font-medium text-slate-600 mt-0.5">
            Ces informations sont personnelles : seul vous pouvez les modifier.
            L'Administrateur n'a aucun acces pour changer votre mot de passe.
        </p>
    </div>

    <div class="bg-white rounded-2xl border border-slate-300 shadow-md overflow-hidden mb-6">
        <div class="px-6 py-4 bg-slate-100 border-b border-slate-300">
            <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Identite</h3>
        </div>
        <div class="p-6 space-y-4">
            <div>
                <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Nom</p>
                <p class="text-base font-bold text-slate-900 mt-0.5">{{ auth()->user()->name }}</p>
            </div>
            <div>
                <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Email</p>
                <p class="text-base font-bold text-slate-900 mt-0.5">{{ auth()->user()->email }}</p>
            </div>
        </div>
    </div>

    <form action="{{ route('director.motdepasse') }}" method="POST" class="bg-white rounded-2xl border border-slate-300 shadow-md overflow-hidden">
        @csrf

        <div class="px-6 py-4 bg-slate-100 border-b border-slate-300">
            <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Changer le mot de passe</h3>
        </div>
        <div class="p-6 space-y-5">
            <div>
                <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Mot de passe actuel</label>
                <input type="password" name="mot_de_passe_actuel" required
                       class="mt-1.5 w-full rounded-xl border border-slate-300 bg-slate-50 text-slate-900 text-sm px-4 py-2.5 focus:bg-white focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 transition outline-none">
            </div>
            <div>
                <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Nouveau mot de passe</label>
                <input type="password" name="nouveau_mot_de_passe" required minlength="8"
                       class="mt-1.5 w-full rounded-xl border border-slate-300 bg-slate-50 text-slate-900 text-sm px-4 py-2.5 focus:bg-white focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 transition outline-none">
                <p class="text-xs font-medium text-slate-500 mt-1">Minimum 8 caracteres.</p>
            </div>
            <div>
                <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Confirmer le nouveau mot de passe</label>
                <input type="password" name="nouveau_mot_de_passe_confirmation" required minlength="8"
                       class="mt-1.5 w-full rounded-xl border border-slate-300 bg-slate-50 text-slate-900 text-sm px-4 py-2.5 focus:bg-white focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 transition outline-none">
            </div>
        </div>
        <div class="px-6 py-4 bg-slate-50/80 border-t border-slate-300 flex justify-end">
            <button class="px-5 py-2.5 rounded-xl bg-orange-600 text-white text-sm font-semibold hover:bg-orange-700 shadow-sm active:scale-[0.98] transition duration-150">
                Mettre a jour le mot de passe
            </button>
        </div>
    </form>
</div>
@endsection