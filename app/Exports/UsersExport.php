<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UsersExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    public function __construct(private array $filters = []) {}

    public function query()
    {
        $adminRoles = ['mahasiswa', 'admin', 'dosen'];

        $query = User::with('roles')
            ->whereHas('roles', fn($q) => $q->whereIn('name', $adminRoles));

        if (!empty($this->filters['role'])) {
            $query->whereHas('roles', fn($q) => $q->where('name', $this->filters['role']));
        }

        if (!empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(
                fn($q) => $q
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('npm', 'like', "%{$search}%")
                    ->orWhere('nip', 'like', "%{$search}%")
                    ->orWhere('nidn', 'like', "%{$search}%")
            );
        }

        return $query->latest();
    }

    public function headings(): array
    {
        return [
            'No',
            'Nama',
            'Email',
            'Role',
            'NPM',
            'NIP',
            'NIDN',
            'No. HP',
            'Lokasi',
            'Status',
            'Tanggal Daftar',
        ];
    }

    public function map($user): array
    {
        static $no = 0;
        $no++;

        return [
            $no,
            $user->name,
            $user->email,
            $user->roles->first()?->name ?? '-',
            $user->npm ?? '-',
            $user->nip ?? '-',
            $user->nidn ?? '-',
            $user->phone ?? '-',
            $user->location ?? '-',
            $user->is_active ? 'Aktif' : 'Tidak Aktif',
            $user->created_at?->format('d-m-Y'),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill'      => ['fillType' => 'solid', 'startColor' => ['rgb' => '059669']],
                'alignment' => ['horizontal' => 'center'],
            ],
        ];
    }
}
