<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UsersExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    public function __construct(private array $filters = []) {}

    public function collection()
    {
        $query = DB::connection('pgsql')
            ->table('tb_users as u')
            ->leftJoin('tb_data_pribadi as dp', 'dp.user_id', '=', 'u.user_id')
            ->leftJoin('trx_user_jabatan_unit as tuju', 'tuju.user_id', '=', 'u.user_id')
            ->leftJoin('m_unit as mu', 'mu.id', '=', 'tuju.unit_id')
            ->leftJoin('m_jabatan as mj', 'mj.id', '=', 'tuju.jabatan_id')
            ->whereNull('u.deleted_at')
            ->select(
                'u.user_id',
                'u.email',
                'u.role',
                'u.npm',
                'u.nidn',
                'u.isverified',
                'u.created_at',
                'dp.nama_lengkap',
                'mj.nama_jabatan',
                'mu.nama_unit',
                'mu.code as unit_code',
            );

        if (!empty($this->filters['role'])) {
            $query->where('u.role', $this->filters['role']);
        }

        if (!empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('u.email', 'ilike', "%{$search}%")
                  ->orWhere('u.nidn', 'ilike', "%{$search}%")
                  ->orWhere('u.npm', 'ilike', "%{$search}%")
                  ->orWhere('dp.nama_lengkap', 'ilike', "%{$search}%");
            });
        }

        return $query->orderByDesc('u.created_at')->get();
    }

    public function headings(): array
    {
        return [
            'No',
            'Nama Lengkap',
            'Email',
            'Role',
            'NPM',
            'NIDN',
            'Jabatan',
            'Unit',
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
            $user->nama_lengkap ?? '-',
            $user->email,
            $user->role,
            trim($user->npm  ?? '') ?: '-',
            trim($user->nidn ?? '') ?: '-',
            $user->nama_jabatan ?? '-',
            $user->nama_unit    ?? '-',
            $user->isverified ? 'Aktif' : 'Belum Verifikasi',
            $user->created_at ? date('d-m-Y H:i', strtotime($user->created_at)) : '-',
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