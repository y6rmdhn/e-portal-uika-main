<?php

namespace App\Repositories;

use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserRepository implements UserRepositoryInterface
{
    private function ucl()
    {
        return DB::connection('ucl')->table('tb_users');
    }

    public function getAllUsers(array $filters = [])
    {
        $query = $this->ucl()->whereNull('deleted_at');

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
        $page    = request()->get('page', 1);
        $total   = $query->count();
        $items   = $query->orderByDesc('created_at')
                         ->offset(($page - 1) * $perPage)
                         ->limit($perPage)
                         ->get();

        // Bungkus jadi LengthAwarePaginator biar kompatibel sama paginatedResponse
        return new \Illuminate\Pagination\LengthAwarePaginator(
            $items, $total, $perPage, $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    public function findById(string $id)
    {
        $user = $this->ucl()
            ->where('user_id', $id)
            ->whereNull('deleted_at')
            ->first();

        if (!$user) throw new \Illuminate\Database\Eloquent\ModelNotFoundException();
        return $user;
    }

    public function findByEmail(string $email)
    {
        return $this->ucl()->where('email', $email)->whereNull('deleted_at')->first();
    }

    public function create(array $data): object
    {
        $uuid = Str::uuid()->toString();

        $this->ucl()->insert([
            'user_id'    => $uuid,
            'email'      => $data['email'],
            'password'   => Hash::make($data['password']),
            'role'       => $data['role'],
            'nidn'       => $data['nidn'] ?? null,
            'npm'        => $data['npm']  ?? null,
            'isverified' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->findById($uuid);
    }

    public function update(string $id, array $data): object
    {
        $updateData = ['updated_at' => now()];

        if (!empty($data['email']))    $updateData['email'] = $data['email'];
        if (!empty($data['role']))     $updateData['role']  = $data['role'];
        if (!empty($data['nidn']))     $updateData['nidn']  = $data['nidn'];
        if (!empty($data['npm']))      $updateData['npm']   = $data['npm'];
        if (!empty($data['password'])) $updateData['password'] = Hash::make($data['password']);

        $this->ucl()->where('user_id', $id)->update($updateData);

        return $this->findById($id);
    }

    public function delete(string $id): bool
    {
        // Soft delete
        $this->ucl()->where('user_id', $id)->update([
            'deleted_at' => now(),
            'updated_at' => now(),
        ]);
        return true;
    }

    public function toggleActive(string $id): object
    {
        // tb_users UCL ga punya kolom is_active, skip atau tambah kolom
        // untuk sekarang return user aja
        return $this->findById($id);
    }

    public function resetPassword(string $id, string $password): object
    {
        $this->ucl()->where('user_id', $id)->update([
            'password'   => Hash::make($password),
            'updated_at' => now(),
        ]);
        return $this->findById($id);
    }

    public function findByNidn(string $nidn)
{
    return $this->ucl()
        ->where('nidn', $nidn)
        ->whereNull('deleted_at')
        ->first();
}

public function findByNpm(string $npm)
{
    return $this->ucl()
        ->where('npm', $npm)
        ->whereNull('deleted_at')
        ->first();
}
}