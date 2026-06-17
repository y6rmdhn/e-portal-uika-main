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
        $query = $this->model->with(['roles', 'unit']);

        // Filter by specific jabatan (role)
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
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('npm', 'like', "%{$search}%")
                    ->orWhere('nip', 'like', "%{$search}%")
                    ->orWhere('nidn', 'like', "%{$search}%");
            });
        }

        $perPage = $filters['per_page'] ?? 10;

        return $query->latest()->paginate($perPage);
    }

    public function findById(string $id)
    {
        return $this->model->with(['roles', 'unit'])->where('public_id', $id)->firstOrFail();
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
            'npm'       => $data['npm'] ?? null,
            'image'     => $data['image'] ?? null,
            'unit_id'   => $data['unit_id'] ?? null,
        ]);

        if (!empty($data['roles'])) {
            $user->syncRoles($data['roles']);
        }

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
            'npm'       => $data['npm'] ?? null,
            'image'     => $data['image'] ?? null,
        ], function ($value) {
            return !is_null($value);
        });

        if (array_key_exists('unit_id', $data)) {
            $updateData['unit_id'] = $data['unit_id'];
        }

        if (!empty($data['password'])) {
            $updateData['password'] = Hash::make($data['password']);
        }

        $user->update($updateData);

        // Sync roles jika ada perubahan
        if (isset($data['roles'])) {
            $user->syncRoles($data['roles']);
        }

        return $user->fresh(['roles', 'unit']);
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

    public function resetPassword(string $id, string $password): object
    {
        $user = $this->findById($id);
        $user->update(['password' => Hash::make($password)]);
        return $user->fresh('roles');
    }
}
