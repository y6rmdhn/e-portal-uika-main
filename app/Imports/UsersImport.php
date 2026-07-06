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

    // Mapping role → jabatan_id otomatis
    private array $jabatanMap = [
        'Mahasiswa'  => 102,
        'Dosen'      => 21,
        'Dosen_Ext'  => 21,
    ];

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            try {
                if (empty($row['email']) || !filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
                    $this->failed[] = ['email' => $row['email'] ?? '?', 'reason' => 'Email tidak valid'];
                    continue;
                }

                $role = in_array($row['role'], ['Mahasiswa', 'Dosen', 'Admin', 'Pegawai', 'Dosen_Ext'])
                    ? $row['role']
                    : 'Mahasiswa';

                $nidn = (!empty($row['nidn']) && $row['nidn'] !== '-') ? trim($row['nidn']) : null;
                $npm  = (!empty($row['npm'])  && $row['npm']  !== '-') ? trim($row['npm'])  : null;
                $nama = $row['nama'] ?? null;
                $password = !empty($row['password']) ? $row['password'] : 'password123';

                // Cek email sudah ada
                $existing = DB::connection('pgsql')
                    ->table('tb_users')
                    ->where('email', $row['email'])
                    ->first();

                if ($existing) {
                    if ($existing->deleted_at !== null) {
                        // Restore user yang soft deleted
                        DB::connection('pgsql')->table('tb_users')
                            ->where('email', $row['email'])
                            ->update([
                                'role'       => $role,
                                'nidn'       => $nidn,
                                'npm'        => $npm,
                                'deleted_at' => null,
                                'updated_at' => now(),
                            ]);
                        $this->imported[] = $row['email'];
                    } else {
                        $this->failed[] = ['email' => $row['email'], 'reason' => 'Email sudah terdaftar'];
                    }
                    continue;
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
                $jabatanId = null;

                // Pegawai — cari jabatan by nama dari kolom CSV
                if ($role === 'Pegawai' && !empty($row['jabatan'])) {
                    $jabatan = Jabatan::whereRaw(
                        "LOWER(nama_jabatan) LIKE ?", ['%' . strtolower(trim($row['jabatan'])) . '%']
                    )->first();
                    if ($jabatan) $jabatanId = $jabatan->id;
                }

                // Role lain — auto dari jabatanMap
                if (!$jabatanId) {
                    $jabatanId = $this->jabatanMap[$role] ?? null;
                }

                $newUser = User::where('user_id', $userId)->first();

                if ($jabatanId && $newUser) {
                    $jabatan = Jabatan::find($jabatanId);
                    if ($jabatan) {
                        try {
                            $newUser->assignRole($jabatan->name);
                        } catch (\Exception $e) {
                            \Log::warning("Import: assign role gagal untuk {$row['email']}: " . $e->getMessage());
                        }
                    }
                }

                // ── Auto-assign unit ──
                if (!empty($row['unit'])) {
                    $unit = Unit::where('code', trim($row['unit']))->first();

                    // Kalau gak ketemu by code, coba by nama
                    if (!$unit) {
                        $unit = Unit::whereRaw(
                            "LOWER(nama_unit) LIKE ?", ['%' . strtolower(trim($row['unit'])) . '%']
                        )->first();
                    }

                    if ($unit && $jabatanId && $newUser) {
                        UserJabatanUnit::firstOrCreate([
                            'user_id'    => $userId,
                            'jabatan_id' => $jabatanId,
                            'unit_id'    => $unit->id,
                        ]);

                        DB::connection('pgsql')
                            ->table('tb_users')
                            ->where('user_id', $userId)
                            ->update(['department_code' => $unit->code]);
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