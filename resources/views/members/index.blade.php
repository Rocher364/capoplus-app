@extends('layouts.app')
@section('title', 'Gestion des membres')
@section('content')

<div class="space-y-6">
    {{-- Header ak Stats rapid --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Membres & Clients</h1>
            <p class="text-xs font-semibold text-slate-500 mt-0.5">Gérez les fiches membres et leurs portefeuilles associés.</p>
        </div>
        <a href="{{ route('members.create') }}" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-orange-600 hover:bg-orange-700 text-white text-xs font-bold uppercase tracking-wider shadow-sm transition-all duration-150 active:scale-95">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
            Nouveau membre
        </a>
    </div>

    {{-- Mesaj Notifications --}}
{{-- Mesaj Erè (Sèlman yon sèl fwa) --}}
@if(session('error'))
    <div class="p-4 rounded-xl bg-rose-50 border border-rose-200/80 text-rose-800 text-sm font-semibold flex items-center gap-3">
        <svg class="w-5 h-5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span>{{ session('error') }}</span>
    </div>
@endif

    {{-- Filter & Search Bar --}}
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-3.5">
        <form method="GET" action="{{ route('members.index') }}" class="flex flex-col sm:flex-row gap-2.5">
            <div class="relative flex-1">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Rechercher par nom, téléphone, NIF/CIN, N° membre..."
                       class="w-full pl-10 pr-4 py-2 text-xs font-medium rounded-xl border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 transition outline-none">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold transition shrink-0">
                    Filtrer
                </button>
                @if(request('q'))
                    <a href="{{ route('members.index') }}" class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-semibold transition shrink-0 flex items-center">
                        Effacer
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Table Container --}}
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/70 border-b border-slate-200/80 text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                        <th class="py-3.5 px-4 whitespace-nowrap">N° Membre</th>
                        <th class="py-3.5 px-4 whitespace-nowrap">Membre</th>
                        <th class="py-3.5 px-4 whitespace-nowrap">Sexe</th>
                        <th class="py-3.5 px-4 whitespace-nowrap">NIF / CIN</th>
                        <th class="py-3.5 px-4 whitespace-nowrap">Contact</th>
                        <th class="py-3.5 px-4 whitespace-nowrap">Compte</th>
                        <th class="py-3.5 px-4 whitespace-nowrap text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs text-slate-700">
                    @forelse($members as $member)
                        <tr class="hover:bg-slate-50/60 transition-colors">
                            {{-- N° Membre --}}
                            <td class="py-3 px-4 font-mono font-semibold text-slate-900 whitespace-nowrap">
                                <span class="px-2 py-1 rounded-md bg-slate-100 border border-slate-200/60 text-slate-700">
                                    {{ $member->numero_membre }}
                                </span>
                            </td>

                            {{-- Nom & Adresse --}}
                            <td class="py-3 px-4 whitespace-nowrap">
                                <div class="font-bold text-slate-900">{{ $member->nom_complet }}</div>
                                @if($member->adresse)
                                    <div class="text-[11px] text-slate-400 max-w-[180px] truncate" title="{{ $member->adresse }}">
                                        {{ $member->adresse }}
                                    </div>
                                @endif
                            </td>

                            {{-- Sexe --}}
                            <td class="py-3 px-4 whitespace-nowrap">
                                @if($member->sexe === 'M')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200/60">
                                        M
                                    </span>
                                @elseif($member->sexe === 'F')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-pink-50 text-pink-700 border border-pink-200/60">
                                        F
                                    </span>
                                @else
                                    <span class="text-slate-300">-</span>
                                @endif
                            </td>

                            {{-- NIF / CIN --}}
                            <td class="py-3 px-4 font-mono text-slate-600 whitespace-nowrap">
                                {{ $member->nif_cin ?? '-' }}
                            </td>

                            {{-- Contact (Téléphone + Email) --}}
                            <td class="py-3 px-4 whitespace-nowrap">
                                <div class="font-medium text-slate-900">{{ $member->telephone ?? '-' }}</div>
                                @if($member->email)
                                    <div class="text-[11px] text-slate-400 max-w-[160px] truncate" title="{{ $member->email }}">
                                        {{ $member->email }}
                                    </div>
                                @endif
                            </td>

                           {{-- Compte Financier (Klikab ak wout ki dwat la) --}}
<td class="py-3 px-4 font-mono whitespace-nowrap">
    @if($member->account)
        <a href="{{ route('accounts.index', ['q' => $member->account->numero_compte]) }}" 
           class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-orange-50 text-orange-700 border border-orange-200/60 font-bold text-[11px] hover:bg-orange-100 hover:text-orange-800 transition shadow-sm" 
           title="Gérer ce compte">
            <span class="w-1.5 h-1.5 rounded-full bg-orange-500"></span>
            {{ $member->account->numero_compte }}
        </a>
    @else
        <span class="text-slate-400 italic">Aucun</span>
    @endif
</td>

                            {{-- Actions --}}
                            <td class="py-3 px-4 text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-2">
                                    <a href="{{ route('members.edit', $member) }}" class="p-1.5 rounded-lg text-slate-500 hover:text-slate-900 hover:bg-slate-100 transition" title="Éditer">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </a>

                                    <form action="{{ route('members.destroy', $member) }}" method="POST" onsubmit="return confirm('Voulez-vous supprimer le membre {{ $member->nom_complet }} ?');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 rounded-lg text-rose-500 hover:text-rose-700 hover:bg-rose-50 transition" title="Supprimer">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 px-4 text-center">
                                <div class="max-w-xs mx-auto text-center space-y-1">
                                    <p class="text-sm font-bold text-slate-700">Aucun membre trouvé</p>
                                    <p class="text-xs text-slate-400">Essayez de modifier votre recherche ou ajoutez un nouveau membre.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($members->hasPages())
            <div class="px-5 py-3.5 border-t border-slate-100 bg-slate-50/50">
                {{ $members->links() }}
            </div>
        @endif
    </div>
</div>

@endsection