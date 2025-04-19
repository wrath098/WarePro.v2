<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsNotDeveloper
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role = 'Developer'): Response
    {
        if (! $request->user()?->hasRole($role)) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Unauthorized'], 403)
                : redirect()->route('dashboard')->with('error', 'Unauthorized access');
        }

        return $next($request);
    }
}
