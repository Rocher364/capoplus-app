<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applique sur les routes operationnelles (comptes, membres, prets).
 * Le Directeur (role auditeur) n'a rien a y faire : il est renvoye vers
 * son propre tableau de bord de supervision.
 */
class RedirectIfAuditeur
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && $request->user()->isAuditeur()) {
            if ($request->expectsJson()) {
                abort(403, 'Acces interdit : les auditeurs ne peuvent pas acceder aux routes operationnelles.');
            }

            return redirect()->route('director.dashboard');
        }

        return $next($request);
    }
}