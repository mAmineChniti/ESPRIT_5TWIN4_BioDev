<?php

namespace App\Http\Middleware;

use App\Models\User;
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

        // Admins are admitted alongside the supply chain roles: they moderate
        // the catalogue and may correct a mistaken chain entry. FoodPolicy
        // grants the same set, minus registering products, which stays with the
        // professionals who actually perform the first step.
        if (! in_array($role, [...User::PROFESSIONAL_ROLES, 'admin'], true)) {
            abort(403, 'Restricted to supply chain professionals.');
        }

        return $next($request);
    }
}
