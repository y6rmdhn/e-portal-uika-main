<?php

namespace Database\Seeders;

use App\Models\SsoClient;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class SsoClientSeeder extends Seeder
{
    /**
     * Seed data awal SSO Client untuk sub-aplikasi UIKA.
     *
     * PENTING: Secret yang ditampilkan di sini hanya muncul SEKALI saat seeder dijalankan.
     * Simpan di tempat yang aman (password manager, vault). Tidak bisa di-recover dari DB.
     */
    public function run(): void
    {
        $clients = [
            [
                'name'               => 'SIAKAD UIKA',
                'description'        => 'Sistem Informasi Akademik Universitas Ibn Khaldun Bogor',
                'callback_url'       => 'https://siakad-uika.ac.id',
                'allowed_module_ids' => null, // null = akses semua modul (karena SIAKAD adalah app utama)
                'app_module_name'    => 'SIAKAD (Akademik)',
            ],
            [
                'name'               => 'E-Library UIKA',
                'description'        => 'Sistem Perpustakaan Digital UIKA',
                'callback_url'       => 'https://e-library-uika.ac.id',
                'allowed_module_ids' => null,
                'app_module_name'    => 'E-Library UIKA',
            ],
            [
                'name'               => 'Portal Keuangan UIKA',
                'description'        => 'Sistem Informasi Keuangan dan Pembayaran Mahasiswa',
                'callback_url'       => 'https://portal-keuangan-uika.ac.id',
                'allowed_module_ids' => null,
                'app_module_name'    => 'Portal Keuangan',
            ],
        ];

        $this->command->info('');
        $this->command->info('========================================');
        $this->command->info('  SSO Client Credentials (SIMPAN INI!)');
        $this->command->info('  Secret hanya tampil sekali!');
        $this->command->info('========================================');

        foreach ($clients as $clientData) {
            // Cek apakah sudah ada (idempotent)
            $existing = SsoClient::where('name', $clientData['name'])->first();
            if ($existing) {
                $this->command->warn("  [SKIP] {$clientData['name']} sudah ada.");
                continue;
            }

            // Find matching app_module
            $appModule = \App\Models\AppModule::where('name', $clientData['app_module_name'])->first();

            // Generate credentials baru
            $plainSecret = Str::random(64);

            $client = SsoClient::create([
                'app_module_id'      => $appModule ? $appModule->id : null,
                'name'               => $clientData['name'],
                'client_id'          => (string) Str::uuid(),
                'client_secret'      => Hash::make($plainSecret),
                'description'        => $clientData['description'],
                'callback_url'       => $clientData['callback_url'],
                'allowed_module_ids' => $clientData['allowed_module_ids'],
                'is_active'          => true,
            ]);

            $this->command->info('');
            $this->command->info("  App      : {$client->name}");
            $this->command->info("  Client ID: {$client->client_id}");
            $this->command->info("  Secret   : {$plainSecret}");
            $this->command->info('  ----------------------------------------');
        }

        $this->command->info('');
        $this->command->warn('  ⚠  Salin credentials di atas ke file .env sub-aplikasi masing-masing!');
        $this->command->info('========================================');
        $this->command->info('');
    }
}
