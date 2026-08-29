<?php

namespace App\Http\Middleware;

use App\Http\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRoleIs
{
    /**
     * Handle an incoming request, insisting the user holds one of the given roles.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return ApiResponse::error('Unauthenticated.', 401);
        }

        if (! in_array($user->role, $roles, true)) {
            return ApiResponse::error('You do not have permission to access this resource.', 403);
        }

        return $next($request);
    }
}
