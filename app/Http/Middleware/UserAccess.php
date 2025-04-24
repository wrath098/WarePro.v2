<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UserAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $roles = 'Developer|System Administrator'): Response
    {
        $allowedRoles = explode('|', $roles);

        if (! $request->user()?->hasAnyRole($allowedRoles)) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Unauthorized'], 403)
                : redirect()->route('dashboard')->with('error', 'Unauthorized access!');
        }

        return $next($request);
    }
}
