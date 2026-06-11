<?php

namespace App\Http\Middleware;

use Closure;
use JWTAuth;
use Exception;
use Tymon\JWTAuth\Http\Middleware\BaseMiddleware;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth as FacadesJWTAuth;

class JwtMiddleware extends BaseMiddleware
{
    public function handle($request, Closure $next)
    {
        if (!$request->bearerToken() && $request->hasCookie('uika_sso_token')) {
            $request->headers->set('Authorization', 'Bearer ' . $request->cookie('uika_sso_token'));
        }

        try {
            $token = $request->bearerToken();

            $user = \Cache::remember('jwt_user_' . md5($token), 300, function () {
                return FacadesJWTAuth::parseToken()->authenticate();
            });

            auth()->setUser($user);
        } catch (Exception $e) {
            \Cache::forget('jwt_user_' . md5($request->bearerToken() ?? ''));

            \Log::error('JWT Error: ' . $e->getMessage() . ' | Class: ' . get_class($e));

            if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenInvalidException) {
                return response()->json([
                    'status'  => 401,
                    'message' => 'Token is Invalid',
                    'data'    => []
                ], 401);
            } else if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenExpiredException) {
                return response()->json([
                    'status'  => 401,
                    'message' => 'Token is Expired',
                    'data'    => []
                ], 401);
            } else {
                return response()->json([
                    'status'  => 401,
                    'message' => 'Authorization Token not found',
                    'data'    => []
                ], 401);
            }
        }
        return $next($request);
    }
}