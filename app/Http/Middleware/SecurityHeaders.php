<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Sources réellement utilisées dans CAPO+ :
     *   - Scripts : bundles Vite locaux uniquement ('self').
     *   - Styles  : bundles Vite locaux + injection inline de Tailwind JIT ('unsafe-inline').
     *   - Fonts   : polices Vite (woff2/woff) servies depuis 'self'.
     *   - Images  : assets locaux + data URIs (avatars, icônes).
     *   - Connexions : API locale uniquement ('self').
     *   - Aucun CDN externe n'est autorisé depuis ce middleware.
     *
     * Note : 'unsafe-inline' pour les styles est inévitable tant que Tailwind
     * injecte des variables CSS dynamiquement. Une CSP stricte basée sur nonces
     * pourra être ajoutée lorsque Vite sera configuré pour injecter un nonce CSP.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $csp = implode('; ', [
            "default-src 'self'",
            "script-src 'self'",
            "style-src 'self' 'unsafe-inline'",
            "font-src 'self' data:",
            "img-src 'self' data:",
            "connect-src 'self'",
            "frame-ancestors 'none'",
            "base-uri 'self'",
            "form-action 'self'",
        ]);

        $response->headers->set('Content-Security-Policy', $csp);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}