<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates the HearthGG-only actions: activating/suspending/cancelling a
 * client, managing the module catalog, and anything else that isn't a
 * given client's own business.
 */
class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->is_super_admin, 403, 'HearthGG super admins only.');

        return $next($request);
    }
}
