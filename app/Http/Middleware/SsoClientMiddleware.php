<?php

namespace App\Http\Middleware;

use App\Models\SsoClient;
use Closure;
use Illuminate\Http\Request;

/**
 * SsoClientMiddleware
 *
 * Memvalidasi bahwa sub-aplikasi yang memanggil endpoint /api/sso/*
 * adalah client yang terdaftar dan aktif di tabel sso_clients.
 *
 * Sub-aplikasi WAJIB mengirim header:
 *   X-SSO-Client-ID: {client_id}
 *   X-SSO-Client-Secret: {plain_secret}
 */
class SsoClientMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $clientId     = $request->header('X-SSO-Client-ID');
        $clientSecret = $request->header('X-SSO-Client-Secret');

        if (!$clientId || !$clientSecret) {
            return response()->json([
                'status'  => 401,
                'success' => false,
                'message' => 'SSO client credentials missing.',
                'data'    => [],
            ], 401);
        }

        $client = SsoClient::active()->where('client_id', $clientId)->first();

        if (!$client) {
            return response()->json([
                'status'  => 401,
                'success' => false,
                'message' => 'SSO client not found or inactive.',
                'data'    => [],
            ], 401);
        }

        if (!$client->verifySecret($clientSecret)) {
            return response()->json([
                'status'  => 401,
                'success' => false,
                'message' => 'Invalid SSO client credentials.',
                'data'    => [],
            ], 401);
        }

        try {
            $client->recordUsage();
        } catch (\Exception) {
            // silent
        }

        $request->attributes->set('sso_client', $client);

        return $next($request);
    }
}
