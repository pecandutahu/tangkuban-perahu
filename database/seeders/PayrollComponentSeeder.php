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
            ['code' => 'GP_SPV', 'name' => 'Gaji Pokok SPV', 'component_type' => PayrollComponentType::EARNING->value, 'is_variable' => false, 'default_amount' => 6000000, 'is_active' => true],
            ['code' => 'GP_ADM', 'name' => 'Gaji Pokok Admin', 'component_type' => PayrollComponentType::EARNING->value, 'is_variable' => false, 'default_amount' => 4500000, 'is_active' => true],
            ['code' => 'GP_DRV', 'name' => 'Gaji Pokok Driver', 'component_type' => PayrollComponentType::EARNING->value, 'is_variable' => false, 'default_amount' => 3000000, 'is_active' => true],
            ['code' => 'GP_HLP', 'name' => 'Gaji Pokok Helper', 'component_type' => PayrollComponentType::EARNING->value, 'is_variable' => false, 'default_amount' => 2000000, 'is_active' => true],
            ['code' => 'GP_MCH', 'name' => 'Gaji Pokok Mekanik', 'component_type' => PayrollComponentType::EARNING->value, 'is_variable' => false, 'default_amount' => 4000000, 'is_active' => true],
            ['code' => 'UM', 'name' => 'Uang Makan Tetap', 'component_type' => PayrollComponentType::EARNING->value, 'is_variable' => false, 'default_amount' => 500000, 'is_active' => true],
            ['code' => 'TJ_FUNC', 'name' => 'Tunjangan Fungsional', 'component_type' => PayrollComponentType::EARNING->value, 'is_variable' => false, 'default_amount' => 0, 'is_active' => true],
            
            // Variabel Earnings 
            ['code' => 'UJ', 'name' => 'Uang Jalan / Insentif Trip', 'component_type' => PayrollComponentType::EARNING->value, 'is_variable' => true, 'default_amount' => 0, 'is_active' => true],
            ['code' => 'LMBR', 'name' => 'Uang Lembur', 'component_type' => PayrollComponentType::EARNING->value, 'is_variable' => true, 'default_amount' => 0, 'is_active' => true],

            // Deductions (Potongan)
            ['code' => 'KSB', 'name' => 'Potongan Kasbon', 'component_type' => PayrollComponentType::DEDUCTION->value, 'is_variable' => true, 'default_amount' => 0, 'is_active' => true],
            ['code' => 'BPJ', 'name' => 'Potongan BPJS', 'component_type' => PayrollComponentType::DEDUCTION->value, 'is_variable' => false, 'default_amount' => 150000, 'is_active' => true],
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
