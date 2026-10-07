<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate for the supply chain back office.
 *
 * This only decides who may *reach* a controller; FoodPolicy decides what they
 * may actually do once inside. Admins are let through so the admin branches of
 * that policy are reachable — FoodPolicy::create still refuses them.
 */
class ProAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $role = $request->user()?->role;

        if (! in_array($role, ['producer', 'processor', 'distributor', 'admin'], true)) {
            abort(403, 'Restricted to supply chain professionals.');
        }

        return $next($request);
    }
}
