@extends('layouts.app')
@section('title', 'Nouveau membre')
@section('content')

<div class="max-w-2xl">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Nouveau membre</h2>
        <p class="text-sm font-medium text-slate-600 mt-0.5">Un numéro de membre unique sera généré automatiquement.</p>
    </div>

    <form action="{{ route('members.store') }}" method="POST" id="memberForm" class="bg-white rounded-2xl border border-slate-300 shadow-md overflow-hidden">
        @csrf

        {{-- Section Identité --}}
        <div class="px-6 py-4 bg-slate-100 border-b border-slate-300">
            <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Identité</h3>
        </div>
        <div class="p-6 space-y-5 border-b border-slate-200">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Prénom <span class="text-rose-500">*</span></label>
                    <input type="text" name="prenom" value="{{ old('prenom') }}" required
                           class="mt-1.5 w-full rounded-xl border @error('prenom') border-rose-500 @else border-slate-300 @enderror bg-slate-50 text-slate-900 text-sm px-4 py-2.5 focus:bg-white focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 transition outline-none">
                    @error('prenom')
                        <p class="mt-1 text-xs text-rose-500 font-semibold">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Nom <span class="text-rose-500">*</span></label>
                    <input type="text" name="nom" value="{{ old('nom') }}" required
                           class="mt-1.5 w-full rounded-xl border @error('nom') border-rose-500 @else border-slate-300 @enderror bg-slate-50 text-slate-900 text-sm px-4 py-2.5 focus:bg-white focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 transition outline-none">
                    @error('nom')
                        <p class="mt-1 text-xs text-rose-500 font-semibold">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">NIF / CIN <span class="text-slate-400 font-normal">(Optionnel)</span></label>
                    <input type="text" name="nif_cin" value="{{ old('nif_cin') }}" placeholder="000-000-000-0"
                           class="mt-1.5 w-full rounded-xl border @error('nif_cin') border-rose-500 @else border-slate-300 @enderror bg-slate-50 text-slate-900 text-sm px-4 py-2.5 focus:bg-white focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 transition outline-none">
                    @error('nif_cin')
                        <p class="mt-1 text-xs text-rose-500 font-semibold">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Date de naissance <span class="text-slate-400 font-normal">(Optionnel)</span></label>
                    <input type="date" name="date_naissance" value="{{ old('date_naissance') }}"
                           class="mt-1.5 w-full rounded-xl border @error('date_naissance') border-rose-500 @else border-slate-300 @enderror bg-slate-50 text-slate-900 text-sm px-4 py-2.5 focus:bg-white focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 transition outline-none">
                    @error('date_naissance')
                        <p class="mt-1 text-xs text-rose-500 font-semibold">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Sexe <span class="text-slate-400 font-normal">(Optionnel)</span></label>
                    <select name="sexe" class="mt-1.5 w-full rounded-xl border @error('sexe') border-rose-500 @else border-slate-300 @enderror bg-slate-50 text-slate-900 text-sm px-4 py-2.5 focus:bg-white focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 transition outline-none">
                        <option value="">-</option>
                        <option value="M" @selected(old('sexe') === 'M')>Masculin</option>
                        <option value="F" @selected(old('sexe') === 'F')>Féminin</option>
                        <option value="Autre" @selected(old('sexe') === 'Autre')>Autre</option>
                    </select>
                    @error('sexe')
                        <p class="mt-1 text-xs text-rose-500 font-semibold">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Section Coordonnées --}}
        <div class="px-6 py-4 bg-slate-100 border-b border-slate-300">
            <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Coordonnées</h3>
        </div>
        <div class="p-6 space-y-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Téléphone <span class="text-slate-400 font-normal">(Optionnel)</span></label>
                    <input type="text" name="telephone" value="{{ old('telephone') }}" placeholder="+509..."
                           class="mt-1.5 w-full rounded-xl border @error('telephone') border-rose-500 @else border-slate-300 @enderror bg-slate-50 text-slate-900 text-sm px-4 py-2.5 focus:bg-white focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 transition outline-none">
                    @error('telephone')
                        <p class="mt-1 text-xs text-rose-500 font-semibold">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Email <span class="text-slate-400 font-normal">(Optionnel)</span></label>
                    <input type="email" name="email" value="{{ old('email') }}" placeholder="exemple@email.com"
                           class="mt-1.5 w-full rounded-xl border @error('email') border-rose-500 @else border-slate-300 @enderror bg-slate-50 text-slate-900 text-sm px-4 py-2.5 focus:bg-white focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 transition outline-none">
                    @error('email')
                        <p class="mt-1 text-xs text-rose-500 font-semibold">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            <div>
                <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Adresse <span class="text-slate-400 font-normal">(Optionnel)</span></label>
                <textarea name="adresse" rows="2"
                          class="mt-1.5 w-full rounded-xl border @error('adresse') border-rose-500 @else border-slate-300 @enderror bg-slate-50 text-slate-900 text-sm px-4 py-3 focus:bg-white focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 transition outline-none">{{ old('adresse') }}</textarea>
                @error('adresse')
                    <p class="mt-1 text-xs text-rose-500 font-semibold">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- Actions --}}
        <div class="px-6 py-4 bg-slate-50/80 border-t border-slate-300 flex items-center gap-4">
            <button type="submit" id="submitBtn" class="px-5 py-2.5 rounded-xl bg-orange-600 text-white text-sm font-semibold hover:bg-orange-700 shadow-sm active:scale-[0.98] transition duration-150 flex items-center gap-2">
                <span>Créer le membre</span>
            </button>
            <a href="{{ route('members.index') }}" class="text-sm font-semibold text-slate-600 hover:text-slate-900 transition">Annuler</a>
        </div>
    </form>
</div>

<script>
    // Anpeche doub-klik lè w ap soumèt fòm lan
    document.getElementById('memberForm').addEventListener('submit', function() {
        const btn = document.getElementById('submitBtn');
        btn.disabled = true;
        btn.classList.add('opacity-50', 'cursor-not-allowed');
        btn.innerText = 'Enregistrement...';
    });
</script>

@endsection