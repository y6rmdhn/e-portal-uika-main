<?php

namespace App\Repositories;

use App\Models\User;
use App\Models\UserJabatanUnit;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserRepository implements UserRepositoryInterface
{
    public function getAllUsers(array $filters = [])
    {
        $query = User::with(['userJabatanUnits.unit', 'userJabatanUnits.jabatan', 'roles'])
            ->whereNull('deleted_at');

        if (!empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                  ->orWhere('nidn', 'like', "%{$search}%")
                  ->orWhere('npm', 'like', "%{$search}%");
            });
        }

        $perPage = $filters['per_page'] ?? 10;
        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    public function findById(string $id)
    {
        return User::with(['userJabatanUnits.unit', 'userJabatanUnits.jabatan', 'roles'])
            ->where('user_id', $id)
            ->whereNull('deleted_at')
            ->firstOrFail();
    }

    public function findByEmail(string $email)
    {
        return User::with(['userJabatanUnits.unit', 'userJabatanUnits.jabatan', 'roles'])
            ->where('email', $email)
            ->whereNull('deleted_at')
            ->first();
    }

    public function create(array $data): object
    {
        $uuid = Str::uuid()->toString();

        $user = User::create([
            'user_id'    => $uuid,
            'email'      => $data['email'],
            'password'   => Hash::make($data['password']),
            'role'       => $data['role'],
            'nidn'       => $data['nidn'] ?? null,
            'npm'        => $data['npm']  ?? null,
            'isverified' => true,
        ]);

        // Sync local unit mapping
        if (!empty($data['unit_id']) && !empty($data['roles'])) {
            $roleName = $data['roles'][0] ?? null;
            $jabatan = \App\Models\Jabatan::where('name', $roleName)->first();
            if ($jabatan) {
                UserJabatanUnit::firstOrCreate([
                    'user_id'    => $uuid,
                    'jabatan_id' => $jabatan->id,
                    'unit_id'    => $data['unit_id'],
                ]);
            }
        }

        // Sync Spatie roles
        if (!empty($data['roles'])) {
            $user->syncRoles($data['roles']);
        }

        return $this->findById($uuid);
    }

    public function update(string $id, array $data): object
    {
        $user = $this->findById($id);

        $updateData = [];
        if (!empty($data['email']))    $updateData['email'] = $data['email'];
        if (!empty($data['role']))     $updateData['role']  = $data['role'];
        if (!empty($data['nidn']))     $updateData['nidn']  = $data['nidn'];
        if (!empty($data['npm']))      $updateData['npm']   = $data['npm'];
        if (!empty($data['password'])) $updateData['password'] = Hash::make($data['password']);

        if (!empty($updateData)) {
            $user->update($updateData);
        }

        // Update local unit & role mapping
        if (isset($data['unit_id']) && isset($data['roles'])) {
            $roleName = $data['roles'][0] ?? null;
            $jabatan = \App\Models\Jabatan::where('name', $roleName)->first();
            if ($jabatan) {
                UserJabatanUnit::where('user_id', $id)->delete();
                UserJabatanUnit::create([
                    'user_id'    => $id,
                    'jabatan_id' => $jabatan->id,
                    'unit_id'    => $data['unit_id'],
                ]);
            }
        }

        // Sync Spatie roles
        if (isset($data['roles'])) {
            $user->syncRoles($data['roles']);
        }

        return $this->findById($id);
    }

    public function delete(string $id): bool
    {
        $user = $this->findById($id);
        $user->update(['deleted_at' => now()]);

        // Clean local mappings
        UserJabatanUnit::where('user_id', $id)->delete();
        $user->roles()->detach();

        return true;
    }

    public function toggleActive(string $id): object
    {
        $user = $this->findById($id);
        $user->update(['isverified' => !$user->isverified]);
        return $this->findById($id);
    }

    public function resetPassword(string $id, string $password): object
    {
        $user = $this->findById($id);
        $user->update(['password' => Hash::make($password)]);
        return $this->findById($id);
    }

    public function findByNidn(string $nidn)
    {
        return User::where('nidn', $nidn)->whereNull('deleted_at')->first();
    }

    public function findByNpm(string $npm)
    {
        return User::where('npm', $npm)->whereNull('deleted_at')->first();
    }
}