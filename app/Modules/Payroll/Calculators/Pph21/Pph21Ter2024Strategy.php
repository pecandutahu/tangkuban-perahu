<?php

namespace App\Modules\Payroll\Calculators\Pph21;

use App\Models\Employee;
use App\Modules\Payroll\Calculators\Contracts\Pph21StrategyInterface;

class Pph21Ter2024Strategy implements Pph21StrategyInterface
{
    /**
     * Hitung PPh 21 menggunakan rumusan TER (Tarif Efektif Rata-Rata) 2024.
     */
    public function calculate(Employee $employee, float $bruto): float
    {
        if ($bruto <= 0) {
            return 0;
        }

        $ptkpStatus = strtoupper(trim($employee->ptkp_status ?: 'TK/0'));
        $ptkpStatus = str_replace(' ', '', $ptkpStatus);

        // 1. Tentukan Kategori TER (A, B, atau C) berdasarkan Status PTKP
        $terCategoryArray = [];
        
        switch ($ptkpStatus) {
            case 'TK/0': case 'TK/1': case 'K/0':
                $terCategoryArray = TerMatrices::$categoryA;
                break;
            case 'TK/2': case 'TK/3': case 'K/1': case 'K/2':
                $terCategoryArray = TerMatrices::$categoryB;
                break;
            case 'K/3':
                $terCategoryArray = TerMatrices::$categoryC;
                break;
            default:
                // Fallback aman ke Kategori A (Pajak Lebih Tinggi) jika status asing
                $terCategoryArray = TerMatrices::$categoryA;
                break;
        }

        // 2. Cari Persentase Tarif Efektif yang sesuai dengan Range Bruto Sebulan
        $terPercentage = 0;
        foreach ($terCategoryArray as $tier) {
            if ($bruto >= $tier['min'] && $bruto <= $tier['max']) {
                $terPercentage = $tier['rate'];
                break;
            }
        }

        // 3. Hitung Pajak PPh 21 Bulanan (Bruto x Tarif TER) -- SANGAT SIMPLE!
        $pajakSebulan = $bruto * $terPercentage;

        // 4. Penalti Non-NPWP (+20%)
        // *Catatan Regulasi: Penalti 20% Non-NPWP TERKADANG disyaratkan hanya untuk skema non-final.
        // Di aplikasi ini kita asumsikan aturan lama tetap berlaku (pengalian di akhir).
        if (!$employee->npwp_number && $pajakSebulan > 0) {
            $pajakSebulan = $pajakSebulan * 1.20;
        }

        return round($pajakSebulan);
    }
}
