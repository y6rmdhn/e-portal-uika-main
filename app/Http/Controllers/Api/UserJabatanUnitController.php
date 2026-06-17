<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\UserJabatanUnit;
use App\Http\Helper\ResponseBuilder;

class UserJabatanUnitController extends Controller
{
    /**
     * POST /api/admins/user-jabatan-units/assign
     */
    public function assign(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id'    => 'required|string|exists:App\Models\User,user_id',
            'jabatan_id' => 'required|integer|exists:m_jabatan,id',
            'unit_id'    => 'required|integer|exists:m_unit,id',
            'keterangan' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 422,
                'message' => $validator->errors()->first(),
                'data'    => [],
            ], 422);
        }

        $assignment = UserJabatanUnit::firstOrCreate([
            'user_id'    => $request->user_id,
            'jabatan_id' => $request->jabatan_id,
            'unit_id'    => $request->unit_id,
        ], [
            'keterangan' => $request->keterangan,
        ]);

        // Sync Spatie role
        $userModel = \App\Models\User::where('user_id', $request->user_id)->first();
        $jabatanModel = \App\Models\Jabatan::find($request->jabatan_id);
        if ($userModel && $jabatanModel) {
            $userModel->assignRole($jabatanModel->name);
        }

        return ResponseBuilder::success(200, 'Jabatan & Unit assigned successfully.', $assignment->load(['jabatan', 'unit']));
    }

    /**
     * POST /api/admins/user-jabatan-units/unassign
     */
    public function unassign(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id'    => 'required|string|exists:App\Models\User,user_id',
            'jabatan_id' => 'required|integer|exists:m_jabatan,id',
            'unit_id'    => 'required|integer|exists:m_unit,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 422,
                'message' => $validator->errors()->first(),
                'data'    => [],
            ], 422);
        }

        $userId = $request->user_id;
        $jabatanId = $request->jabatan_id;
        $unitId = $request->unit_id;

        UserJabatanUnit::where([
            'user_id'    => $userId,
            'jabatan_id' => $jabatanId,
            'unit_id'    => $unitId,
        ])->delete();

        // Sync Spatie role
        $userModel = \App\Models\User::where('user_id', $userId)->first();
        $jabatanModel = \App\Models\Jabatan::find($jabatanId);
        if ($userModel && $jabatanModel) {
            // Check if user has this same jabatan in any other unit
            $otherAssignments = UserJabatanUnit::where([
                'user_id'    => $userId,
                'jabatan_id' => $jabatanId,
            ])->exists();

            if (!$otherAssignments) {
                $userModel->removeRole($jabatanModel->name);
            }
        }

        return ResponseBuilder::success(200, 'Jabatan & Unit unassigned successfully.');
    }
}
