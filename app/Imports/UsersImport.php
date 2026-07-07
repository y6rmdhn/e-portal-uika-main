<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\Jabatan;
use App\Models\Unit;
use App\Models\User;
use App\Models\UserJabatanUnit;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;

class UsersImport implements ToCollection, WithHeadingRow, SkipsOnError
{
    use SkipsErrors;

    public array $imported = [];
    public array $failed   = [];

    private array $jabatanMap = [
        'Mahasiswa'  => 102,
        'Dosen'      => 21,
        'Dosen_Ext'  => 21,
        // Pegawai tidak ada di sini — harus dari kolom Jabatan di CSV
    ];

    private function findJabatan(string $namaJabatan): ?Jabatan
    {
        // 1. Exact match (case-insensitive)
        $jabatan = Jabatan::whereRaw(
            "LOWER(nama_jabatan) = ?",
            [strtolower(trim($namaJabatan))]
        )->first();

        if ($jabatan) return $jabatan;

        // 2. LIKE match
        $jabatan = Jabatan::whereRaw(
            "LOWER(nama_jabatan) LIKE ?",
            ['%' . strtolower(trim($namaJabatan)) . '%']
        )->first();

        if ($jabatan) return $jabatan;

        // 3. Keyword match — tiap kata dicari
        $keywords = explode(' ', strtolower(trim($namaJabatan)));
        foreach ($keywords as $keyword) {
            if (strlen($keyword) > 2) {
                $jabatan = Jabatan::whereRaw(
                    "LOWER(nama_jabatan) LIKE ?",
                    ['%' . $keyword . '%']
                )->first();
                if ($jabatan) return $jabatan;
            }
        }

        return null;
    }

    /**
     * Hitung jabatan_id + model Jabatan berdasarkan role & kolom jabatan CSV.
     * Dipakai bareng-bareng oleh flow insert baru maupun restore soft-deleted user.
     */
    private function resolveJabatan(string $role, ?string $jabatan): array
    {
        $jabatanId    = null;
        $jabatanModel = null;

        if ($role === 'Pegawai' && $jabatan) {
            $jabatanModel = $this->findJabatan($jabatan);
            if ($jabatanModel) {
                $jabatanId = $jabatanModel->id;
            }
        } else {
            $jabatanId = $this->jabatanMap[$role] ?? null;
            if ($jabatanId) {
                $jabatanModel = Jabatan::find($jabatanId);
            }
        }

        return [$jabatanId, $jabatanModel];
    }

    /**
     * Assign role Spatie ke user + update role_id di tb_users.
     */
    private function applyJabatan(string $userId, string $email, ?int $jabatanId, ?Jabatan $jabatanModel): void
    {
        if (!$jabatanId || !$jabatanModel) {
            return;
        }

        $userModel = User::where('user_id', $userId)->first();

        if ($userModel) {
            try {
                $userModel->assignRole($jabatanModel->name);
            } catch (\Exception $e) {
                \Log::warning("Import: assign role gagal untuk {$email}: " . $e->getMessage());
            }
        }

        DB::connection('pgsql')
            ->table('tb_users')
            ->where('user_id', $userId)
            ->update(['role_id' => $jabatanId]);
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            try {
                // Validasi email
                if (empty($row['email']) || !filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
                    $this->failed[] = ['email' => $row['email'] ?? '?', 'reason' => 'Email tidak valid'];
                    continue;
                }

                $role     = in_array($row['role'], ['Mahasiswa', 'Dosen', 'Admin', 'Pegawai', 'Dosen_Ext'])
                    ? $row['role'] : 'Mahasiswa';
                $nidn     = (!empty($row['nidn']) && $row['nidn'] !== '-') ? trim($row['nidn']) : null;
                $npm      = (!empty($row['npm'])  && $row['npm']  !== '-') ? trim($row['npm'])  : null;
                $nama     = $row['nama']     ?? null;
                $password = !empty($row['password']) ? $row['password'] : 'password123';
                $jabatan  = !empty($row['jabatan']) ? trim($row['jabatan']) : null;
                $unit     = !empty($row['unit'])    ? trim($row['unit'])    : null;

                // Cek email sudah ada
                $existing = DB::connection('pgsql')
                    ->table('tb_users')
                    ->where('email', $row['email'])
                    ->first();

                if ($existing) {
                    if ($existing->deleted_at !== null) {
                        // ── Restore soft-deleted user ──
                        [$jabatanId, $jabatanModel] = $this->resolveJabatan($role, $jabatan);

                        DB::connection('pgsql')->table('tb_users')
                            ->where('email', $row['email'])
                            ->update([
                                'role'       => $role,
                                'nidn'       => $nidn,
                                'npm'        => $npm,
                                'role_id'    => $jabatanId, // <-- fix: role_id ikut diupdate
                                'deleted_at' => null,
                                'updated_at' => now(),
                            ]);

                        // <-- fix: assign ulang Spatie role juga
                        $this->applyJabatan($existing->user_id, $row['email'], $jabatanId, $jabatanModel);

                        // ── Auto-assign unit (restore) ──
                        if ($unit) {
                            $unitModel = Unit::where('code', $unit)->first();

                            if (!$unitModel) {
                                $unitModel = Unit::whereRaw(
                                    "LOWER(nama_unit) LIKE ?",
                                    ['%' . strtolower($unit) . '%']
                                )->first();
                            }

                            if ($unitModel && $jabatanId) {
                                UserJabatanUnit::firstOrCreate([
                                    'user_id'    => $existing->user_id,
                                    'jabatan_id' => $jabatanId,
                                    'unit_id'    => $unitModel->id,
                                ]);

                                DB::connection('pgsql')
                                    ->table('tb_users')
                                    ->where('user_id', $existing->user_id)
                                    ->update(['department_code' => $unitModel->code]);
                            }
                        }

                        $this->imported[] = $row['email'];
                    } else {
                        $this->failed[] = [
                            'email'  => $row['email'],
                            'reason' => 'Email sudah terdaftar.',
                        ];
                    }
                    continue;
                }

                // Cek NIDN duplikat
                if ($nidn) {
                    $nidnExists = DB::connection('pgsql')
                        ->table('tb_users')
                        ->whereRaw("TRIM(nidn) = ?", [$nidn])
                        ->whereNull('deleted_at')
                        ->exists();

                    if ($nidnExists) {
                        $this->failed[] = [
                            'email'  => $row['email'],
                            'reason' => "NIDN {$nidn} sudah terdaftar di sistem.",
                        ];
                        continue;
                    }
                }

                // Cek NPM duplikat
                if ($npm) {
                    $npmExists = DB::connection('pgsql')
                        ->table('tb_users')
                        ->whereRaw("TRIM(npm) = ?", [$npm])
                        ->whereNull('deleted_at')
                        ->exists();

                    if ($npmExists) {
                        $this->failed[] = [
                            'email'  => $row['email'],
                            'reason' => "NPM {$npm} sudah terdaftar di sistem.",
                        ];
                        continue;
                    }
                }

                $userId = Str::uuid()->toString();

                // Insert ke tb_users pgsql
                DB::connection('pgsql')->table('tb_users')->insert([
                    'user_id'    => $userId,
                    'email'      => $row['email'],
                    'password'   => Hash::make($password),
                    'role'       => $role,
                    'nidn'       => $nidn,
                    'npm'        => $npm,
                    'isverified' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Insert ke tb_data_pribadi
                DB::connection('pgsql')->table('tb_data_pribadi')->insert([
                    'dp_id'        => Str::uuid()->toString(),
                    'user_id'      => $userId,
                    'nama_lengkap' => $nama,
                    'email'        => $row['email'],
                ]);

                // ── Auto-assign jabatan ──
                [$jabatanId, $jabatanModel] = $this->resolveJabatan($role, $jabatan);

                if (!$jabatanModel && $role === 'Pegawai' && $jabatan) {
                    \Log::warning("Import: jabatan '{$jabatan}' tidak ditemukan untuk {$row['email']}");
                }

                $this->applyJabatan($userId, $row['email'], $jabatanId, $jabatanModel);

                // ── Auto-assign unit ──
                if ($unit) {
                    $unitModel = Unit::where('code', $unit)->first();

                    if (!$unitModel) {
                        $unitModel = Unit::whereRaw(
                            "LOWER(nama_unit) LIKE ?",
                            ['%' . strtolower($unit) . '%']
                        )->first();
                    }

                    if ($unitModel && $jabatanId) {
                        UserJabatanUnit::firstOrCreate([
                            'user_id'    => $userId,
                            'jabatan_id' => $jabatanId,
                            'unit_id'    => $unitModel->id,
                        ]);

                        DB::connection('pgsql')
                            ->table('tb_users')
                            ->where('user_id', $userId)
                            ->update(['department_code' => $unitModel->code]);
                    }
                }

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
