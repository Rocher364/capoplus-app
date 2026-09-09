<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applique sur les routes du Directeur (rapport du jour, historique).
 * Seul le role 'auditeur' y a acces : l'Admin et l'Agent sont renvoyes
 * vers leur propre tableau de bord operationnel, meme s'ils devinent l'URL.
 */
class EnsureAuditeur
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || ! $request->user()->isAuditeur()) {
            return redirect()->route('dashboard');
        }

        return $next($request);
    }
}