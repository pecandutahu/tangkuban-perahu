<?php

namespace App\Modules\Payroll\Calculators\Contracts;

use App\Models\Employee;

interface Pph21StrategyInterface
{
    /**
     * Menghitung nilai pemotongan PPh 21 sebulan.
     * 
     * @param Employee $employee Data pegawai beserta status PTKP
     * @param float $bruto Pendapatan kotor bulan berjalan
     * @return float Nilai potongan pajak (Deduction)
     */
    public function calculate(Employee $employee, float $bruto): float;
}
