<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Jabatan;
use App\Models\Unit;

class JabatanUnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Seed m_jabatan (from dump file)
        $jabatans = [
            ['id' => 1, 'name' => 'Rektor', 'guard_name' => 'web', 'created_at' => '2024-09-16 23:50:59'],
            ['id' => 2, 'name' => 'Wakil Rektor 1', 'guard_name' => 'web', 'created_at' => '2024-09-16 23:51:05'],
            ['id' => 3, 'name' => 'Wakil Rektor 2', 'guard_name' => 'web', 'created_at' => '2024-09-16 23:51:18'],
            ['id' => 4, 'name' => 'Wakil Rektor 3', 'guard_name' => 'web', 'created_at' => '2024-09-16 23:51:29'],
            ['id' => 5, 'name' => 'Ketua Program Studi', 'guard_name' => 'web', 'created_at' => '2024-09-16 23:51:40'],
            ['id' => 6, 'name' => 'Kepala Laboratorium', 'guard_name' => 'web', 'created_at' => '2025-01-19 11:22:56'],
            ['id' => 7, 'name' => 'Dekan', 'guard_name' => 'web', 'created_at' => '2025-01-19 11:24:47'],
            ['id' => 8, 'name' => 'Wakil Dekan Bidang Akademik', 'guard_name' => 'web', 'created_at' => '2025-01-19 11:24:49'],
            ['id' => 9, 'name' => 'Walik Dekan Bidang Sumber Daya', 'guard_name' => 'web', 'created_at' => '2025-01-19 11:25:05'],
            ['id' => 10, 'name' => 'Wakil Dekan Bidang Kemahasiswaaan', 'guard_name' => 'web', 'created_at' => '2025-01-19 11:25:45'],
            ['id' => 11, 'name' => 'Kepala Bagian Tata Usaha', 'guard_name' => 'web', 'created_at' => '2025-01-19 11:25:59'],
            ['id' => 12, 'name' => 'Kepala Sub Bagian Administrasi Umum Keuangan Fasilitas dan Properti', 'guard_name' => 'web', 'created_at' => '2025-01-19 11:26:09'],
            ['id' => 13, 'name' => 'Kepala Sub Bagian Akademik', 'guard_name' => 'web', 'created_at' => '2025-01-19 11:26:19'],
            ['id' => 14, 'name' => 'Staf Keuangan', 'guard_name' => 'web', 'created_at' => '2025-01-19 11:26:29'],
            ['id' => 15, 'name' => 'STAF IT', 'guard_name' => 'web', 'created_at' => '2025-01-19 11:26:37'],
            ['id' => 16, 'name' => 'Staf Akademik', 'guard_name' => 'web', 'created_at' => '2025-01-19 11:32:39'],
            ['id' => 17, 'name' => 'Staff Fasilitas & Properti', 'guard_name' => 'web', 'created_at' => '2025-01-19 11:32:47'],
            ['id' => 18, 'name' => 'Staf Tata Usaha', 'guard_name' => 'web', 'created_at' => '2025-01-19 11:32:54'],
            ['id' => 19, 'name' => 'Laboran', 'guard_name' => 'web', 'created_at' => '2025-01-19 11:33:02'],
            ['id' => 20, 'name' => 'Sekretaris Program Studi', 'guard_name' => 'web', 'created_at' => '2025-01-19 11:33:58'],
            ['id' => 21, 'name' => 'dosen', 'guard_name' => 'web', 'created_at' => '2025-01-19 11:34:15'],
            ['id' => 22, 'name' => 'Gugus Penjaminan Mutu', 'guard_name' => 'web', 'created_at' => '2025-01-19 11:37:06'],
            ['id' => 23, 'name' => 'Kepala Bagian Hukum Tata Negara', 'guard_name' => 'web', 'created_at' => '2025-01-19 11:43:04'],
            ['id' => 24, 'name' => 'Kepala Bagian Hukum Perdata', 'guard_name' => 'web', 'created_at' => '2025-01-19 11:43:25'],
            ['id' => 25, 'name' => 'Kepala Bagian Hukum Pidana', 'guard_name' => 'web', 'created_at' => '2025-01-19 11:43:40'],
            ['id' => 26, 'name' => 'Kepala Bagian Hukum Lingkungan', 'guard_name' => 'web', 'created_at' => '2025-01-19 11:43:52'],
            ['id' => 27, 'name' => 'Kepala Sub Bagian AAKA', 'guard_name' => 'web', 'created_at' => '2025-01-19 11:44:10'],
            ['id' => 28, 'name' => 'Kepala Sub Bagian AUFKP', 'guard_name' => 'web', 'created_at' => '2025-01-19 11:44:31'],
            ['id' => 29, 'name' => 'Staff Akademik', 'guard_name' => 'web', 'created_at' => '2025-01-19 11:44:45'],
            ['id' => 30, 'name' => 'Staff Umum', 'guard_name' => 'web', 'created_at' => '2025-01-19 11:44:50'],
            ['id' => 31, 'name' => 'Staff Keuangan', 'guard_name' => 'web', 'created_at' => '2025-01-19 11:45:00'],
            ['id' => 32, 'name' => 'Staff S2', 'guard_name' => 'web', 'created_at' => '2025-01-19 11:45:09'],
        ];

        foreach ($jabatans as $jabatan) {
            Jabatan::updateOrCreate(['id' => $jabatan['id']], [
                'name' => $jabatan['name'],
                'nama_jabatan' => $jabatan['name'],
                'guard_name' => $jabatan['guard_name'],
                'created_at' => $jabatan['created_at'],
            ]);
        }

        // 2. Seed m_unit (from dump file)
        $units = [
            ['id' => 1, 'code' => 'FT_TI', 'nama_unit' => 'Teknik Informatika', 'created_at' => '2024-09-16 23:51:56', 'updated_at' => null],
            ['id' => 2, 'code' => 'FT_TS', 'nama_unit' => 'Teknik Sipil', 'created_at' => '2024-10-18 00:18:34', 'updated_at' => null],
            ['id' => 3, 'code' => 'Univ', 'nama_unit' => 'Universitas', 'created_at' => '2024-10-21 04:13:40', 'updated_at' => null],
            ['id' => 4, 'code' => 'FTS', 'nama_unit' => 'Fakultas Teknik dan Sains', 'created_at' => '2024-10-21 04:13:51', 'updated_at' => null],
            ['id' => 5, 'code' => 'FH', 'nama_unit' => 'Fakultas Hukum', 'created_at' => '2024-10-21 04:14:01', 'updated_at' => null],
            ['id' => 6, 'code' => 'FKIP', 'nama_unit' => 'Fakultas Keguruan dan Ilmu Pendidikan', 'created_at' => '2024-10-21 04:14:23', 'updated_at' => null],
            ['id' => 7, 'code' => 'FEB', 'nama_unit' => 'Fakultas Ekonomi dan Bisnis', 'created_at' => '2024-10-21 04:14:51', 'updated_at' => null],
            ['id' => 8, 'code' => 'FAI', 'nama_unit' => 'Fakultas Agama Islam', 'created_at' => '2024-10-21 04:15:24', 'updated_at' => null],
            ['id' => 9, 'code' => 'FIKES', 'nama_unit' => 'Fakultas Ilmu Kesehatan', 'created_at' => '2024-10-21 04:15:36', 'updated_at' => null],
            ['id' => 10, 'code' => 'BAAK', 'nama_unit' => 'Biro Administrasi Akademik dan Kemahasiswaan', 'created_at' => '2024-10-21 04:16:36', 'updated_at' => null],
            ['id' => 11, 'code' => 'BASK', 'nama_unit' => 'Biro Aset, Sumberdaya, dan Kerjasama', 'created_at' => '2024-10-21 04:16:59', 'updated_at' => null],
            ['id' => 12, 'code' => 'BPPSI', 'nama_unit' => 'Biro Perencanaan, Pelaporan, dan Sistem Informasi', 'created_at' => '2024-10-21 04:17:19', 'updated_at' => null],
            ['id' => 13, 'code' => 'CC', 'nama_unit' => 'Unit Career Center', 'created_at' => '2024-10-21 04:17:48', 'updated_at' => null],
            ['id' => 14, 'code' => 'ICO', 'nama_unit' => 'Unit International Collaboration Office', 'created_at' => '2024-10-21 04:18:18', 'updated_at' => null],
            ['id' => 15, 'code' => 'FTS_TM', 'nama_unit' => 'Teknik Mesin', 'created_at' => '2024-10-21 04:18:46', 'updated_at' => null],
            ['id' => 16, 'code' => 'FTS_TE', 'nama_unit' => 'Teknik Elektro', 'created_at' => '2024-10-21 04:19:09', 'updated_at' => null],
            ['id' => 17, 'code' => 'FH_IH', 'nama_unit' => 'Ilmu Hukum', 'created_at' => '2024-10-21 04:20:09', 'updated_at' => null],
            ['id' => 18, 'code' => 'LPPM', 'nama_unit' => 'Lembaga Penelitian dan Pengabdian Masyarakat', 'created_at' => '2024-10-21 04:21:00', 'updated_at' => null],
            ['id' => 19, 'code' => 'KPMAI', 'nama_unit' => 'Kantor Penjamin Mutu Audit Internal', 'created_at' => '2024-10-21 04:21:19', 'updated_at' => null],
            ['id' => 20, 'code' => 'SPS', 'nama_unit' => 'Sekolah Pascasarjana', 'created_at' => '2024-10-22 03:55:12', 'updated_at' => null],
            ['id' => 21, 'code' => 'SPS_MTP', 'nama_unit' => 'Magister Teknologi Pendidikan', 'created_at' => '2024-10-22 03:55:36', 'updated_at' => null],
            ['id' => 22, 'code' => 'FH_MHB', 'nama_unit' => 'Hukum Bisnis (S2)', 'created_at' => '2025-02-18 13:18:40', 'updated_at' => null],
        ];

        foreach ($units as $unit) {
            Unit::updateOrCreate(['id' => $unit['id']], $unit);
        }

        // 3. Assign default unit to existing test users
        $admin = User::where('email', 'admin@gmail.com')->first();
        if ($admin) {
            $admin->update(['unit_id' => 3]); // Univ
        }

        $dosen = User::where('email', 'dosen@gmail.com')->first();
        if ($dosen) {
            $dosen->update(['unit_id' => 1]); // Teknik Informatika
        }
    }
}
