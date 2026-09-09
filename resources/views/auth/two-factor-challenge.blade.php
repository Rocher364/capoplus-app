<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verification - CASH</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen flex items-center justify-center px-4">
    <div class="w-full max-w-sm bg-white rounded-2xl border border-slate-300 shadow-md p-8">
        <div class="flex items-center gap-2 mb-6">
            <x-eagle-logo class="w-7 h-7 text-orange-600" />
            <span class="text-lg font-extrabold text-slate-900 tracking-tight">CASH</span>
        </div>
        
        <h1 class="text-xl font-bold text-slate-900 tracking-tight mb-1">Verification en deux etapes</h1>
        <p class="text-sm font-medium text-slate-600 mb-6">
            Entrez le code de votre application d'authentification, ou un code de recuperation.
        </p>

        @if ($errors->any())
            <div class="mb-6 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm p-4">
                <ul class="list-disc list-inside space-y-1 font-medium">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('two-factor.login') }}" class="space-y-5">
            @csrf
            <div>
                <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Code (application d'authentification)</label>
                <input type="text" name="code" inputmode="numeric" autofocus
                       class="mt-1.5 w-full rounded-xl border border-slate-300 bg-slate-50 text-slate-900 text-sm px-4 py-3 focus:bg-white focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 transition duration-150 outline-none">
            </div>
            
            <div>
                <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Ou code de recuperation</label>
                <input type="text" name="recovery_code"
                       class="mt-1.5 w-full rounded-xl border border-slate-300 bg-slate-50 text-slate-900 text-sm px-4 py-3 focus:bg-white focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 transition duration-150 outline-none">
            </div>
            
            <button type="submit"
                    class="w-full py-3.5 rounded-xl bg-orange-600 text-white text-sm font-semibold hover:bg-orange-700 shadow-sm hover:shadow-orange-600/20 active:scale-[0.98] transition duration-150">
                Verifier
            </button>
        </form>
    </div>
</body>
</html>