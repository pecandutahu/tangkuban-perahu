<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Enum\PayrollComponentType;
use App\Models\PayrollComponent;

class PayrollComponentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $components = [
            // Earnings (Pendapatan)
            ['code' => 'GP_SPV', 'name' => 'Tunjangan Jabatan SPV', 'component_type' => PayrollComponentType::EARNING->value, 'is_variable' => false, 'default_amount' => 6000000, 'is_active' => true],
            ['code' => 'GP_ADM', 'name' => 'Tunjangan Jabatan Admin', 'component_type' => PayrollComponentType::EARNING->value, 'is_variable' => false, 'default_amount' => 4500000, 'is_active' => true],
            ['code' => 'GP_DRV', 'name' => 'Tunjangan Jabatan Driver', 'component_type' => PayrollComponentType::EARNING->value, 'is_variable' => false, 'default_amount' => 3000000, 'is_active' => true],
            ['code' => 'GP_HLP', 'name' => 'Tunjangan Jabatan Helper', 'component_type' => PayrollComponentType::EARNING->value, 'is_variable' => false, 'default_amount' => 2000000, 'is_active' => true],
            ['code' => 'GP_MCH', 'name' => 'Tunjangan Jabatan Mekanik', 'component_type' => PayrollComponentType::EARNING->value, 'is_variable' => false, 'default_amount' => 4000000, 'is_active' => true],
            // Universal Basic Salary component
            ['code' => 'GP', 'name' => 'Gaji Pokok', 'component_type' => PayrollComponentType::EARNING->value, 'is_variable' => false, 'default_amount' => 0, 'is_active' => true],
            ['code' => 'UM', 'name' => 'Uang Makan Tetap', 'component_type' => PayrollComponentType::EARNING->value, 'is_variable' => false, 'default_amount' => 500000, 'is_active' => true],
            ['code' => 'TJ_FUNC', 'name' => 'Tunjangan Fungsional', 'component_type' => PayrollComponentType::EARNING->value, 'is_variable' => false, 'default_amount' => 0, 'is_active' => true],
            
            // Variabel Earnings 
            ['code' => 'UJ', 'name' => 'Uang Jalan / Insentif Trip', 'component_type' => PayrollComponentType::EARNING->value, 'is_variable' => true, 'default_amount' => 0, 'is_active' => true],
            ['code' => 'LMBR', 'name' => 'Uang Lembur', 'component_type' => PayrollComponentType::EARNING->value, 'is_variable' => true, 'default_amount' => 0, 'is_active' => true],

            // Deductions (Potongan)
            ['code' => 'KSB',  'name' => 'Potongan Kasbon', 'component_type' => PayrollComponentType::DEDUCTION->value, 'is_variable' => true,  'default_amount' => 0, 'is_active' => true],

            // ==== BPJS KARYAWAN (iuran ditanggung karyawan) ====
            ['code' => 'BPJS_TK',  'name' => 'Potongan BPJS Ketenagakerjaan Karyawan (2% JHT + 1% JP)', 'component_type' => PayrollComponentType::DEDUCTION->value, 'is_variable' => false, 'default_amount' => 0, 'is_active' => true],
            ['code' => 'BPJS_KES', 'name' => 'Potongan BPJS Kesehatan Karyawan (1%)',                   'component_type' => PayrollComponentType::DEDUCTION->value, 'is_variable' => false, 'default_amount' => 0, 'is_active' => true],

            // ==== BPJS PERUSAHAAN (iuran ditanggung perusahaan) ====
            // Tunjangan (Earning) = perusahaan menanggung iuran sebagai fasilitas
            ['code' => 'TJ_BPJS_TK_CO',  'name' => 'Tunjangan BPJS Ketenagakerjaan Perusahaan (3.7% JHT + 2% JP + 0.24% JKK + 0.3% JKm)', 'component_type' => PayrollComponentType::EARNING->value, 'is_variable' => false, 'default_amount' => 0, 'is_active' => true],
            ['code' => 'TJ_BPJS_KES_CO', 'name' => 'Tunjangan BPJS Kesehatan Perusahaan (4%)',                       'component_type' => PayrollComponentType::EARNING->value, 'is_variable' => false, 'default_amount' => 0, 'is_active' => true],
            // Potongan (Deduction) = offset nominal tunjangan di atas, dibayarkan perusahaan ke BPJS
            ['code' => 'POT_BPJS_TK_CO',  'name' => 'Potongan BPJS Ketenagakerjaan Perusahaan (dibayar ke BPJS)', 'component_type' => PayrollComponentType::DEDUCTION->value, 'is_variable' => false, 'default_amount' => 0, 'is_active' => true],
            ['code' => 'POT_BPJS_KES_CO', 'name' => 'Potongan BPJS Kesehatan Perusahaan (dibayar ke BPJS)',       'component_type' => PayrollComponentType::DEDUCTION->value, 'is_variable' => false, 'default_amount' => 0, 'is_active' => true],

            ['code' => 'KLAIM', 'name' => 'Potongan Klaim Barang/Kerusakan', 'component_type' => PayrollComponentType::DEDUCTION->value, 'is_variable' => true, 'default_amount' => 0, 'is_active' => true],
        ];


        foreach ($components as $component) {
            PayrollComponent::updateOrCreate(
                ['code' => $component['code']],
                $component
            );
        }
    }
}
