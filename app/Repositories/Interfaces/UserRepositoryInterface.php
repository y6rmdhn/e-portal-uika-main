<?php

namespace App\Repositories\Interfaces;

interface UserRepositoryInterface
{
    public function getAllUsers(array $filters = []);
    public function findById(string $id);
    public function findByEmail(string $email);
    public function create(array $data): object;
    public function update(string $id, array $data): object;
    public function delete(string $id): bool;
    public function toggleActive(string $id): object;
}
