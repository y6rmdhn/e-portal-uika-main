<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class ProfileController extends Controller
{
    use ApiResponse;

    public function show(): JsonResponse
    {
        $user = JWTAuth::user();

        $dp = DB::connection('pgsql')
            ->table('tb_data_pribadi')
            ->where('user_id', $user->user_id)
            ->first();

        return response()->json([
            'status'  => true,
            'message' => 'Profile retrieved successfully',
            'data'    => [
                // Identitas utama
                'id'            => $user->user_id,
                'email'         => $user->email,
                'name'          => $dp?->nama_lengkap ?? $user->email,
                'role'          => $user->role,
                'nidn'          => $user->nidn,
                'npm'           => $user->npm,
                'image'         => $dp?->image,

                // Data pribadi
                'nama_lengkap'  => $dp?->nama_lengkap,
                'nik'           => $dp?->nik,
                'jenkel'        => $dp?->jenkel,
                'tanggal_lahir' => $dp?->tanggal_lahir,
                'tempat_lahir'  => $dp?->tempat_lahir,
                'ibu_kandung'   => $dp?->ibu_kandung,
                'agama'         => $dp?->agama,
                'warga_negara'  => $dp?->warga_negara,

                // Alamat dan kontak
                'no_hp'         => $dp?->no_hp,
                'alamat'        => $dp?->alamat,
                'rt'            => $dp?->rt,
                'rw'            => $dp?->rw,
                'desa_kelurahan' => $dp?->desa_kelurahan,
                'kota_kabupaten' => $dp?->kota_kabupaten,
                'provinsi'      => $dp?->provinsi,
                'kode_pos'      => $dp?->kode_pos,

                // Keluarga
                'status_kawin'      => $dp?->status_kawin,
                'nama_pasangan'     => $dp?->nama_pasangan,
                'nip_pasangan'      => $dp?->nip_pasangan,
                'pekerjaan_pasangan' => $dp?->pekerjaan_pasangan,
                'tanggal_pns_pasangan' => $dp?->tanggal_pns_pasangan,

                // Wali
                'wali'          => $dp?->wali,
                'telp_wali'     => $dp?->telp_wali,
                'alamat_wali'   => $dp?->alamat_wali,

                // Lainnya
                'pekerjaan'         => $dp?->pekerjaan,
                'alamat_pekerjaan'  => $dp?->alamat_pekerjaan,
            ],
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nama_lengkap'      => 'nullable|string|max:255',
            'nik'               => 'nullable|string|max:60',
            'jenkel'            => 'nullable|string',
            'tanggal_lahir'     => 'nullable|date',
            'tempat_lahir'      => 'nullable|string|max:255',
            'ibu_kandung'       => 'nullable|string|max:255',
            'agama'             => 'nullable|string|max:25',
            'warga_negara'      => 'nullable|string|max:100',
            'no_hp'             => 'nullable|string|max:50',
            'alamat'            => 'nullable|string|max:100',
            'rt'                => 'nullable|integer',
            'rw'                => 'nullable|integer',
            'desa_kelurahan'    => 'nullable|string|max:100',
            'kota_kabupaten'    => 'nullable|string|max:100',
            'provinsi'          => 'nullable|string|max:100',
            'kode_pos'          => 'nullable|string|max:5',
            'status_kawin'      => 'nullable|integer',
            'nama_pasangan'     => 'nullable|string|max:100',
            'nip_pasangan'      => 'nullable|string|max:18',
            'pekerjaan_pasangan' => 'nullable|string|max:255',
            'tanggal_pns_pasangan' => 'nullable|date',
            'wali'              => 'nullable|string|max:100',
            'telp_wali'         => 'nullable|string|max:50',
            'alamat_wali'       => 'nullable|string|max:255',
            'pekerjaan'         => 'nullable|string|max:255',
            'alamat_pekerjaan'  => 'nullable|string|max:255',
            'image'             => 'nullable|image|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $user = JWTAuth::user();

        $updateData = [
            'nama_lengkap'      => $request->nama_lengkap,
            'nik'               => $request->nik,
            'jenkel'            => $request->jenkel,
            'tanggal_lahir'     => $request->tanggal_lahir,
            'tempat_lahir'      => $request->tempat_lahir,
            'ibu_kandung'       => $request->ibu_kandung,
            'agama'             => $request->agama,
            'warga_negara'      => $request->warga_negara,
            'no_hp'             => $request->no_hp,
            'alamat'            => $request->alamat,
            'rt'                => $request->rt,
            'rw'                => $request->rw,
            'desa_kelurahan'    => $request->desa_kelurahan,
            'kota_kabupaten'    => $request->kota_kabupaten,
            'provinsi'          => $request->provinsi,
            'kode_pos'          => $request->kode_pos,
            'status_kawin'      => $request->status_kawin,
            'nama_pasangan'     => $request->nama_pasangan,
            'nip_pasangan'      => $request->nip_pasangan,
            'pekerjaan_pasangan' => $request->pekerjaan_pasangan,
            'tanggal_pns_pasangan' => $request->tanggal_pns_pasangan,
            'wali'              => $request->wali,
            'telp_wali'         => $request->telp_wali,
            'alamat_wali'       => $request->alamat_wali,
            'pekerjaan'         => $request->pekerjaan,
            'alamat_pekerjaan'  => $request->alamat_pekerjaan,
            'updated_at'        => now(),
        ];

        // Handle upload foto
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = 'profile_' . $user->user_id . '.' . $file->getClientOriginalExtension();
            $path = public_path('storage/profiles');
            if (!file_exists($path)) mkdir($path, 0755, true);
            $file->move($path, $filename);
            $updateData['image'] = url('storage/profiles/' . $filename);
        }

        $exists = DB::connection('pgsql')
            ->table('tb_data_pribadi')
            ->where('user_id', $user->user_id)
            ->exists();

        if ($exists) {
            DB::connection('pgsql')
                ->table('tb_data_pribadi')
                ->where('user_id', $user->user_id)
                ->update($updateData);
        } else {
            $updateData['dp_id']   = (string) \Illuminate\Support\Str::uuid();
            $updateData['user_id'] = $user->user_id;
            $updateData['email']   = $user->email;
            DB::connection('pgsql')
                ->table('tb_data_pribadi')
                ->insert($updateData);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Profile berhasil diupdate.',
        ]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'string', 'min:8', 'max:32', 'confirmed'],
        ], [
            'current_password.required' => 'Password lama wajib diisi.',
            'password.required'         => 'Password baru wajib diisi.',
            'password.min'              => 'Password baru minimal 8 karakter.',
            'password.confirmed'        => 'Konfirmasi password tidak cocok.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $user = JWTAuth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'status'  => false,
                'message' => 'Password lama tidak sesuai.',
            ], 422);
        }

        DB::connection('pgsql')
            ->table('tb_users')
            ->where('user_id', $user->user_id)
            ->update(['password' => Hash::make($request->password)]);

        return $this->successResponse(null, 'Password berhasil diubah.');
    }
}
