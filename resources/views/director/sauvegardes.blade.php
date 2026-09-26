@extends('layouts.director')
@section('title', 'Sauvegardes & Restauration')
@section('content')

<div class="flex items-center justify-between mb-6 flex-wrap gap-4 no-print">
    <div>
        <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Gestion des Sauvegardes & Backup</h2>
        <p class="text-sm font-medium text-slate-600 mt-0.5">Exportation, importation, restauration et planification automatique sécurisée.</p>
    </div>
    <div class="flex items-center gap-2">
        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold {{ $schedule['enabled'] ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : 'bg-slate-200 text-slate-700' }}">
            <span class="w-2 h-2 rounded-full {{ $schedule['enabled'] ? 'bg-emerald-500 animate-pulse' : 'bg-slate-400' }}"></span>
            {{ $schedule['enabled'] ? 'Sauvegarde auto ACTIVE (' . ucfirst($schedule['frequency']) . ' à ' . $schedule['time'] . ')' : 'Sauvegarde auto DÉSACTIVÉE' }}
        </span>
    </div>
</div>

@if ($errors->has('backup'))
    <div class="mb-5 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-sm p-4 font-semibold shadow-sm">
        ⚠️ {{ $errors->first('backup') }}
    </div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    {{-- CARTE 1 : CREER UNE SAUVEGARDE MANUELLE --}}
    <div class="bg-white rounded-2xl border border-slate-300 shadow-md p-6 flex flex-col justify-between">
        <div>
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-xl bg-orange-100 text-orange-600 flex items-center justify-center font-bold text-lg">💾</div>
                <div>
                    <h3 class="font-bold text-slate-900 text-sm uppercase tracking-wider">Sauvegarde instantanée</h3>
                    <p class="text-xs text-slate-500">Exporter l'état actuel de la base de données</p>
                </div>
            </div>
            <p class="text-xs text-slate-600 leading-relaxed mb-4">
                Génère un instantané complet et cryptographiquement scellé (SHA-256) contenant les membres, comptes, transactions, prêts et la piste d'audit.
            </p>
            <form action="{{ route('director.sauvegardes.creer') }}" method="POST" class="space-y-3">
                @csrf
                <div>
                    <label class="text-[11px] font-bold text-slate-700 uppercase tracking-wider block">Description (optionnelle)</label>
                    <input type="text" name="description" placeholder="Ex: Avant clôture mensuelle..."
                           class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-50 text-slate-900 text-xs px-3.5 py-2.5 focus:bg-white focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 transition outline-none">
                </div>
                <button type="submit"
                        class="w-full py-2.5 rounded-xl bg-orange-600 text-white text-xs font-bold uppercase tracking-wider hover:bg-orange-700 shadow-sm active:scale-[0.98] transition">
                    Créer la sauvegarde maintenant
                </button>
            </form>
        </div>
    </div>

    {{-- CARTE 2 : IMPORTER UNE SAUVEGARDE EXTERNE --}}
    <div class="bg-white rounded-2xl border border-slate-300 shadow-md p-6 flex flex-col justify-between">
        <div>
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-lg">📥</div>
                <div>
                    <h3 class="font-bold text-slate-900 text-sm uppercase tracking-wider">Importer une sauvegarde</h3>
                    <p class="text-xs text-slate-500">Téléverser une archive CAPO+ (.json)</p>
                </div>
            </div>
            <p class="text-xs text-slate-600 leading-relaxed mb-4">
                Importez un fichier de sauvegarde précédemment exporté. Le fichier sera validé et vérifié avant toute possibilité de restauration.
            </p>
            <form action="{{ route('director.sauvegardes.importer') }}" method="POST" enctype="multipart/form-data" class="space-y-3">
                @csrf
                <div>
                    <label class="text-[11px] font-bold text-slate-700 uppercase tracking-wider block">Fichier de sauvegarde (.json)</label>
                    <input type="file" name="fichier_sauvegarde" accept=".json" required
                           class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-50 text-slate-900 text-xs px-3 py-2 file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-slate-200 file:text-slate-700 hover:file:bg-slate-300">
                </div>
                <button type="submit"
                        class="w-full py-2.5 rounded-xl bg-slate-900 text-white text-xs font-bold uppercase tracking-wider hover:bg-slate-800 shadow-sm active:scale-[0.98] transition">
                    Importer le fichier
                </button>
            </form>
        </div>
    </div>

    {{-- CARTE 3 : PLANIFICATION AUTOMATIQUE --}}
    <div class="bg-white rounded-2xl border border-slate-300 shadow-md p-6">
        <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center font-bold text-lg">⏰</div>
            <div>
                <h3 class="font-bold text-slate-900 text-sm uppercase tracking-wider">Planification automatique</h3>
                <p class="text-xs text-slate-500">Programmer l'heure et la fréquence</p>
            </div>
        </div>

        <form action="{{ route('director.sauvegardes.planification') }}" method="POST" class="space-y-3">
            @csrf
            <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-50 border border-slate-200">
                <label for="auto_backup_enabled" class="text-xs font-bold text-slate-800 cursor-pointer">Activer les sauvegardes auto</label>
                <input type="checkbox" name="enabled" id="auto_backup_enabled" value="1" {{ $schedule['enabled'] ? 'checked' : '' }}
                       class="rounded border-slate-300 text-orange-600 focus:ring-orange-500 w-4 h-4 cursor-pointer">
            </div>

            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="text-[10px] font-bold text-slate-700 uppercase tracking-wider block">Fréquence</label>
                    <select name="frequency" class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-50 text-slate-900 text-xs px-2.5 py-2 outline-none">
                        <option value="daily" {{ $schedule['frequency'] === 'daily' ? 'selected' : '' }}>Chaque jour</option>
                        <option value="weekly" {{ $schedule['frequency'] === 'weekly' ? 'selected' : '' }}>Chaque semaine</option>
                        <option value="monthly" {{ $schedule['frequency'] === 'monthly' ? 'selected' : '' }}>Chaque mois</option>
                    </select>
                </div>
                <div>
                    <label class="text-[10px] font-bold text-slate-700 uppercase tracking-wider block">Heure d'exécution</label>
                    <input type="time" name="time" value="{{ $schedule['time'] }}" required
                           class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-50 text-slate-900 text-xs px-2.5 py-2 outline-none">
                </div>
            </div>

            <div>
                <label class="text-[10px] font-bold text-slate-700 uppercase tracking-wider block">Garder les N dernières copies</label>
                <input type="number" name="keep_last" min="1" max="100" value="{{ $schedule['keep_last'] }}"
                       class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-50 text-slate-900 text-xs px-3 py-2 outline-none">
            </div>

            <button type="submit"
                    class="w-full py-2.5 rounded-xl bg-emerald-600 text-white text-xs font-bold uppercase tracking-wider hover:bg-emerald-700 shadow-sm active:scale-[0.98] transition">
                Enregistrer la planification
            </button>
        </form>
    </div>
</div>

{{-- SECTION 4 : HISTORIQUE DES SAUVEGARDES DISPONIBLES --}}
<div class="bg-white rounded-2xl border border-slate-300 shadow-md overflow-hidden print-card">
    <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between">
        <div>
            <h3 class="font-bold text-sm uppercase tracking-wider">Sauvegardes disponibles sur le serveur</h3>
            <p class="text-xs text-slate-300 mt-0.5">Téléchargement sécurisé, inspection et restauration contrôlée</p>
        </div>
        <span class="text-xs font-bold bg-orange-600 px-3 py-1 rounded-full text-white">{{ count($backups) }} archive(s)</span>
    </div>

    <div class="w-full overflow-x-auto">
        <table class="w-full text-sm text-left border-collapse">
            <thead class="bg-slate-100 text-slate-700 uppercase text-[11px] font-bold tracking-wider border-b border-slate-200">
                <tr>
                    <th class="px-5 py-3.5 whitespace-nowrap">Date & Heure</th>
                    <th class="px-5 py-3.5 whitespace-nowrap">Nom du fichier</th>
                    <th class="px-5 py-3.5 whitespace-nowrap">Description & Auteur</th>
                    <th class="px-5 py-3.5 text-right whitespace-nowrap">Taille</th>
                    <th class="px-5 py-3.5 text-center whitespace-nowrap">Contenu (Lignes)</th>
                    <th class="px-5 py-3.5 text-right whitespace-nowrap no-print">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-xs text-slate-800">
                @forelse ($backups as $b)
                    <tr class="hover:bg-slate-50/80 transition duration-150">
                        <td class="px-5 py-3.5 font-mono text-slate-600 whitespace-nowrap">
                            {{ $b['mtime']->format('d/m/Y H:i:s') }}
                            <div class="text-[10px] text-slate-400">({{ $b['mtime']->diffForHumans() }})</div>
                        </td>
                        <td class="px-5 py-3.5 font-mono font-bold text-slate-900 whitespace-nowrap">
                            {{ $b['filename'] }}
                        </td>
                        <td class="px-5 py-3.5 text-slate-700 whitespace-nowrap">
                            <div class="font-semibold">{{ $b['meta']['description'] ?? 'Sauvegarde' }}</div>
                            <div class="text-[10px] text-slate-500 font-medium">Par : {{ $b['meta']['created_by'] ?? 'Système' }}</div>
                        </td>
                        <td class="px-5 py-3.5 text-right font-mono font-bold text-slate-700 whitespace-nowrap">
                            {{ $b['size_formatted'] }}
                        </td>
                        <td class="px-5 py-3.5 text-center whitespace-nowrap">
                            @if (!empty($b['meta']['counts']))
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                    {{ array_sum($b['meta']['counts']) }} enregistrements
                                </span>
                            @else
                                <span class="text-slate-400 text-[10px]">-</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5 text-right whitespace-nowrap no-print">
                            <div class="inline-flex items-center gap-2">
                                {{-- BOUTON TELECHARGER --}}
                                <a href="{{ route('director.sauvegardes.telecharger', $b['filename']) }}"
                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                    Télécharger
                                </a>

                                {{-- BOUTON SUPPRIMER --}}
                                <form action="{{ route('director.sauvegardes.supprimer', $b['filename']) }}" method="POST"
                                      onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer définitivement ce fichier de sauvegarde ?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="p-1.5 rounded-lg hover:bg-rose-50 text-rose-600 transition" title="Supprimer">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-slate-500 font-medium bg-slate-50/50">
                            Aucune archive de sauvegarde générée pour le moment. Cliquez sur "Créer la sauvegarde maintenant" ci-dessus pour faire votre première copie.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
