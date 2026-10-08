<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class SsoSyncController extends Controller
{
    /**
     * GET /api/sso/users-sync
     * Returns list of users for sub-applications to sync.
     */
    public function index(Request $request)
    {
        // Optional: you can add authorization check here if needed.
        
        $users = User::with(['roles', 'dataPribadi'])->get()->map(function ($user) {
            return [
                'user_id' => $user->user_id,
                'nama_lengkap' => $user->dataPribadi->nama_lengkap ?? null,
                'email' => $user->email,
                'username' => $user->npm ?? $user->nidn ?? $user->email, // Using npm/nidn/email as username
                'password' => $user->password, // Send hashed password as requested by lecturer
                'role' => $user->role,
                'jabatan' => $user->roles->pluck('name'),
                'is_verified' => $user->isverified,
                'created_at' => $user->created_at,
            ];
        });

        return response()->json([
            'status' => 200,
            'message' => 'SSO Users retrieved successfully.',
            'data' => $users
        ]);
    }
}
