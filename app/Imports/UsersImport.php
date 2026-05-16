<?php

namespace App\Imports;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;

class UsersImport implements ToCollection, WithHeadingRow, WithValidation, SkipsOnError
{
    use SkipsErrors;

    public array $imported = [];
    public array $failed   = [];

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            try {
                // skip baris kosong atau baris yang email-nya bukan email valid
                if (empty($row['email']) || !filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
                    continue;
                }

                if (User::where('email', $row['email'])->exists()) {
                    $this->failed[] = ['email' => $row['email'], 'reason' => 'Email sudah terdaftar'];
                    continue;
                }

                $user = User::create([
                    'name'      => $row['nama'],
                    'email'     => $row['email'],
                    'password'  => Hash::make('password123'),
                    'npm'       => (!empty($row['npm']) && $row['npm'] !== '-') ? $row['npm'] : null,
                    'nip'       => (!empty($row['nip']) && $row['nip'] !== '-') ? $row['nip'] : null,
                    'nidn'      => (!empty($row['nidn']) && $row['nidn'] !== '-') ? $row['nidn'] : null,
                    'phone'     => (!empty($row['no_hp']) && $row['no_hp'] !== '-') ? $row['no_hp'] : null,
                    'location'  => (!empty($row['lokasi']) && $row['lokasi'] !== '-') ? $row['lokasi'] : null,
                    'is_active' => ($row['status'] ?? '') === 'Aktif',
                ]);

                $role = in_array($row['role'], ['admin', 'dosen', 'mahasiswa'])
                    ? $row['role'] : 'mahasiswa';

                $user->assignRole($role);
                $this->imported[] = $row['email'];
            } catch (\Exception $e) {
                $this->failed[] = [
                    'email'  => $row['email'] ?? '?',
                    'reason' => $e->getMessage(),
                ];
            }
        }
    }
    // Hapus rules() atau kosongkan — validasi manual di collection()
    public function rules(): array
    {
        return [];
    }
}
