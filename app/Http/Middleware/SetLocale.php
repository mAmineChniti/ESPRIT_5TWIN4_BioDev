<?php

namespace App\Http\Middleware;

use App\Enums\Locale;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * Precedence: the URL prefix wins (it names the locale explicitly); then
     * the visitor's locale cookie; then English. The resolved locale is also
     * published as the default route parameter, so route() builds prefixed
     * URLs without every call site naming the locale.
     *
     * This runs inside the web group, after SubstituteBindings, and forgets
     * the locale segment before dispatch on purpose: the dispatcher maps
     * parameters to controller arguments positionally, so a leftover {locale}
     * would shift into the first model slot (e.g. the locale string landing
     * in Warehouse $warehouse). Model binding itself is unaffected — it reads
     * by parameter name.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $segment = $request->route()?->parameter('locale');
        $cookie = $request->cookie('locale');

        if (is_string($segment) && in_array($segment, Locale::values(), true)) {
            $locale = $segment;
        } else {
            $locale = Locale::resolve(is_string($cookie) ? $cookie : null)->value;
        }

        app()->setLocale($locale);
        Carbon::setLocale($locale);
        URL::defaults(['locale' => $locale]);

        // The address bar stays the single source of truth; the segment has
        // served its purpose once the locale is resolved.
        $request->route()?->forgetParameter('locale');

        $response = $next($request);

        // Re-stamp the cookie from the URL only, never the other way round.
        // JSON endpoints are excluded: cookies on API responses are overhead.
        if (is_string($segment) && $segment === $locale && ! $request->expectsJson()) {
            $response->headers->setCookie(cookie()->forever('locale', $locale));
        }

        return $response;
    }
}
