<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;

class BpjsSettingSeeder extends Seeder
{
    /**
     * Seed konfigurasi tarif BPJS ke dalam tabel settings.
     *
     * Tarif yang dapat dikonfigurasi:
     *   Karyawan:
     *     - jht_employee  : 2%   (Jaminan Hari Tua karyawan)
     *     - jp_employee   : 1%   (Jaminan Pensiun karyawan)
     *     - kes_employee  : 1%   (Kesehatan karyawan, plafon 12jt)
     *   Perusahaan:
     *     - jht_company   : 3.7% (Jaminan Hari Tua perusahaan)
     *     - jp_company    : 2%   (Jaminan Pensiun perusahaan)
     *     - jkk_company   : 0.24% (Jaminan Kecelakaan Kerja - sesuaikan risiko jabatan)
     *     - jkm_company   : 0.3% (Jaminan Kematian perusahaan)
     *     - kes_company   : 4%   (Kesehatan perusahaan, plafon 12jt)
     *   Lainnya:
     *     - kes_salary_cap: 12.000.000 (Plafon gaji basis BPJS Kesehatan)
     */
    public function run(): void
    {
        $defaults = [
            // Tarif BPJS disimpan sebagai JSON object
            'bpjs_rates' => json_encode([
                // --- Iuran karyawan ---
                'jht_employee'   => 2.0,    // %
                'jp_employee'    => 1.0,    // %
                'kes_employee'   => 1.0,    // %
                // --- Iuran perusahaan ---
                'jht_company'    => 3.7,    // %
                'jp_company'     => 2.0,    // %
                'jkk_company'    => 0.24,   // % - Risiko rendah (ubah sesuai tingkat risiko)
                'jkm_company'    => 0.3,    // %
                'kes_company'    => 4.0,    // %
                // --- Batasan ---
                'kes_salary_cap' => 12000000, // Rp (plafon gaji basis BPJS Kesehatan)
            ]),
        ];

        foreach ($defaults as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }
    }
}
