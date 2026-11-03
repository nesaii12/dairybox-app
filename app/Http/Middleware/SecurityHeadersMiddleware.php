<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddleware
{
    /**
     * Handle an incoming request and apply OWASP security response headers.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Anti-clickjacking: ensure application cannot be embedded in malicious iframes
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // Prevent MIME type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Control referrer leakage to third parties
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Restrict sensitive browser features/hardware APIs
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // Legacy XSS filter for older user agents
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Enforce HTTPS HSTS when running on HTTPS or in production
        if ($request->isSecure() || app()->isProduction()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
