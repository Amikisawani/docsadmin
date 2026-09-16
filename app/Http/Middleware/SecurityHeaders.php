<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        // DENY sur le HTML empêche le clickjacking. SAMEORIGIN sur les fichiers
        // (PDF/images) est obligatoire : Firefox charge son viewer PDF dans un
        // iframe, et refuse l'aperçu avec le message « intégrée par un autre site ».
        $allowSameOriginFrame = $this->allowsSameOriginFrame($request, $response);
        $response->headers->set('X-Frame-Options', $allowSameOriginFrame ? 'SAMEORIGIN' : 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->headers->set('X-XSS-Protection', '0');

        if (app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }

    private function allowsSameOriginFrame(Request $request, Response $response): bool
    {
        if ($request->is('storage/*')) {
            return true;
        }

        $contentType = strtolower((string) $response->headers->get('Content-Type', ''));

        return str_starts_with($contentType, 'application/pdf')
            || str_starts_with($contentType, 'image/');
    }
}
