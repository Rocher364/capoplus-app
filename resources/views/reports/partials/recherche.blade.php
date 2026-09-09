<form method="GET" action="{{ $action }}" class="mb-5 flex flex-wrap items-end gap-3 no-print">
    <input type="hidden" name="periode" value="personnalise">
    <div>
        <label class="text-xs font-bold text-slate-700 uppercase tracking-wider block mb-1.5">Du</label>
        <input type="date" name="debut" value="{{ request('debut') }}"
               onchange="this.form.submit()"
               class="rounded-xl border border-slate-300 bg-white text-slate-900 text-sm px-3.5 py-2 focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 transition outline-none shadow-sm">
    </div>
    <div>
        <label class="text-xs font-bold text-slate-700 uppercase tracking-wider block mb-1.5">Au</label>
        <input type="date" name="fin" value="{{ request('fin') }}"
               onchange="this.form.submit()"
               class="rounded-xl border border-slate-300 bg-white text-slate-900 text-sm px-3.5 py-2 focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 transition outline-none shadow-sm">
    </div>
    <button class="px-5 py-2 rounded-xl bg-slate-900 text-white text-sm font-semibold hover:bg-slate-800 shadow-sm transition duration-150 active:scale-95">
        Rechercher
    </button>
    @if ($periode === 'personnalise')
        <a href="{{ $action }}" class="px-4 py-2 rounded-xl border border-slate-300 bg-white text-slate-700 text-sm font-semibold hover:bg-slate-100 transition shadow-sm">
            Effacer
        </a>
    @endif
</form>