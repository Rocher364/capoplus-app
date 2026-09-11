<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Connexion - CASH</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-100 text-slate-900 antialiased">
    <div class="min-h-screen grid grid-cols-1 lg:grid-cols-2">
        <!-- Section Gòch (Brand / Gradient) -->
        <div class="hidden lg:flex flex-col justify-between p-12 bg-gradient-to-br from-orange-600 via-orange-700 to-slate-900 text-white relative overflow-hidden">
            <div class="absolute -top-24 -right-24 w-96 h-96 rounded-full bg-white/5"></div>
            <div class="absolute bottom-0 -left-24 w-72 h-72 rounded-full bg-white/5"></div>

            <div class="relative flex items-center gap-3">
                <x-eagle-logo class="w-10 h-10 text-white" />
                <span class="text-2xl font-extrabold tracking-tight">CASH</span>
            </div>

            <div class="relative max-w-md">
                <h2 class="text-3xl font-bold leading-tight mb-4">Gestion financiere et micro-credit, simplifiee.</h2>
                <p class="text-orange-100 text-sm leading-relaxed">
                    Comptes membres, depots et retraits securises, prets avec echeancier automatique,
                    et une piste d'audit complete pour chaque operation.
                </p>
                <ul class="mt-8 space-y-3 text-sm text-orange-100">
                    <li class="flex items-center gap-3">
                        <span class="w-2 h-2 rounded-full bg-orange-300"></span>
                        Transactions atomiques et securisees
                    </li>
                    <li class="flex items-center gap-3">
                        <span class="w-2 h-2 rounded-full bg-orange-300"></span>
                        Authentification a deux facteurs
                    </li>
                    <li class="flex items-center gap-3">
                        <span class="w-2 h-2 rounded-full bg-orange-300"></span>
                        Bilan journalier et rapports imprimables
                    </li>
                </ul>
            </div>

            <p class="relative text-xs text-orange-200">&copy; {{ date('Y') }} CASH - Plateforme locale, hors ligne.</p>
        </div>

        <!-- Section Dwat (Formulaire Connexion) -->
        <div class="flex items-center justify-center p-8 bg-slate-100">
            <div class="w-full max-w-md">
                <div class="lg:hidden flex items-center justify-center gap-3 mb-8">
                    <x-eagle-logo class="w-9 h-9 text-orange-600" />
                    <span class="text-2xl font-extrabold text-slate-900">CASH</span>
                </div>

                <div class="bg-white p-8 rounded-2xl border border-slate-300 shadow-md">
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight mb-1">Connexion</h1>
                    <p class="text-sm font-medium text-slate-600 mb-6">Accedez a votre espace de gestion.</p>

                    @if ($errors->any())
                        <div class="mb-5 rounded-xl bg-rose-50 border border-rose-300 text-rose-700 text-sm p-4">
                            <ul class="list-disc list-inside space-y-1 font-medium">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}" class="space-y-5">
                        @csrf
                        <div>
                            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider block">Email</label>
                            <input type="email" name="email" value="{{ old('email') }}" required autofocus
                                   placeholder="vous@cash.test"
                                   class="mt-1.5 w-full rounded-xl border border-slate-300 bg-slate-50 text-slate-900 text-sm px-4 py-2.5 focus:bg-white focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 transition outline-none">
                        </div>

                        <div>
                            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider block">Mot de passe</label>
                            <input type="password" name="password" required
                                   placeholder="********"
                                   class="mt-1.5 w-full rounded-xl border border-slate-300 bg-slate-50 text-slate-900 text-sm px-4 py-2.5 focus:bg-white focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 transition outline-none">
                        </div>

                        <div class="flex items-center justify-between">
                            <label class="flex items-center gap-2 text-sm font-semibold text-slate-700 cursor-pointer">
                                <input type="checkbox" name="remember" class="rounded border-slate-300 text-orange-600 focus:ring-orange-500">
                                Se souvenir de moi
                            </label>
                        </div>

                        <button type="submit"
                                class="w-full py-3 rounded-xl bg-orange-600 text-white text-sm font-semibold hover:bg-orange-700 shadow-sm active:scale-[0.98] transition duration-150">
                            Se connecter
                        </button>
                    </form>
                </div>

                <p class="mt-6 text-center text-xs font-semibold text-slate-500">
                    Acces reserve au personnel autorise de CASH.
                </p>
            </div>
        </div>
    </div>
</body>
</html>
