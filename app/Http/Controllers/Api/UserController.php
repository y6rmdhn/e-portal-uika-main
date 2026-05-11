<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAdminRequest;
use App\Http\Requests\UpdateAdminRequest;
use App\Http\Resources\UserAdminResource;
use App\Services\UserAdminService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
}
