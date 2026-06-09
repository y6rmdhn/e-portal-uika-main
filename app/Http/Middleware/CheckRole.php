<?php
// app/Http/Middleware/CheckRole.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        $user = JWTAuth::user();

        if (!$user) {
            return response()->json(['status' => 401, 'message' => 'Unauthorized.'], 401);
        }

        $userRole = strtolower($user->role ?? '');

        foreach ($roles as $role) {
            if ($userRole === strtolower($role)) {
                return $next($request);
            }
        }

        return response()->json(['status' => 403, 'message' => 'Forbidden.'], 403);
    }
}