@extends('layouts.director')
@section('title', 'Utilisateurs')
@section('content')

<div class="mb-6 max-w-2xl">
    <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Utilisateurs</h2>
    <p class="text-sm font-medium text-slate-600 mt-0.5">
        En tant que Directeur, vous pouvez reinitialiser le mot de passe de n'importe quel
        compte Administrateur ou Agent. L'inverse n'existe pas : personne ne peut modifier votre mot de passe a vous.
    </p>
</div>

<div class="space-y-4 max-w-3xl">
    @forelse ($utilisateurs as $u)
        <div class="bg-white rounded-2xl border border-slate-300 shadow-md overflow-hidden">
            <div class="px-6 py-4 flex items-center justify-between flex-wrap gap-3">
                <div>
                    <p class="font-bold text-slate-900">{{ $u->name }}</p>
                    <p class="text-sm font-medium text-slate-500">{{ $u->email }}</p>
                </div>
                <span class="px-3 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200 uppercase tracking-wider">
                    {{ $u->role->label() }}
                </span>
            </div>
            <form action="{{ route('director.utilisateurs.motdepasse', $u) }}" method="POST"
                  class="px-6 py-4 bg-slate-50/80 border-t border-slate-300 flex flex-wrap items-end gap-3">
                @csrf
                <div class="flex-1 min-w-[180px]">
                    <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Nouveau mot de passe</label>
                    <input type="password" name="nouveau_mot_de_passe" required minlength="8"
                           class="mt-1.5 w-full rounded-xl border border-slate-300 bg-white text-slate-900 text-sm px-4 py-2.5 focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 transition outline-none">
                </div>
                <div class="flex-1 min-w-[180px]">
                    <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Confirmer</label>
                    <input type="password" name="nouveau_mot_de_passe_confirmation" required minlength="8"
                           class="mt-1.5 w-full rounded-xl border border-slate-300 bg-white text-slate-900 text-sm px-4 py-2.5 focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 transition outline-none">
                </div>
                <button class="px-5 py-2.5 rounded-xl bg-orange-600 text-white text-sm font-semibold hover:bg-orange-700 shadow-sm active:scale-[0.98] transition duration-150"
                        onclick="return confirm('Reinitialiser le mot de passe de {{ $u->name }} ?');">
                    Reinitialiser
                </button>
            </form>
        </div>
    @empty
        <div class="bg-white rounded-2xl border border-slate-300 p-8 text-center">
            <p class="text-sm font-medium text-slate-500">Aucun utilisateur Admin/Agent pour le moment.</p>
        </div>
    @endforelse
</div>
@endsection