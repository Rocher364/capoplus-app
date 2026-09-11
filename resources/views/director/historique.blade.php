@extends('layouts.director')
@section('title', 'Historique')
@section('content')

<div class="mb-6">
    <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Historique complet</h2>
    <p class="text-sm font-medium text-slate-600 mt-0.5">Toutes les actions enregistrees dans CASH, depuis le debut. Lecture seule.</p>
</div>

<form method="GET" action="{{ route('director.historique') }}" class="mb-6 flex flex-wrap gap-2.5">
    <input type="text" name="q" value="{{ request('q') }}" placeholder="Rechercher : action, agent, ou type (membre, compte, pret...)"
           class="flex-1 min-w-[260px] rounded-xl border border-slate-300 bg-slate-50 text-slate-900 placeholder-slate-400 text-sm px-4 py-2.5 focus:bg-white focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 transition outline-none">
    <input type="date" name="date" value="{{ request('date') }}"
           class="rounded-xl border border-slate-300 bg-slate-50 text-slate-900 text-sm px-4 py-2.5 focus:bg-white focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 transition outline-none">
    <button class="px-5 py-2.5 rounded-xl bg-slate-900 text-white text-sm font-semibold hover:bg-slate-800 shadow-sm active:scale-[0.98] transition duration-150">Rechercher</button>
    @if (request('date') || request('q'))
        <a href="{{ route('director.historique') }}" class="px-5 py-2.5 rounded-xl border border-slate-300 bg-white text-slate-700 text-sm font-semibold hover:bg-slate-50 transition">Effacer</a>
    @endif
</form>

<div class="bg-slate-100 rounded-2xl border border-slate-300 shadow-md overflow-hidden">
    <div class="w-full overflow-x-auto my-2"><table class="w-full text-sm text-left">
        <thead class="bg-slate-200/80 text-slate-800 uppercase text-xs font-bold tracking-wider border-b border-slate-300">
            <tr>
                <th class="px-6 py-3.5">Date</th>
                <th class="px-6 py-3.5">Heure</th>
                <th class="px-6 py-3.5">Action</th>
                <th class="px-6 py-3.5">Concerne</th>
                <th class="px-6 py-3.5">Effectue par</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-300 bg-slate-50/50">
            @forelse ($activites as $a)
                <tr class="hover:bg-white transition duration-150">
                    <td class="px-6 py-3.5 font-medium text-slate-700 text-xs">{{ $a->created_at->format('d/m/Y') }}</td>
                    <td class="px-6 py-3.5 font-mono text-xs font-bold text-slate-600">{{ $a->created_at->format('H:i:s') }}</td>
                    <td class="px-6 py-3.5">
                        <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-slate-200 text-slate-800 border border-slate-300 inline-block">
                            {{ str_replace('.', ' - ', $a->action) }}
                        </span>
                    </td>
                    <td class="px-6 py-3.5 text-slate-700 font-semibold">
                        {{ class_basename($a->auditable_type ?? '') ?: '-' }}
                        @if ($a->auditable_id) <span class="text-slate-500 font-mono text-xs">#{{ $a->auditable_id }}</span> @endif
                    </td>
                    <td class="px-6 py-3.5 text-slate-900 font-bold">{{ $a->user->name ?? 'Systeme' }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-6 py-12 text-center text-slate-500 font-medium bg-slate-50/50">Aucune activite trouvee.</td></tr>
            @endforelse
        </tbody>
    </table></div>
</div>

<div class="mt-6">{{ $activites->links() }}</div>
@endsection
