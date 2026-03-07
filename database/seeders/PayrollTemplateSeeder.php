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
            'Driver' => ['GP_DRV', 'UM', 'UJ', 'KSB', 'BPJ', 'KLAIM'],
            'Helper' => ['GP_HLP', 'UM', 'UJ', 'KSB', 'BPJ', 'KLAIM'],
            'Staff Admin' => ['GP_ADM', 'UM', 'LMBR', 'BPJ'],
            'Mekanik' => ['GP_MCH', 'UM', 'LMBR', 'KSB', 'BPJ'],
            'Supervisor' => ['GP_SPV', 'UM', 'BPJ'],
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
