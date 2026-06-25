<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Jabatan;
use App\Http\Helper\ResponseBuilder;

class JabatanController extends Controller
{
    /**
     * GET /api/admins/jabatans
     */
    public function index()
    {
        $jabatans = Jabatan::all();
        return ResponseBuilder::success(200, 'success', $jabatans);
    }

    /**
     * GET /api/admins/jabatans/{id}
     */
    public function show($id)
    {
        $jabatan = Jabatan::find($id);

        if (!$jabatan) {
            return response()->json([
                'status'  => 404,
                'message' => 'Jabatan not found.',
                'data'    => [],
            ], 404);
        }

        return ResponseBuilder::success(200, 'success', $jabatan);
    }

    /**
     * POST /api/admins/jabatans
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama_jabatan' => 'required|string|max:100|unique:m_jabatan,nama_jabatan',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 422,
                'message' => $validator->errors()->first(),
                'data'    => [],
            ], 422);
        }

        $jabatan = Jabatan::create([
            'nama_jabatan' => $request->nama_jabatan,
        ]);

        return ResponseBuilder::success(201, 'Jabatan created successfully.', $jabatan);
    }

    /**
     * PUT /api/admins/jabatans/{id}
     */
    public function update(Request $request, $id)
    {
        $jabatan = Jabatan::find($id);

        if (!$jabatan) {
            return response()->json([
                'status'  => 404,
                'message' => 'Jabatan not found.',
                'data'    => [],
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nama_jabatan' => 'required|string|max:100|unique:m_jabatan,nama_jabatan,' . $id,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 422,
                'message' => $validator->errors()->first(),
                'data'    => [],
            ], 422);
        }

        $jabatan->update([
            'nama_jabatan' => $request->nama_jabatan,
        ]);

        return ResponseBuilder::success(200, 'Jabatan updated successfully.', $jabatan);
    }

    /**
     * DELETE /api/admins/jabatans/{id}
     */
    public function destroy($id)
    {
        $jabatan = Jabatan::find($id);

        if (!$jabatan) {
            return response()->json([
                'status'  => 404,
                'message' => 'Jabatan not found.',
                'data'    => [],
            ], 404);
        }

        $jabatan->delete();

        return response()->json([
            'status'  => 200,
            'message' => 'Jabatan deleted successfully.',
            'data'    => [],
        ], 200);
    }
}
