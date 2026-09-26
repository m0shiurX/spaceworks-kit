<?php

declare(strict_types=1);

namespace Spaceworks\Kit\Attribution;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Stores the visitor's first touch in the attribution cookie on the first
 * full HTML page load. Inertia visits, XHR, prefetches, non-GET requests,
 * non-HTML responses and errors are skipped; an existing cookie is never
 * overwritten.
 */
class CaptureAttribution
{
    public function __construct(private Attribution $attribution) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($this->shouldCapture($request, $response)) {
            $response->headers->setCookie(
                $this->attribution->cookieFor(AttributionData::fromRequest($request)),
            );
        }

        return $response;
    }

    private function shouldCapture(Request $request, Response $response): bool
    {
        return $request->isMethod('GET')
            && ! $request->header('X-Inertia')
            && ! $request->ajax()
            && ! $request->prefetch()
            && $response->getStatusCode() < 400
            && ($response->isRedirection() || str_starts_with((string) $response->headers->get('Content-Type'), 'text/html'))
            && ! $this->attribution->hasFirstTouch($request);
    }
}
