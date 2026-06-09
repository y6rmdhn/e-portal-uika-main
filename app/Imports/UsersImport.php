<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;

class UsersImport implements ToCollection, WithHeadingRow, SkipsOnError
{
    use SkipsErrors;

    public array $imported = [];
    public array $failed   = [];

    public function collection(Collection $rows): void
{
    foreach ($rows as $row) {
        try {
            if (empty($row['email']) || !filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            // Cek apakah email ada termasuk yang soft deleted
            $existing = DB::connection('ucl')->table('tb_users')
                ->where('email', $row['email'])
                ->first();

            if ($existing) {
                if ($existing->deleted_at !== null) {
                    // Restore user yang soft deleted
                    DB::connection('ucl')->table('tb_users')
                        ->where('email', $row['email'])
                        ->update([
                            'role'       => in_array($row['role'], ['Mahasiswa', 'Dosen', 'Admin']) ? $row['role'] : 'Mahasiswa',
                            'nidn'       => (!empty($row['nidn']) && $row['nidn'] !== '-') ? $row['nidn'] : null,
                            'npm'        => (!empty($row['npm'])  && $row['npm']  !== '-') ? $row['npm']  : null,
                            'deleted_at' => null,
                            'updated_at' => now(),
                        ]);
                    $this->imported[] = $row['email'];
                } else {
                    $this->failed[] = ['email' => $row['email'], 'reason' => 'Email sudah terdaftar'];
                }
                continue;
            }

            // Insert baru
            DB::connection('ucl')->table('tb_users')->insert([
                'user_id'    => Str::uuid()->toString(),
                'email'      => $row['email'],
                'password'   => Hash::make('password123'),
                'role'       => in_array($row['role'], ['Mahasiswa', 'Dosen', 'Admin']) ? $row['role'] : 'Mahasiswa',
                'nidn'       => (!empty($row['nidn']) && $row['nidn'] !== '-') ? $row['nidn'] : null,
                'npm'        => (!empty($row['npm'])  && $row['npm']  !== '-') ? $row['npm']  : null,
                'isverified' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->imported[] = $row['email'];

        } catch (\Exception $e) {
            $this->failed[] = [
                'email'  => $row['email'] ?? '?',
                'reason' => $e->getMessage(),
            ];
        }
    }
}

    public function rules(): array
    {
        return [];
    }
}