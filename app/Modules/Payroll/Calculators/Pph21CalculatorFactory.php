<?php

namespace App\Modules\Payroll\Calculators;

use App\Models\Setting;
use App\Modules\Payroll\Calculators\Contracts\Pph21StrategyInterface;
use App\Modules\Payroll\Calculators\Pph21\Pph21Ter2024Strategy;

class Pph21CalculatorFactory
{
    /**
     * Membuat instansi kalkulator PPh 21 berdasarkan pengaturan global.
     * Mengembalikan default TER 2024 jika belum ada setting.
     */
    public static function make(): ?Pph21StrategyInterface
    {
        // Mencari nilai setting. Disimpan dalam memory session jika sering dipanggil (opsional),
        // atau kita load langsung untuk kesederhanaan karena di-cache oleh Laravel Database jika kecil.
        $version = null;
        
        try {
            $setting = Setting::where('key', 'pph21_calculator_version')->first();
            if ($setting) {
                $version = $setting->value;
            }
        } catch (\Exception $e) {
            // Abaikan jika tabel setting belum di-migrate, fallback otomatis jalan.
        }

        switch ($version) {
            case 'none':
                return null;
                
            case 'ter_2024':
                return new Pph21Ter2024Strategy();
            
            // Nantinya developer bisa menambahkan versi baru di sini tanpa ngerusak sistem utama:
            // case 'regulasi_2026':
            //     return new Pph21Regulasi2026Strategy();
                
            default:
                // Fallback default
                return new Pph21Ter2024Strategy();
        }
    }
}
