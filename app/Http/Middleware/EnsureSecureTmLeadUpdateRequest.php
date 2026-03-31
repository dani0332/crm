<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class EnsureSecureTmLeadUpdateRequest
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {       
        $contentType = strtolower((string) $request->header('Content-Type', ''));
        if (! Str::startsWith($contentType, 'application/json')) {
            abort(415, 'Unsupported Media Type');
        }

        if (! $this->hasXsrfTokenHeader($request)) {
            abort(419, 'CSRF token mismatch.');
        }

        if (! $this->isSameOriginOrReferrer($request)) {
            abort(403, 'Invalid request origin.');
        }

        return $next($request);
    }

    private function hasXsrfTokenHeader(Request $request): bool
    {
        $headerToken = (string) $request->header('X-XSRF-TOKEN', '');

        return $headerToken !== '';
    }

    private function isSameOriginOrReferrer(Request $request): bool
    {
        $expectedHost = strtolower((string) parse_url($request->getSchemeAndHttpHost(), PHP_URL_HOST));
        if ($expectedHost === '') {
            return false;
        }

        $originHost = strtolower((string) parse_url((string) $request->header('Origin', ''), PHP_URL_HOST));
        if ($originHost !== '' && $originHost === $expectedHost) {
            return true;
        }

        $refererHost = strtolower((string) parse_url((string) $request->header('Referer', ''), PHP_URL_HOST));

        return $refererHost !== '' && $refererHost === $expectedHost;
    }
}
