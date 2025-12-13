<?php

declare(strict_types=1);

namespace DissNik\RobotsTxt\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CacheRobotsTxt
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response->getStatusCode() === 200 && $request->is('robots.txt')) {
            $response->headers->set('Content-Type', 'text/plain');

            if (config('robots-txt.cache.enabled', true)) {
                $duration = config('robots-txt.cache.duration', 3600);
                $response->setMaxAge($duration)
                    ->setPublic();
            }
        }

        return $response;
    }
}
