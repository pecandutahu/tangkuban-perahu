<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;
use App\Models\PayrollComponent;

class Pph21SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Cari ID komponen BPJS_TK pengurang pajak
        $bpjsComponent = PayrollComponent::where('code', 'BPJS_TK')->first();

        $excludedIds = [];
        if ($bpjsComponent) {
            $excludedIds[] = $bpjsComponent->id;
        }

        // Susun konfigurasi
        $defaults = [
            'pph21_calculator_version' => 'ter_2024',
            'pph21_excluded_components' => json_encode($excludedIds)
        ];

        foreach ($defaults as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }
    }
}
