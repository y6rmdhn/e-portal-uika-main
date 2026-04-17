<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class SsoController extends Controller
{
    public function generateTicket(Request $request)
    {
        $ticket = Str::random(50);

        // Simpan tiket di cache dengan masa berlaku 2 menit
        Cache::put('sso_ticket_' . $ticket, $request->user()->id, now()->addMinutes(2));

        return response()->json(['ticket' => $ticket]);
    }

    public function verifyTicket(Request $request)
    {
        $ticket = $request->ticket;
        $userId = Cache::get('sso_ticket_' . $ticket);

        if (!$userId) {
            return response()->json(['message' => 'Tiket SSO tidak valid atau sudah kadaluarsa'], 401);
        }

        Cache::forget('sso_ticket_' . $ticket);

        $user = User::find($userId);

        return response()->json($user);
    }

    public function checkIdentifier(Request $request)
    {
        // validasi input
        $request->validate([
            'role_id' => 'required|string',
            'identifier' => 'required|string',
        ]);

        $roleId = $request->role_id;
        $identifier = $request->identifier;

        try {
            if ($roleId == "1") {
                // $response = 
            }
        } catch (\Throwable $th) {
            //throw $th;
        }
    }
}
