<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\PayrollTemplate;
use App\Models\PayrollComponent;

class PayrollTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $getComp = function($code) {
            return PayrollComponent::where('code', $code)->first()->id ?? null;
        };

        $templates = [
            // 'GP'      = Gaji Pokok universal (basis BPJS)
            // 'GP_*'    = Tunjangan Jabatan spesifik
            // 'BPJS_*'  = Iuran BPJS karyawan
            // 'TJ_BPJS_*_CO'  = Tunjangan BPJS perusahaan (earning)
            // 'POT_BPJS_*_CO' = Offset BPJS perusahaan (deduction, dibayar ke BPJS)
            'Driver'     => ['GP', 'GP_DRV', 'UM', 'UJ', 'KSB', 'BPJS_TK', 'BPJS_KES', 'TJ_BPJS_TK_CO', 'TJ_BPJS_KES_CO', 'POT_BPJS_TK_CO', 'POT_BPJS_KES_CO', 'KLAIM'],
            'Helper'     => ['GP', 'GP_HLP', 'UM', 'UJ', 'KSB', 'BPJS_TK', 'BPJS_KES', 'TJ_BPJS_TK_CO', 'TJ_BPJS_KES_CO', 'POT_BPJS_TK_CO', 'POT_BPJS_KES_CO', 'KLAIM'],
            'Staff Admin' => ['GP', 'GP_ADM', 'UM', 'LMBR', 'BPJS_TK', 'BPJS_KES', 'TJ_BPJS_TK_CO', 'TJ_BPJS_KES_CO', 'POT_BPJS_TK_CO', 'POT_BPJS_KES_CO'],
            'Mekanik'    => ['GP', 'GP_MCH', 'UM', 'LMBR', 'KSB', 'BPJS_TK', 'BPJS_KES', 'TJ_BPJS_TK_CO', 'TJ_BPJS_KES_CO', 'POT_BPJS_TK_CO', 'POT_BPJS_KES_CO'],
            'Supervisor' => ['GP', 'GP_SPV', 'UM', 'BPJS_TK', 'BPJS_KES', 'TJ_BPJS_TK_CO', 'TJ_BPJS_KES_CO', 'POT_BPJS_TK_CO', 'POT_BPJS_KES_CO'],
        ];


        foreach ($templates as $templateName => $componentCodes) {
            $type = ($templateName === 'Helper') ? 'contract' : 'permanent';

            // Coba cari Master Position yang mirip dengan nama Template
            $position = \App\Models\Position::where('name', 'ilike', '%' . $templateName . '%')->first();

            $template = PayrollTemplate::create([
                'name' => "Template Gaji $templateName",
                'employment_type' => $type,
                'position_id' => $position ? $position->id : null,
            ]);

            foreach ($componentCodes as $code) {
                $comp = PayrollComponent::where('code', $code)->first();
                if ($comp) {
                    $template->components()->create([
                        'payroll_component_id' => $comp->id,
                        'default_amount' => $comp->default_amount,
                    ]);
                }
            }
        }
    }
}
