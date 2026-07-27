<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set(
            'Permissions-Policy',
            'camera=(self), geolocation=(), microphone=(), payment=(), usb=()'
        );
        $response->headers->set(
            'Content-Security-Policy',
            "frame-ancestors 'none'; base-uri 'self'; form-action 'self'"
        );

        $contentType = strtolower((string) $response->headers->get('Content-Type'));
        $isSensitiveDocument = str_starts_with($contentType, 'text/html')
            || str_starts_with($contentType, 'application/json');

        if ($request->user() && $isSensitiveDocument) {
            $response->headers->set('Cache-Control', 'private, no-store, max-age=0');
            $response->headers->set('Pragma', 'no-cache');
        }

        if (app()->isProduction() && $request->isSecure()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains'
            );
        }

        return $response;
    }
}
