@extends('layouts.director')
@section('title', 'Parametres')
@section('content')

<div class="max-w-3xl">
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
                       class="mt-1.5 w-full rounded-xl border border-slate-600 bg-slate-100 text-slate-950 text-sm px-4 py-2.5 placeholder-slate-600 focus:border-slate-900 focus:bg-white focus:ring-2 focus:ring-slate-500/30 transition outline-none">
            </div>
            <div>
                <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Nouveau mot de passe</label>
                <input type="password" name="nouveau_mot_de_passe" required minlength="8"
                       class="mt-1.5 w-full rounded-xl border border-slate-600 bg-slate-100 text-slate-950 text-sm px-4 py-2.5 placeholder-slate-600 focus:border-slate-900 focus:bg-white focus:ring-2 focus:ring-slate-500/30 transition outline-none">
                <p class="text-xs font-medium text-slate-500 mt-1">Minimum 8 caracteres.</p>
            </div>
            <div>
                <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Confirmer le nouveau mot de passe</label>
                <input type="password" name="nouveau_mot_de_passe_confirmation" required minlength="8"
                       class="mt-1.5 w-full rounded-xl border border-slate-600 bg-slate-100 text-slate-950 text-sm px-4 py-2.5 placeholder-slate-600 focus:border-slate-900 focus:bg-white focus:ring-2 focus:ring-slate-500/30 transition outline-none">
            </div>
        </div>
        <div class="px-6 py-4 bg-slate-50/80 border-t border-slate-300 flex justify-end">
            <button class="px-5 py-2.5 rounded-xl bg-amber-400 text-slate-950 text-sm font-semibold hover:bg-amber-500 shadow-sm active:scale-[0.98] transition duration-150">
                Mettre a jour le mot de passe
            </button>
        </div>
    </form>

    <section class="mt-6 rounded-2xl border border-slate-300 bg-white p-6 shadow-md">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Administration</p>
                <h3 class="mt-1 text-lg font-bold text-slate-900">Administrateur</h3>
                <p class="mt-1 text-sm text-slate-600">Reinitialisez le mot de passe d'un compte Administrateur ou Agent.</p>
            </div>
            <a href="{{ route('director.utilisateurs') }}" class="inline-flex items-center justify-center rounded-xl bg-amber-400 px-4 py-2.5 text-sm font-bold text-slate-950 shadow-sm transition hover:bg-amber-500">
                Ouvrir pour chanjer le mot de passe
            </a>
        </div>
    </section>

    <section class="mt-8 overflow-hidden rounded-2xl border border-rose-400 bg-white shadow-lg shadow-rose-100/60">
        <div class="border-b border-slate-800 bg-slate-900 px-6 py-5 text-white">
            <div class="flex items-start gap-4">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-rose-500/20 text-rose-200 ring-1 ring-rose-300/30" aria-hidden="true">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v4m0 4h.01M10.29 3.86l-7.12 12a2 2 0 001.72 3h14.22a2 2 0 001.72-3l-7.12-12a2 2 0 00-3.44 0z"/></svg>
                </div>
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-emerald-300">Zone critique</p>
                    <h3 class="mt-1 text-lg font-extrabold tracking-tight">Effacement securise des donnees</h3>
                    <p class="mt-1 text-sm leading-6 text-white/90">Operation reservee au Directeur. Elle est irreversible et doit etre effectuee uniquement apres verification.</p>
                </div>
            </div>
        </div>

        <div class="p-6">
            <div class="grid gap-3 sm:grid-cols-3 mb-6">
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-700">Etape 01</p>
                    <p class="mt-1 text-sm font-bold text-slate-900">Backup automatique</p>
                    <p class="mt-1 text-xs leading-5 text-slate-700">Une copie est creee avant toute suppression.</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-700">Etape 02</p>
                    <p class="mt-1 text-sm font-bold text-slate-900">Re-authentification</p>
                    <p class="mt-1 text-xs leading-5 text-slate-700">Votre mot de passe actuel est verifie.</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-700">Etape 03</p>
                    <p class="mt-1 text-sm font-bold text-slate-900">Audit conserve</p>
                    <p class="mt-1 text-xs leading-5 text-slate-700">Les utilisateurs et journaux restent proteges.</p>
                </div>
            </div>

            <form action="{{ route('director.purger-donnees') }}" method="POST" class="space-y-5" onsubmit="return confirm('Cette action est irreversible. Une sauvegarde sera creee avant effacement. Continuer ?');">
                @csrf
                <div class="grid gap-5 md:grid-cols-2">
                    <label class="block text-sm font-bold text-slate-950">
                        Mot de passe actuel
                        <input type="password" name="mot_de_passe_actuel" required autocomplete="current-password" class="mt-2 w-full rounded-xl border border-slate-600 bg-slate-100 px-4 py-3 text-slate-950 placeholder-slate-600 shadow-sm outline-none transition focus:border-slate-900 focus:bg-white focus:ring-4 focus:ring-slate-500/20">
                    </label>
                    <label class="block text-sm font-bold text-slate-950">
                        Confirmation obligatoire
                        <span class="mt-2 block rounded-lg border border-emerald-300 bg-emerald-50 px-3 py-2 font-mono text-xs font-extrabold tracking-wide text-emerald-950">EFFACER LES DONNEES</span>
                        <input type="text" name="confirmation" required autocomplete="off" placeholder="Recopiez le texte ci-dessus" class="mt-2 w-full rounded-xl border border-slate-600 bg-slate-100 px-4 py-3 font-mono text-sm text-slate-950 placeholder-slate-600 shadow-sm outline-none transition focus:border-slate-900 focus:bg-white focus:ring-4 focus:ring-slate-500/20">
                    </label>
                </div>
                <div class="flex flex-col gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-xs leading-5 text-slate-700">Seront effaces : membres, comptes, transactions, prets et remboursements.</p>
                    <button type="submit" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-amber-400 px-5 py-3 text-sm font-bold text-slate-950 shadow-md shadow-amber-900/20 transition hover:bg-amber-500 focus:outline-none focus:ring-4 focus:ring-amber-500/30">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        Effacer les donnees
                    </button>
                </div>
            </form>
        </div>
    </section>
</div>
@endsection
