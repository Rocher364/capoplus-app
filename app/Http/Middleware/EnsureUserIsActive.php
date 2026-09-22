<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware vérifiant qu'un utilisateur authentifié possède toujours un compte actif.
 *
 * Si un compte est désactivé, suspendu ou verrouillé pendant qu'une session est ouverte,
 * la requête suivante invalide immédiatement la session et redirige vers la page de connexion.
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->canAuthenticate()) {
            Auth::guard('web')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                abort(403, 'Votre compte a été désactivé ou verrouillé.');
            }

            return redirect()->route('login')->withErrors([
                'email' => 'Votre compte a été désactivé ou verrouillé.',
            ]);
        }

        return $next($request);
    }
}
