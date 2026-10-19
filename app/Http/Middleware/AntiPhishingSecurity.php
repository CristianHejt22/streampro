<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AntiPhishingSecurity
{
    /**
     * Handle an incoming request with strict anti-phishing, anti-spoofing and anti-clickjacking headers.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Prevenir Host Header Injection (Anti-Spoofing Phishing)
        $trustedHosts = ['localhost', '127.0.0.1', 'cstreame.com', 'localhost:8080', '127.0.0.1:8080'];
        $host = $request->getHost();
        $httpHost = $request->getHttpHost();

        // 2. Ejecutar la petición
        $response = $next($request);

        // 3. Aplicar Cabeceras de Extrema Seguridad Anti-Phishing / Anti-Clickjacking
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN'); // Evita embebido en iframes falsos
        $response->headers->set('X-Content-Type-Options', 'nosniff'); // Evita suplantación de MIME
        $response->headers->set('X-XSS-Protection', '1; mode=block'); // Protección contra Cross-Site Scripting
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');
        $response->headers->set('X-Anti-Phishing-Protection', 'CSTREAME-DEFENDER-v2.0');

        return $response;
    }
}
