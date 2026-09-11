<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CASH - @yield('title', 'Tableau de bord')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#fff7ed', 100: '#ffedd5', 500: '#f97316',
                            600: '#ea580c', 700: '#c2410c',
                        },
                    },
                },
            },
        };
    </script>
    <style>
        @media print {
            .no-print { display: none !important; }
            aside, header.app-header { display: none !important; }
            main { padding: 0 !important; }
            body { background: white !important; }
            .print-card { box-shadow: none !important; border: 1px solid #ddd !important; }
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen antialiased">
    <div class="flex min-h-screen">
        <aside class="hidden md:flex w-64 bg-slate-900 text-slate-100 flex-col shrink-0 no-print">
            <div class="px-6 py-5 border-b border-slate-800 flex items-center gap-3">
                <x-eagle-logo class="w-9 h-9 text-orange-500 shrink-0" />
                <div>
                    <span class="text-xl font-extrabold tracking-tight">CASH</span>
                    <p class="text-[11px] text-slate-400 -mt-0.5">Micro-credit &amp; gestion financiere</p>
                </div>
            </div>
            <nav class="flex-1 px-3 py-4 space-y-1">
                <a href="{{ route('dashboard') }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition
                          {{ request()->routeIs('dashboard') ? 'bg-orange-600 text-white' : 'text-slate-300 hover:bg-slate-800' }}">
                    Tableau de bord
                </a>
                <a href="{{ route('reports.index') }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition
                          {{ request()->routeIs('reports.*') ? 'bg-orange-600 text-white' : 'text-slate-300 hover:bg-slate-800' }}">
                    Rapports
                </a>
                <a href="{{ route('accounts.index') }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition
                          {{ request()->routeIs('accounts.*') ? 'bg-orange-600 text-white' : 'text-slate-300 hover:bg-slate-800' }}">
                    Comptes
                </a>
                <a href="{{ route('members.index') }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition
                          {{ request()->routeIs('members.*') ? 'bg-orange-600 text-white' : 'text-slate-300 hover:bg-slate-800' }}">
                    Membres
                </a>
                <a href="{{ route('loans.index') }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition
                          {{ request()->routeIs('loans.*') ? 'bg-orange-600 text-white' : 'text-slate-300 hover:bg-slate-800' }}">
                    Prets
                </a>
            </nav>
            <div class="px-4 py-4 border-t border-slate-800">
                <p class="text-xs text-slate-400">Connecte</p>
                <p class="text-sm text-slate-200 font-medium truncate">{{ auth()->user()->name ?? '-' }}</p>
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
                            <a href="{{ route('dashboard') }}" class="block rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs('dashboard') ? 'bg-orange-600 text-white' : 'text-slate-700 hover:bg-slate-100' }}">Tableau de bord</a>
                            <a href="{{ route('reports.index') }}" class="block rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs('reports.*') ? 'bg-orange-600 text-white' : 'text-slate-700 hover:bg-slate-100' }}">Rapports</a>
                            <a href="{{ route('accounts.index') }}" class="block rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs('accounts.*') ? 'bg-orange-600 text-white' : 'text-slate-700 hover:bg-slate-100' }}">Comptes</a>
                            <a href="{{ route('members.index') }}" class="block rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs('members.*') ? 'bg-orange-600 text-white' : 'text-slate-700 hover:bg-slate-100' }}">Membres</a>
                            <a href="{{ route('loans.index') }}" class="block rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs('loans.*') ? 'bg-orange-600 text-white' : 'text-slate-700 hover:bg-slate-100' }}">Prets</a>
        <div class="border-t border-gray-100 mt-2 pt-2">
            <form action="{{ route('logout') }}" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir vous déconnecter ?');">
                @csrf
                <button type="submit" id="logout-force-btn" class="w-full text-left px-4 py-2 text-sm font-semibold text-red-600 hover:bg-red-50 rounded-md transition block">
                    Se déconnecter
                </button>
            </form>
        </div>
                        </nav>
                    </details>
                    <h1 class="truncate text-base sm:text-lg font-semibold text-slate-800">@yield('title', 'Tableau de bord')</h1>
                </div>
                <div class="flex items-center gap-2 sm:gap-3 shrink-0">
                    <span class="text-xs text-slate-400" id="cash-clock"></span>
                    <button onclick="window.location.reload()" type="button"
                            class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-200 text-slate-600 text-xs font-medium hover:bg-slate-50">
                        Actualiser
                    </button>
                </div>
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

    <script>
        function cashUpdateClock() {
            var el = document.getElementById('cash-clock');
            if (!el) return;
            var now = new Date();
            el.textContent = now.toLocaleDateString('fr-FR') + ' - ' + now.toLocaleTimeString('fr-FR');
        }
        cashUpdateClock();
        setInterval(cashUpdateClock, 1000);
    </script>
</body>
</html>



