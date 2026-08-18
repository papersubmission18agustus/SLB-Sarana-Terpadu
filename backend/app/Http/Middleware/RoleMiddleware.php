<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $role = method_exists($user, 'getRole')
            ? $user->getRole()
            : ($user instanceof \App\Models\Student ? 'pendamping' : null);

        if (! in_array($role, $roles, true)) {
            return response()->json(['message' => 'Anda tidak memiliki akses ke resource ini.'], 403);
        }

        return $next($request);
    }
}
