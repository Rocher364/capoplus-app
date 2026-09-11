<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CASH Direction - @yield('title', 'Rapport du jour')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print { display: none !important; }
            aside, header.app-header { display: none !important; }
            main { padding: 0 !important; }
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen antialiased">
    <div class="flex min-h-screen">
        <aside class="hidden md:flex w-64 bg-slate-950 text-slate-100 flex-col shrink-0 no-print">
            <div class="px-6 py-5 border-b border-slate-800 flex items-center gap-3">
                <x-eagle-logo class="w-9 h-9 text-orange-500 shrink-0" />
                <div>
                    <span class="text-xl font-extrabold tracking-tight">CASH</span>
                    <p class="text-[11px] text-slate-400 -mt-0.5">Espace Direction</p>
                </div>
            </div>
            <nav class="flex-1 px-3 py-4 space-y-1">
                <a href="{{ route('director.dashboard') }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition
                          {{ request()->routeIs('director.dashboard') ? 'bg-orange-600 text-white' : 'text-slate-300 hover:bg-slate-800' }}">
                    Rapport du jour
                </a>
                <a href="{{ route('director.rapports') }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition
                          {{ request()->routeIs('director.rapports') ? 'bg-orange-600 text-white' : 'text-slate-300 hover:bg-slate-800' }}">
                    Rapports (semaine/mois/annee)
                </a>
                <a href="{{ route('director.historique') }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition
                          {{ request()->routeIs('director.historique') ? 'bg-orange-600 text-white' : 'text-slate-300 hover:bg-slate-800' }}">
                    Historique
                </a>
                <a href="{{ route('director.utilisateurs') }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition
                          {{ request()->routeIs('director.utilisateurs*') ? 'bg-orange-600 text-white' : 'text-slate-300 hover:bg-slate-800' }}">
                    Utilisateurs
                </a>
                <a href="{{ route('director.parametres') }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition
                          {{ request()->routeIs('director.parametres') ? 'bg-orange-600 text-white' : 'text-slate-300 hover:bg-slate-800' }}">
                    Parametres
                </a>
            </nav>
            <div class="px-4 py-4 border-t border-slate-800">
                <p class="text-xs text-slate-400">Connecte</p>
                <p class="text-sm text-slate-200 font-medium truncate">{{ auth()->user()->name ?? '-' }}</p>
                <p class="text-[11px] text-orange-400 font-medium mt-0.5">Directeur (lecture seule)</p>
                <form method="POST" action="{{ route('logout') }}" class="mt-2">
                    @csrf
                    <button class="text-xs text-slate-400 hover:text-orange-400">Se deconnecter</button>
                </form>
            </div>
        </aside>

        <div class="flex-1 flex flex-col min-w-0">
            <header class="app-header bg-white border-b px-4 sm:px-8 py-3 sm:py-4 flex items-center justify-between no-print">
                <div class="flex items-center gap-3 min-w-0">
                    <details class="relative md:hidden">
                        <summary class="list-none cursor-pointer rounded-lg border border-slate-200 p-2 text-slate-700" aria-label="Ouvrir le menu">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                        </summary>
                        <nav class="absolute left-0 top-11 z-30 w-56 rounded-xl border border-slate-200 bg-white p-2 shadow-xl">
                            <a href="{{ route('director.dashboard') }}" class="block rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs('director.dashboard') ? 'bg-orange-600 text-white' : 'text-slate-700 hover:bg-slate-100' }}">Rapport du jour</a>
                            <a href="{{ route('director.rapports') }}" class="block rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs('director.rapports') ? 'bg-orange-600 text-white' : 'text-slate-700 hover:bg-slate-100' }}">Rapports</a>
                            <a href="{{ route('director.historique') }}" class="block rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs('director.historique') ? 'bg-orange-600 text-white' : 'text-slate-700 hover:bg-slate-100' }}">Historique</a>
                            <a href="{{ route('director.utilisateurs') }}" class="block rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs('director.utilisateurs*') ? 'bg-orange-600 text-white' : 'text-slate-700 hover:bg-slate-100' }}">Utilisateurs</a>
                            <a href="{{ route('director.parametres') }}" class="block rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs('director.parametres') ? 'bg-orange-600 text-white' : 'text-slate-700 hover:bg-slate-100' }}">Parametres</a>
        <div class="px-3 py-2 border-t border-gray-100 mt-2">
            <form action="{{ route('logout') }}" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir vous déconnecter ?');" class="m-0">
                @csrf
                <button type="submit" id="logout-director-dashboard-btn" class="w-full text-left text-sm font-semibold text-red-600 hover:text-red-700 py-1 transition block">
                    Se déconnecter
                </button>
            </form>
        </div>
                        </nav>
                    </details>
                    <h1 class="truncate text-base sm:text-lg font-semibold text-slate-800">@yield('title', 'Rapport du jour')</h1>
                </div>
                <button onclick="window.location.reload()" type="button"
                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-200 text-slate-600 text-xs font-medium hover:bg-slate-50">
                    Actualiser
                </button>
            </header>

            <main class="flex-1 min-w-0 px-4 sm:px-6 lg:px-8 py-4 sm:py-6">
                @if (session('success'))
                    <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 text-sm no-print">
                        {{ session('success') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm no-print">
                        <ul class="list-disc list-inside space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>

