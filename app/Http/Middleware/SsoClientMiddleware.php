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

        // Validasi header wajib ada
        if (!$clientId || !$clientSecret) {
            return response()->json([
                'status'  => 401,
                'success' => false,
                'message' => 'SSO client credentials missing. Provide X-SSO-Client-ID and X-SSO-Client-Secret headers.',
                'data'    => [],
            ], 401);
        }

        // Cari client berdasarkan client_id
        $client = SsoClient::active()->where('client_id', $clientId)->first();

        if (!$client) {
            return response()->json([
                'status'  => 401,
                'success' => false,
                'message' => 'SSO client not found or inactive.',
                'data'    => [],
            ], 401);
        }

        // Validasi secret
        if (!$client->verifySecret($clientSecret)) {
            return response()->json([
                'status'  => 401,
                'success' => false,
                'message' => 'Invalid SSO client credentials.',
                'data'    => [],
            ], 401);
        }

        // Catat penggunaan (async-style, tidak block request)
        try {
            $client->recordUsage();
        } catch (\Exception) {
            // silent — jangan sampai gagal catat break request
        }

        // Inject client ke request agar controller bisa pakai
        $request->attributes->set('sso_client', $client);

        return $next($request);
    }
}
