<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;

class EmployeeTemplateExport implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
    public function title(): string
    {
        return 'Template Import Karyawan';
    }

    public function headings(): array
    {
        return [
            'nik_internal',
            'name',
            'ktp_number',
            'npwp_number',
            'ptkp_status',
            'department_name',
            'position_name',
            'branch_name',
            'employment_type',
            'join_date',
            'payment_method',
            'bank_name',
            'bank_account',
            'is_active',
        ];
    }

    public function array(): array
    {
        // Contoh baris data agar HR tahu format yang benar
        return [
            [
                'EMP-001',
                'Budi Santoso',
                '3201234567890001',
                '12.345.678.9-001.000',
                'TK/0',
                'Operasional',
                'Supir',
                'Kantor Pusat',
                'tetap',
                '2024-01-15',
                'transfer',
                'BCA',
                '1234567890',
                '1',
            ],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        // Style header row (bold + background biru)
        return [
            1 => [
                'font'    => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1E40AF']],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 16, // nik_internal
            'B' => 30, // name
            'C' => 20, // ktp_number
            'D' => 22, // npwp_number
            'E' => 12, // ptkp_status
            'F' => 20, // department_name
            'G' => 20, // position_name
            'H' => 20, // branch_name
            'I' => 14, // employment_type
            'J' => 14, // join_date
            'K' => 14, // payment_method
            'L' => 16, // bank_name
            'M' => 18, // bank_account
            'N' => 10, // is_active
        ];
    }
}
