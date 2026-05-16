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

class UserController extends Controller
{

    use ApiResponse;

    public function __construct(
        protected UserAdminService $service
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['role', 'is_active', 'search', 'per_page']);

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
            $data['is_active'] = filter_var($data['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN);
            $data['image'] = $request->file('image');


            $user = $this->service->createUser($data);

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
            $data['is_active'] = filter_var($data['is_active'] ?? null, FILTER_VALIDATE_BOOLEAN);
            $data['image'] = $request->file('image');

            $user = $this->service->updateAdmin($id, $data);

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
            $this->service->deleteAdmin($id);

            return $this->successResponse(
                null,
                'User deleted successfully'
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->errorResponse('User not found', 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete user: ' . $e->getMessage(), 500);
        }
    }

    public function resetPassword(ResetUserPasswordRequest $request, string $id): JsonResponse
    {
        try {
            $this->service->resetUserPassword($id, $request->validated()['password']);

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
        return Excel::download(new UsersExport($filters), 'users-' . now()->format('Y-m-d') . '.xlsx');
    }

    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'],
        ]);

        try {
            $import = new UsersImport();
            Excel::import($import, $request->file('file'));

            return $this->successResponse([
                'imported' => count($import->imported),
                'failed'   => $import->failed,
            ], 'Import selesai. ' . count($import->imported) . ' data berhasil diimport.');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal import: ' . $e->getMessage(), 500);
        }
    }
}
