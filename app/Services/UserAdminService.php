<?php

namespace App\Services;

use App\Mail\AdminResetPasswordMail;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;

class UserAdminService
{
    public function __construct(
        protected UserRepositoryInterface $userRepository,
        protected ActivityLogService $activityLog,
    ) {}

    public function getAllUsers(array $filters = [])
    {
        return $this->userRepository->getAllUsers($filters);
    }

    public function getAdminDetail(string $id)
    {
        return $this->userRepository->findById($id);
    }

    public function createUser(array $data): object
{
    return DB::transaction(function () use ($data) {
        // Cek apakah email ada termasuk soft deleted
        $existing = DB::connection('ucl')->table('tb_users')
            ->where('email', $data['email'])
            ->first();

        if ($existing) {
            if ($existing->deleted_at !== null) {
                // Restore user soft deleted
                DB::connection('ucl')->table('tb_users')
                    ->where('email', $data['email'])
                    ->update([
                        'password'   => Hash::make($data['password']),
                        'role'       => $data['role'],
                        'nidn'       => $data['nidn'] ?? null,
                        'npm'        => $data['npm']  ?? null,
                        'deleted_at' => null,
                        'updated_at' => now(),
                    ]);
                return $this->userRepository->findById($existing->user_id);
            }
            throw new \Exception("Email {$data['email']} sudah terdaftar.", 422);
        }

        // Cek duplikat NIDN
        if (!empty($data['nidn']) && $this->userRepository->findByNidn($data['nidn'])) {
            throw new \Exception("NIDN {$data['nidn']} sudah terdaftar.", 422);
        }

        // Cek duplikat NPM
        if (!empty($data['npm']) && $this->userRepository->findByNpm($data['npm'])) {
            throw new \Exception("NPM {$data['npm']} sudah terdaftar.", 422);
        }

        return $this->userRepository->create($data);
    });
}

    public function updateAdmin(string $id, array $data): object
{
    return DB::transaction(function () use ($id, $data) {
        $user = $this->userRepository->findById($id);

        // Cek email unik kecuali milik user itu sendiri
        if (!empty($data['email']) && $data['email'] !== $user->email) {
            if ($this->userRepository->findByEmail($data['email'])) {
                throw new \Exception("Email {$data['email']} sudah terdaftar.", 422);
            }
        }

        // Cek NIDN unik
        if (!empty($data['nidn']) && $data['nidn'] !== trim($user->nidn ?? '')) {
            if ($this->userRepository->findByNidn($data['nidn'])) {
                throw new \Exception("NIDN {$data['nidn']} sudah terdaftar.", 422);
            }
        }

        // Cek NPM unik
        if (!empty($data['npm']) && $data['npm'] !== trim($user->npm ?? '')) {
            if ($this->userRepository->findByNpm($data['npm'])) {
                throw new \Exception("NPM {$data['npm']} sudah terdaftar.", 422);
            }
        }

        return $this->userRepository->update($id, $data);
    });
}

    public function deleteAdmin(string $id): bool
{
    return DB::transaction(function () use ($id) {
        return $this->userRepository->delete($id);
    });
}

    public function toggleActive(string $id): object
    {
        return $this->userRepository->toggleActive($id);
    }

    public function resetUserPassword(string $id, string $password): object
    {
        return DB::transaction(function () use ($id, $password) {
            $user  = $this->userRepository->findById($id);
            $admin = JWTAuth::user();

            $result = $this->userRepository->resetPassword($id, $password);

            // Kirim email notifikasi ke user
            Mail::to($user->email)->send(new AdminResetPasswordMail(
                userName: $user->name,
                newPassword: $password,
                adminName: $admin->name,
            ));

            $this->activityLog->log(
                ActivityLogService::TYPE_RESET_PASSWORD,
                "Password direset oleh administrator {$admin->name}",
                userId: $user->id,
                actorId: $admin->id,
                metadata: ['admin_name' => $admin->name, 'admin_email' => $admin->email],
            );

            return $result;
        });
    }

    // ─── Private Helpers ──────────────────────────────────────────────────────

    private function ensureEmailUnique(string $email): void
    {
        if ($this->userRepository->findByEmail($email)) {
            throw new \Exception("Email {$email} sudah terdaftar.", 422);
        }
    }

    private function uploadImage(UploadedFile $file): string
    {
        return $file->store('admins/photos', 'public');
    }
}
