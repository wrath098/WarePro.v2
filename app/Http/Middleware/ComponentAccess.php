<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ComponentAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $role = 'Developer';
        $requiredPermissions = [
            'view-proposed-budget',
            'view-account-class',
            'view-category',
            'view-item-class',
            'view-office',
        ];

        $user = $request->user();

        if (!$user->hasAnyPermission($requiredPermissions) && !$user->hasRole($role)) {
            return $this->unauthorizedResponse($request, 'Unauthorized access!');
        }

        return $next($request);
    }

    protected function unauthorizedResponse(Request $request, string $message)
    {
        return $request->expectsJson()
            ? response()->json(['message' => $message], 403)
            : redirect()->route('dashboard')->with('error', $message);
    }
}
