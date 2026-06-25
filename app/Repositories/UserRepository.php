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
        $query = User::with(['userJabatanUnits.unit', 'userJabatanUnits.jabatan', 'roles', 'unit'])
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
        return User::with(['userJabatanUnits.unit', 'userJabatanUnits.jabatan', 'roles', 'unit'])
            ->where('user_id', $id)
            ->whereNull('deleted_at')
            ->firstOrFail();
    }

    public function findByEmail(string $email)
    {
        return User::with(['userJabatanUnits.unit', 'userJabatanUnits.jabatan', 'roles', 'unit'])
            ->where('email', $email)
            ->whereNull('deleted_at')
            ->first();
    }

    public function create(array $data): object
    {
        $uuid = Str::uuid()->toString();

        $rolesList = $data['roles'] ?? [];
        $roleName = $data['role'] ?? ($rolesList[0] ?? null);

        $roleId = null;
        if ($roleName) {
            $jab = \App\Models\Jabatan::where('name', $roleName)->first();
            if ($jab) {
                $roleId = $jab->id;
            }
        }

        $deptCode = null;
        if (!empty($data['unit_id'])) {
            $unit = \App\Models\Unit::find($data['unit_id']);
            if ($unit) {
                $deptCode = $unit->code;
            }
        } elseif (!empty($data['department_code'])) {
            $deptCode = $data['department_code'];
        }

        $user = User::create([
            'user_id'         => $uuid,
            'email'           => $data['email'],
            'password'        => Hash::make($data['password']),
            'role'            => $roleName,
            'role_id'         => $roleId,
            'department_code' => $deptCode,
            'nidn'            => $data['nidn'] ?? null,
            'npm'             => $data['npm']  ?? null,
            'isverified'      => true,
        ]);

        // Sync local unit mapping
        $unitId = $data['unit_id'] ?? null;
        if (!$unitId && $deptCode) {
            $unit = \App\Models\Unit::where('code', $deptCode)->first();
            if ($unit) {
                $unitId = $unit->id;
            }
        }

        if ($unitId && !empty($rolesList)) {
            foreach ($rolesList as $rName) {
                $jabatan = \App\Models\Jabatan::where('name', $rName)->first();
                if ($jabatan) {
                    UserJabatanUnit::create([
                        'user_id'    => $uuid,
                        'jabatan_id' => $jabatan->id,
                        'unit_id'    => $unitId,
                    ]);
                }
            }
        }

        // Sync Spatie roles
        if (!empty($rolesList)) {
            $user->syncRoles($rolesList);
        }

        return $this->findById($uuid);
    }

    public function update(string $id, array $data): object
    {
        $user = $this->findById($id);

        $updateData = [];
        if (!empty($data['email']))    $updateData['email'] = $data['email'];
        if (!empty($data['nidn']))     $updateData['nidn']  = $data['nidn'];
        if (!empty($data['npm']))      $updateData['npm']   = $data['npm'];
        if (!empty($data['password'])) $updateData['password'] = Hash::make($data['password']);

        // Determine updated roles list first
        $rolesList = isset($data['roles']) ? $data['roles'] : null;

        // If roles list is updated, automatically set primary role to the first one in the list
        // (unless explicit role is provided)
        $roleName = $data['role'] ?? ($rolesList !== null && !empty($rolesList) ? $rolesList[0] : null);

        if ($roleName) {
            $updateData['role'] = $roleName;
            $jab = \App\Models\Jabatan::where('name', $roleName)->first();
            if ($jab) {
                $updateData['role_id'] = $jab->id;
            } else {
                $updateData['role_id'] = null;
            }
        }

        $deptCode = null;
        if (isset($data['unit_id'])) {
            if ($data['unit_id']) {
                $unit = \App\Models\Unit::find($data['unit_id']);
                $deptCode = $unit ? $unit->code : null;
            }
            $updateData['department_code'] = $deptCode;
        } elseif (isset($data['department_code'])) {
            $deptCode = $data['department_code'];
            $updateData['department_code'] = $deptCode;
        }

        if (!empty($updateData)) {
            $user->update($updateData);
        }

        if ($rolesList !== null) {
            $user->syncRoles($rolesList);
        }

        // Update local unit & role mapping
        if (isset($data['unit_id']) || isset($data['department_code']) || isset($data['roles'])) {
            $unitId = null;
            if (isset($data['unit_id'])) {
                $unitId = $data['unit_id'];
            } elseif (isset($data['department_code'])) {
                $unit = \App\Models\Unit::where('code', $data['department_code'])->first();
                $unitId = $unit ? $unit->id : null;
            } else {
                $unitId = $user->unit_id;
            }

            if ($rolesList === null) {
                $rolesList = $user->roles->pluck('name')->toArray();
            }

            UserJabatanUnit::where('user_id', $id)->delete();
            if ($unitId && !empty($rolesList)) {
                foreach ($rolesList as $rName) {
                    $jab = \App\Models\Jabatan::where('name', $rName)->first();
                    if ($jab) {
                        UserJabatanUnit::create([
                            'user_id'    => $id,
                            'jabatan_id' => $jab->id,
                            'unit_id'    => $unitId,
                        ]);
                    }
                }
            }
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