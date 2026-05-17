<?php

namespace App\Services;

use App\Mail\AdminResetPasswordMail;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tymon\JWTAuth\Facades\JWTAuth;

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
            $this->ensureEmailUnique($data['email']);

            if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
                $data['image'] = $this->uploadImage($data['image']);
            };

            return $this->userRepository->create($data);
        });
    }

    public function updateAdmin(string $id, array $data): object
    {
        return DB::transaction(function () use ($id, $data) {
            $user = $this->userRepository->findById($id);

            // Cek email unik kecuali milik user itu sendiri
            if (!empty($data['email']) && $data['email'] !== $user->email) {
                $this->ensureEmailUnique($data['email']);
            };

            if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
                // hapus foto lama jika ada
                if ($user->image) {
                    Storage::disk('public')->delete($user->image);
                }
                $data['image'] = $this->uploadImage($data['image']);
            }

            return $this->userRepository->update($id, $data);
        });
    }

    public function deleteAdmin(string $id): bool
    {
        return DB::transaction(function () use ($id) {
            $user = $this->userRepository->findById($id);

            // Hapus foto jika ada
            if ($user->image) {
                Storage::disk('public')->delete($user->image);
            }

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
