<?php

namespace App\Repositories;

use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserRepository implements UserRepositoryInterface
{
    protected User $model;

    public function __construct(User $model)
    {
        $this->model = $model;
    }

    public function getAllUsers(array $filters = [])
    {
        $adminRoles = ['mahasiswa', 'admin', 'dosen'];

        $query = $this->model
            ->with(['roles'])
            ->whereHas('roles', fn($q) => $q->whereIn('name', $adminRoles));

        // Filter by specific role
        if (!empty($filters['role'])) {
            $query->whereHas('roles', fn($q) => $q->where('name', $filters['role']));
        }

        // Filter by active status
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        // Search by name or email
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $perPage = $filters['per_page'] ?? 10;

        return $query->latest()->paginate($perPage);
    }

    public function findById(string $id)
    {
        return $this->model->with('roles')->where('public_id', $id)->firstOrFail();
    }

    public function findByEmail(string $email)
    {
        return $this->model->where('email', $email)->first();
    }

    public function create(array $data): object
    {
        $user = $this->model->create([
            'name'      => $data['name'],
            'email'     => $data['email'],
            'password'  => Hash::make($data['password']),
            'phone'     => $data['phone'] ?? null,
            'location'  => $data['location'] ?? null,
            'about_me'  => $data['about_me'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'nidn'      => $data['nidn'] ?? null,
            'nip'       => $data['nip'] ?? null,
        ]);

        $user->assignRole($data['role']);

        return $user->load('roles');
    }

    public function update(string $id, array $data): object
    {
        $user = $this->findById($id);

        $updateData = array_filter([
            'name'      => $data['name'] ?? null,
            'email'     => $data['email'] ?? null,
            'phone'     => $data['phone'] ?? null,
            'location'  => $data['location'] ?? null,
            'about_me'  => $data['about_me'] ?? null,
            'is_active' => $data['is_active'] ?? null,
            'nidn'      => $data['nidn'] ?? null,
            'nip'       => $data['nip'] ?? null,
        ], function ($value) {
            return !is_null($value);
        });

        if (!empty($data['password'])) {
            $updateData['password'] = Hash::make($data['password']);
        }

        $user->update($updateData);

        // Sync role jika ada perubahan
        if (!empty($data['role'])) {
            $user->syncRoles([$data['role']]);
        }

        return $user->fresh('roles');
    }

    public function delete(string $id): bool
    {
        $user = $this->findById($id);
        $user->roles()->detach();
        return $user->delete();
    }

    public function toggleActive(string $id): object
    {
        $user = $this->findById($id);
        $user->update(['is_active' => !$user->is_active]);
        return $user->fresh('roles');
    }
}
