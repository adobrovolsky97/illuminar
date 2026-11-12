<?php

namespace Adobrovolsky97\Illuminar\Http\Middleware;

use Adobrovolsky97\Illuminar\Illuminar;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

/**
 * Protects Illuminar UI and API.
 *
 * Define a "viewIlluminar" gate to control access, otherwise the UI is available only
 * in environments listed in illuminar.allowed_environments (local by default).
 */
class Authorize
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Illuminar::isEnabled()) {
            abort(404);
        }

        if (!$this->allowed($request)) {
            abort(403);
        }

        return $next($request);
    }

    private function allowed(Request $request): bool
    {
        if (Gate::has('viewIlluminar')) {
            return Gate::check('viewIlluminar', [$request->user()]);
        }

        return app()->environment((array)config('illuminar.allowed_environments', ['local']));
    }
}
