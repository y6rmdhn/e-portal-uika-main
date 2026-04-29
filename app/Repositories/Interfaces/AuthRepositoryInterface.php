<?php

namespace App\Repositories\Interfaces;

use App\Models\User;

interface AuthRepositoryInterface
{
    public function findByEmail(string $email): ?User;

    public function create(array $data): User;

    public function updateLastLogin(string $userId): void;

    /**
     * Cari user berdasarkan email Google.
     * Return ['user' => User, 'is_new' => bool].
     * is_new = true berarti email belum ada → frontend harus redirect ke register.
     */
    public function findByEmailGoogle(string $email): ?User;
}
