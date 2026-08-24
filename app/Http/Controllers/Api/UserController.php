<?php

namespace App\Http\Controllers\Api;

use App\Exports\UsersExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAdminRequest;
use App\Http\Requests\UpdateAdminRequest;
use App\Http\Resources\UserAdminResource;
use App\Services\UserAdminService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\ResetUserPasswordRequest;
use App\Imports\UsersImport;
use Maatwebsite\Excel\Facades\Excel;
use App\Services\ActivityLogService;

class UserController extends Controller
{

    use ApiResponse;

    public function __construct(
        protected UserAdminService $service,
        protected ActivityLogService $activityLog,
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // $filters = $request->only(['role', 'is_active', 'search', 'per_page', 'unit_id']);

            $filters = $request->only(['role', 'is_active', 'search', 'per_page', 'unit_id', 'isverified']);

            $users = $this->service->getAllUsers($filters);

            return $this->paginatedResponse($users, 'Users retrieved successfully', UserAdminResource::class);
        } catch (\Throwable $th) {
            return $this->errorResponse('Failed to retrieve users: ', 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAdminRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $user = $this->service->createUser($data);

            // ← log
            $this->activityLog->logForCurrentUser(
                ActivityLogService::TYPE_USER_CREATE,
                "Membuat user baru: {$user->email}",
                ['user_id' => $user->user_id, 'role' => $user->role]
            );

            return $this->successResponse(
                new UserAdminResource($user),
                'User Berhasil dibuat',
                201
            );
        } catch (\Exception $e) {
            $code = $e->getCode() === 422 ? 422 : 500;
            return $this->errorResponse('Failed to create user: ' . $e->getMessage(), $code);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $user = $this->service->getAdminDetail($id);

            return $this->successResponse(
                new UserAdminResource($user),
                'User retrieved successfully'
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->errorResponse('User not found', 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve user: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAdminRequest $request, string $id): JsonResponse
    {
        try {
            $data = $request->validated();
            $user = $this->service->updateAdmin($id, $data);

            // ← log
            $this->activityLog->logForCurrentUser(
                ActivityLogService::TYPE_USER_UPDATE,
                "Mengupdate user: {$user->email}",
                ['user_id' => $user->user_id]
            );

            return $this->successResponse(
                new UserAdminResource($user),
                'User updated successfully'
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->errorResponse('User not found', 404);
        } catch (\Exception $e) {
            $code = $e->getCode() === 422 ? 422 : 500;
            return $this->errorResponse('Failed to update user: ' . $e->getMessage(), $code);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $user = $this->service->getAdminDetail($id);

            // ← log sebelum delete
            $this->activityLog->logForCurrentUser(
                ActivityLogService::TYPE_USER_DELETE,
                "Menghapus user: {$user->email}",
                ['user_id' => $user->user_id, 'role' => $user->role]
            );

            $this->service->deleteAdmin($id);

            return $this->successResponse(null, 'User deleted successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->errorResponse('User not found', 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete user: ' . $e->getMessage(), 500);
        }
    }

    public function resetPassword(ResetUserPasswordRequest $request, string $id): JsonResponse
    {
        try {
            $user = $this->service->getAdminDetail($id);
            $this->service->resetUserPassword($id, $request->validated()['password']);

            // ← log
            $this->activityLog->logForCurrentUser(
                ActivityLogService::TYPE_RESET_PASSWORD,
                "Reset password user: {$user->email}",
                ['user_id' => $user->user_id]
            );

            return $this->successResponse(null, 'Password berhasil direset dan email notifikasi telah dikirim.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->errorResponse('User not found', 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to reset password: ' . $e->getMessage(), 500);
        }
    }

    public function export(Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $filters = $request->only(['role', 'search']);

        // ← log manual
        try {
            $actor = \Tymon\JWTAuth\Facades\JWTAuth::user();
            if ($actor) {
                $this->activityLog->log(
                    ActivityLogService::TYPE_APP_ACCESS,
                    'Export data user',
                    $actor->user_id,
                    $actor->user_id,
                    ['filters' => $filters]
                );
            }
        } catch (\Exception $e) {
            // silent fail
        }

        return Excel::download(new UsersExport($filters), 'users-' . now()->format('Y-m-d') . '.xlsx');
    }

    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:5120'],
        ]);

        try {
            $import = new UsersImport();
            Excel::import($import, $request->file('file'));

            // ← log manual
            try {
                $actor = \Tymon\JWTAuth\Facades\JWTAuth::user();
                if ($actor) {
                    $this->activityLog->log(
                        ActivityLogService::TYPE_APP_ACCESS,
                        'Import data user: ' . count($import->imported) . ' berhasil, ' . count($import->failed) . ' gagal',
                        $actor->user_id,
                        $actor->user_id,
                        ['imported' => count($import->imported), 'failed' => count($import->failed)]
                    );
                }
            } catch (\Exception $e) {
                //
            }

            return $this->successResponse([
                'imported' => count($import->imported),
                'failed'   => $import->failed,
            ], 'Import selesai. ' . count($import->imported) . ' data berhasil diimport.');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal import: ' . $e->getMessage(), 500);
        }
    }

    public function activityLogs(Request $request, string $id): JsonResponse
    {
        try {
            $user    = $this->service->getAdminDetail($id);
            $filters = $request->only(['type', 'date_from', 'date_to', 'per_page']);
            $logs    = $this->activityLog->getByUser($user->user_id, $filters); // ← user_id

            return $this->paginatedResponse($logs, 'Activity logs retrieved', \App\Http\Resources\ActivityLogResource::class);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->errorResponse('User not found', 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve logs: ' . $e->getMessage(), 500);
        }
    }

    public function assignUnit(Request $request, string $id): JsonResponse
    {
        try {
            $request->validate([
                'unit_id' => 'required|integer|exists:m_unit,id',
            ]);

            $user = $this->service->updateAdmin($id, ['unit_id' => $request->unit_id]);

            // Log Aktivitas
            try {
                $unit = \App\Models\Unit::find($request->unit_id);
                $unitName = $unit ? "[{$unit->code}] {$unit->nama_unit}" : "ID {$request->unit_id}";
                $actor = \Tymon\JWTAuth\Facades\JWTAuth::user();
                $this->activityLog->log(
                    'unit_assign',
                    "Menugaskan unit {$unitName} ke user: {$user->email}",
                    $actor?->user_id,
                    $actor?->user_id,
                    ['user_id' => $user->user_id, 'unit_id' => $request->unit_id, 'unit_name' => $unitName]
                );
            } catch (\Exception $e) {
                // silent fail
            }

            return $this->successResponse(
                new UserAdminResource($user),
                'Unit assigned successfully'
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->errorResponse('User not found', 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to assign unit: ' . $e->getMessage(), 500);
        }
    }

    public function unassignUnit(string $id): JsonResponse
    {
        try {
            // Dapatkan info user sebelum unassign untuk log email
            $userBefore = $this->service->getAdminDetail($id);
            $oldUnitId = $userBefore->unit_id;
            $unitName = "ID {$oldUnitId}";
            if ($oldUnitId) {
                $unit = \App\Models\Unit::find($oldUnitId);
                if ($unit) {
                    $unitName = "[{$unit->code}] {$unit->nama_unit}";
                }
            }

            $user = $this->service->updateAdmin($id, ['unit_id' => null]);

            // Log Aktivitas
            try {
                $actor = \Tymon\JWTAuth\Facades\JWTAuth::user();
                $this->activityLog->log(
                    'unit_unassign',
                    "Mencabut unit {$unitName} dari user: {$user->email}",
                    $actor?->user_id,
                    $actor?->user_id,
                    ['user_id' => $user->user_id, 'old_unit_id' => $oldUnitId, 'unit_name' => $unitName]
                );
            } catch (\Exception $e) {
                // silent fail
            }

            return $this->successResponse(
                new UserAdminResource($user),
                'Unit unassigned successfully'
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->errorResponse('User not found', 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to unassign unit: ' . $e->getMessage(), 500);
        }
    }

    public function toggleActive(string $id): JsonResponse
    {
        try {
            $user = $this->service->toggleActive($id);
            $status = $user->isverified ? 'diaktifkan' : 'dinonaktifkan';

            // ← log
            $this->activityLog->logForCurrentUser(
                ActivityLogService::TYPE_USER_TOGGLE,
                "Akun user {$user->email} {$status}",
                ['user_id' => $user->user_id, 'isverified' => $user->isverified]
            );

            return $this->successResponse($user, 'Status akun berhasil diubah');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->errorResponse('User not found', 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to toggle active: ' . $e->getMessage(), 500);
        }
    }
}
