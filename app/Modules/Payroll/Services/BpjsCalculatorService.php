<?php

namespace App\Modules\Payroll\Services;

use App\Models\Employee;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;

class BpjsCalculatorService
{
    /**
     * Menghitung 6 komponen BPJS (karyawan + perusahaan) berdasarkan Gaji Pokok.
     *
     * Tarif dibaca dari tabel settings (key: bpjs_rates).
     * Iuran default (bila settings kosong):
     *   Karyawan: JHT 2%, JP 1%, Kes 1%
     *   Perusahaan: JHT 3.7%, JP 2%, JKK 0.24%, JKm 0.3%, Kes 4%
     *   Plafon Kes: Rp 12.000.000
     *
     * Prioritas basis Gaji Pokok:
     *   1. Submitted request (form input)
     *   2. employee_components tersimpan
     *   3. Default GP dari template
     *   Jika Gaji Pokok < UMR cabang, UMR digunakan sebagai floor.
     *
     * @param Employee $employee            Karyawan (dengan relasi branch)
     * @param array    $submittedComponents [{payroll_component_id, amount}]
     * @return array   Payload komponen siap disimpan ke employee_components
     */
    public static function calculateForAdmin(Employee $employee, array $submittedComponents = []): array
    {
        // --- 0. Baca tarif dan status modul dari settings DB ---
        $isBpjsEnabled = DB::table('settings')->where('key', 'bpjs_enabled')->value('value');
        if ($isBpjsEnabled !== null && !filter_var($isBpjsEnabled, FILTER_VALIDATE_BOOLEAN)) {
            return $submittedComponents; // BPJS Dinonaktifkan secara global, kembalikan saja override manual tanpa kalkulasi BPJS.
        }

        $rates = self::getRates();

        // --- 1. Cari komponen 'GP' global ---
        $gpComp = DB::table('payroll_components')->where('code', 'GP')->first();

        // --- 2. Ambil Gaji Pokok dari submitted ---
        $basicSalary = null;
        if ($gpComp) {
            foreach ($submittedComponents as $comp) {
                if ((int)$comp['payroll_component_id'] === (int)$gpComp->id) {
                    $basicSalary = (float)$comp['amount'];
                    break;
                }
            }
        }

        // --- 3. Fallback: employee_components tersimpan ---
        if ($basicSalary === null && $gpComp) {
            $saved = DB::table('employee_components')
                ->where('employee_id', $employee->id)
                ->where('payroll_component_id', $gpComp->id)
                ->first();
            if ($saved) {
                $basicSalary = (float)$saved->amount;
            }
        }

        // --- 4. Fallback: default GP dari template ---
        if ($basicSalary === null) {
            try {
                $template = PayrollTemplateResolver::resolve($employee);
                if ($template) {
                    foreach ($template->components as $tc) {
                        if ($tc->component && $tc->component->code === 'GP') {
                            $basicSalary = (float)$tc->component->default_amount;
                            break;
                        }
                    }
                }
            } catch (\Exception $e) {
                // Template tidak ditemukan
            }
        }

        $basicSalary = $basicSalary ?? 0;

        // --- 5. Floor UMR cabang ---
        $umr        = $employee->branch ? (float)($employee->branch->umr_amount ?? 0) : 0;
        $baseSalary = max($basicSalary, $umr);

        // --- 6. Build component map dari submitted ---
        $componentMap = [];
        foreach ($submittedComponents as $comp) {
            $componentMap[(int)$comp['payroll_component_id']] = $comp['amount'];
        }

        // --- 7. Hitung 6 komponen BPJS ---
        if ($baseSalary > 0) {
            $cap = (float)$rates['kes_salary_cap'];
            $cappedSalary = min($baseSalary, $cap);

            // Iuran Karyawan
            $empTkAmount  = $baseSalary   * (($rates['jht_employee'] + $rates['jp_employee']) / 100);
            $empKesAmount = $cappedSalary  * ($rates['kes_employee'] / 100);

            // Iuran Perusahaan
            $coTkRate     = $rates['jht_company'] + $rates['jp_company']
                          + $rates['jkk_company'] + $rates['jkm_company'];  // configurable JKK + JKm
            $coTkAmount   = $baseSalary   * ($coTkRate / 100);
            $coKesAmount  = $cappedSalary  * ($rates['kes_company'] / 100);

            $codes = [
                'BPJS_TK'         => $empTkAmount,
                'BPJS_KES'        => $empKesAmount,
                'TJ_BPJS_TK_CO'   => $coTkAmount,
                'TJ_BPJS_KES_CO'  => $coKesAmount,
                'POT_BPJS_TK_CO'  => $coTkAmount,    // offset = tunjangan perusahaan
                'POT_BPJS_KES_CO' => $coKesAmount,   // offset = tunjangan perusahaan
            ];

            foreach ($codes as $code => $amount) {
                $row = DB::table('payroll_components')->where('code', $code)->first();
                if ($row) {
                    $componentMap[(int)$row->id] = round($amount, 0);
                }
            }
        }

        // --- 8. Kembalikan payload ---
        $finalComponents = [];
        foreach ($componentMap as $compId => $amount) {
            $finalComponents[] = [
                'payroll_component_id' => $compId,
                'amount'               => $amount,
            ];
        }

        return $finalComponents;
    }

    /**
     * Baca tarif BPJS dari settings DB.
     * Fallback ke tarif default pemerintah jika belum dikonfigurasi.
     */
    public static function getRates(): array
    {
        $defaults = [
            'jht_employee'   => 2.0,
            'jp_employee'    => 1.0,
            'kes_employee'   => 1.0,
            'jht_company'    => 3.7,
            'jp_company'     => 2.0,
            'jkk_company'    => 0.24,
            'jkm_company'    => 0.3,
            'kes_company'    => 4.0,
            'kes_salary_cap' => 12000000,
        ];

        $setting = Setting::where('key', 'bpjs_rates')->first();
        if (!$setting) {
            return $defaults;
        }

        $stored = json_decode($setting->value, true);
        if (!is_array($stored)) {
            return $defaults;
        }

        return array_merge($defaults, $stored);
    }
}
