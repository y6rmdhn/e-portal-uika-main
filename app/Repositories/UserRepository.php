<?php

namespace App\Repositories;

use App\Models\User;
use App\Models\UserJabatanUnit;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserRepository implements UserRepositoryInterface
{
    public function getAllUsers(array $filters = [])
    {
        $query = User::with(['userJabatanUnits.unit', 'userJabatanUnits.jabatan', 'roles', 'unit', 'dataPribadi'])
            ->whereNull('deleted_at');

        if (!empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        if (!empty($filters['unit_id'])) {
            $query->where(function ($q) use ($filters) {
                $q->whereHas('unit', function ($uq) use ($filters) {
                    $uq->where('id', $filters['unit_id']);
                })->orWhereHas('userJabatanUnits', function ($uq) use ($filters) {
                    $uq->where('unit_id', $filters['unit_id']);
                });
            });
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                    ->orWhere('nidn', 'like', "%{$search}%")
                    ->orWhere('npm', 'like', "%{$search}%");
            });
        }

        if (isset($filters['isverified']) && $filters['isverified'] !== '') {
            $query->where('isverified', (bool) $filters['isverified']);
        }

        $perPage = $filters['per_page'] ?? 10;
        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    public function findById(string $id)
    {
        return User::with(['userJabatanUnits.unit', 'userJabatanUnits.jabatan', 'roles', 'unit', 'dataPribadi'])
            ->where('user_id', $id)
            ->whereNull('deleted_at')
            ->firstOrFail();
    }

    public function findByEmail(string $email)
    {
        return User::with(['userJabatanUnits.unit', 'userJabatanUnits.jabatan', 'roles', 'unit', 'dataPribadi'])
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
        $rolesList = array_key_exists('roles', $data) ? $data['roles'] : null;

        // If roles list is updated, automatically set primary role to the first one in the list
        // (unless explicit role is provided)
        if ($rolesList !== null) {
            if (empty($rolesList)) {
                // If roles list is emptied, we should still enforce a default fallback role since the database requires non-null 'role'
                // However, our frontend now blocks submitting empty roles. As a fallback:
                $updateData['role'] = '';
                $updateData['role_id'] = null;
            } else {
                $roleName = $data['role'] ?? $rolesList[0];
                $updateData['role'] = $roleName;
                $jab = \App\Models\Jabatan::where('name', $roleName)->first();
                $updateData['role_id'] = $jab ? $jab->id : null;
            }
        }

        $deptCode = null;
        if (array_key_exists('unit_id', $data)) {
            if ($data['unit_id']) {
                $unit = \App\Models\Unit::find($data['unit_id']);
                $deptCode = $unit ? $unit->code : null;
            }
            $updateData['department_code'] = $deptCode;
        } elseif (array_key_exists('department_code', $data)) {
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
        if (array_key_exists('unit_id', $data) || array_key_exists('department_code', $data) || $rolesList !== null) {
            $unitId = null;
            if (array_key_exists('unit_id', $data)) {
                $unitId = $data['unit_id'];
            } elseif (array_key_exists('department_code', $data)) {
                $unit = \App\Models\Unit::where('code', $data['department_code'])->first();
                $unitId = $unit ? $unit->id : null;
            } else {
                $unitId = $user->unit?->id;
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

        \Cache::forget('jwt_user_id_' . $id);

        return $this->findById($id);
    }

    public function delete(string $id): bool
    {
        $this->findById($id); // validasi user ada, kalau gak ada throw ModelNotFoundException

        // Soft delete langsung ke pgsql
        DB::connection('pgsql')
            ->table('tb_users')
            ->where('user_id', $id)
            ->update(['deleted_at' => now()]);

        // Clean local mappings di MySQL eportal
        UserJabatanUnit::where('user_id', $id)->delete();

        try {
            $user = User::where('user_id', $id)->first();
            $user?->roles()->detach();
        } catch (\Exception $e) {
            // ignore
        }

        return true;
    }

    public function toggleActive(string $id): object
    {
        $user = $this->findById($id);

        DB::connection('pgsql')
            ->table('tb_users')
            ->where('user_id', $id)
            ->update(['isverified' => !$user->isverified]);

        \Cache::forget('jwt_user_id_' . $id);

        return $this->findById($id);
    }

    public function resetPassword(string $id, string $password): object
    {
        $user = $this->findById($id);
        $user->update(['password' => Hash::make($password)]);
        \Cache::forget('jwt_user_id_' . $id);

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
