<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Unit;
use App\Http\Helper\ResponseBuilder;
use App\Services\ActivityLogService;
use Tymon\JWTAuth\Facades\JWTAuth;

class UnitController extends Controller
{
    protected ActivityLogService $activityLog;

    public function __construct(ActivityLogService $activityLog)
    {
        $this->activityLog = $activityLog;
    }

    /**
     * GET /api/admins/units
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 15);
        $search  = $request->query('search', '');

        $query = Unit::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('nama_unit', 'like', "%{$search}%");
            });
        }

        $units = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'status'  => 200,
            'message' => 'success',
            'data'    => $units->items(),
            'meta'    => [
                'current_page' => $units->currentPage(),
                'per_page'     => $units->perPage(),
                'total'        => $units->total(),
                'last_page'    => $units->lastPage(),
                'from'         => $units->firstItem(),
                'to'           => $units->lastItem(),
            ],
        ]);
    }

    /**
     * GET /api/admins/units/{id}
     */
    public function show($id)
    {
        $unit = Unit::find($id);

        if (!$unit) {
            return response()->json([
                'status'  => 404,
                'message' => 'Unit not found.',
                'data'    => [],
            ], 404);
        }

        return ResponseBuilder::success(200, 'success', $unit);
    }

    /**
     * POST /api/admins/units
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code'      => 'required|string|max:50|unique:m_unit,code',
            'nama_unit' => 'required|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 422,
                'message' => $validator->errors()->first(),
                'data'    => [],
            ], 422);
        }

        $unit = Unit::create([
            'code'      => $request->code,
            'nama_unit' => $request->nama_unit,
        ]);

        // Log aktivitas
        try {
            $actor = JWTAuth::user();
            $this->activityLog->log(
                ActivityLogService::TYPE_UNIT_CREATE,
                "Membuat unit baru: [{$unit->code}] {$unit->nama_unit}",
                $actor?->user_id,
                $actor?->user_id,
                ['unit_id' => $unit->id, 'code' => $unit->code, 'nama_unit' => $unit->nama_unit]
            );
        } catch (\Exception $e) {
            // silent fail
        }

        return ResponseBuilder::success(201, 'Unit created successfully.', $unit);
    }

    /**
     * PUT /api/admins/units/{id}
     */
    public function update(Request $request, $id)
    {
        $unit = Unit::find($id);

        if (!$unit) {
            return response()->json([
                'status'  => 404,
                'message' => 'Unit not found.',
                'data'    => [],
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'code'      => 'required|string|max:50|unique:m_unit,code,' . $id,
            'nama_unit' => 'required|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 422,
                'message' => $validator->errors()->first(),
                'data'    => [],
            ], 422);
        }

        $oldCode = $unit->code;
        $oldName = $unit->nama_unit;

        $unit->update([
            'code'      => $request->code,
            'nama_unit' => $request->nama_unit,
        ]);

        // Log aktivitas
        try {
            $actor = JWTAuth::user();
            $this->activityLog->log(
                ActivityLogService::TYPE_UNIT_UPDATE,
                "Memperbarui unit: [{$oldCode}] {$oldName} → [{$unit->code}] {$unit->nama_unit}",
                $actor?->user_id,
                $actor?->user_id,
                [
                    'unit_id'      => $unit->id,
                    'before'       => ['code' => $oldCode, 'nama_unit' => $oldName],
                    'after'        => ['code' => $unit->code, 'nama_unit' => $unit->nama_unit],
                ]
            );
        } catch (\Exception $e) {
            // silent fail
        }

        return ResponseBuilder::success(200, 'Unit updated successfully.', $unit);
    }

    /**
     * DELETE /api/admins/units/{id}
     */
    public function destroy($id)
    {
        $unit = Unit::find($id);

        if (!$unit) {
            return response()->json([
                'status'  => 404,
                'message' => 'Unit not found.',
                'data'    => [],
            ], 404);
        }

        $unitCode = $unit->code;
        $unitName = $unit->nama_unit;

        $unit->delete();

        // Log aktivitas
        try {
            $actor = JWTAuth::user();
            $this->activityLog->log(
                ActivityLogService::TYPE_UNIT_DELETE,
                "Menghapus unit: [{$unitCode}] {$unitName}",
                $actor?->user_id,
                $actor?->user_id,
                ['unit_id' => $id, 'code' => $unitCode, 'nama_unit' => $unitName]
            );
        } catch (\Exception $e) {
            // silent fail
        }

        return response()->json([
            'status'  => 200,
            'message' => 'Unit deleted successfully.',
            'data'    => [],
        ], 200);
    }
}
