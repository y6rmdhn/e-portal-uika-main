<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Unit;
use App\Http\Helper\ResponseBuilder;

class UnitController extends Controller
{
    /**
     * GET /api/admins/units
     */
    public function index()
    {
        $units = Unit::all();
        return ResponseBuilder::success(200, 'success', $units);
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

        $unit->update([
            'code'      => $request->code,
            'nama_unit' => $request->nama_unit,
        ]);

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

        $unit->delete();

        return response()->json([
            'status'  => 200,
            'message' => 'Unit deleted successfully.',
            'data'    => [],
        ], 200);
    }
}
