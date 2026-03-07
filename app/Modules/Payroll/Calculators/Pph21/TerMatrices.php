<?php

namespace App\Modules\Payroll\Calculators\Pph21;

/**
 * Representasi Array dari Tabel Tarif Efektif Rata-Rata (TER) PPh 21 PP 58/2023
 * Catatan: Data ini telah disederhanakan/diringkas untuk keperluan demonstrasi fungsionalitas.
 * Di lingkungan produksi sesungguhnya, array ini berisi ratusan lapis sesuai lampiran PMK 168/2023.
 */
class TerMatrices
{
    /**
     * Kategori A: (TK/0, TK/1, K/0)
     */
    public static $categoryA = [
        ['min' => 0,          'max' => 5400000,    'rate' => 0.00],   // 0 - 5.4 Jt (0%)
        ['min' => 5400000.01, 'max' => 5650000,    'rate' => 0.0025], // 5.4 Jt - 5.65 Jt (0.25%)
        ['min' => 5650000.01, 'max' => 5950000,    'rate' => 0.005],  // 5.65 Jt - 5.95 Jt (0.5%)
        ['min' => 5950000.01, 'max' => 6300000,    'rate' => 0.0075], // 5.95 Jt - 6.30 Jt (0.75%)
        ['min' => 6300000.01, 'max' => 6750000,    'rate' => 0.01],   // 6.30 Jt - 6.75 Jt (1%)
        ['min' => 6750000.01, 'max' => 7500000,    'rate' => 0.0125], // 6.75 Jt - 7.50 Jt (1.25%) ---> 7.43 Jt MASUK SINI
        ['min' => 7500000.01, 'max' => 8550000,    'rate' => 0.015],  // 7.50 Jt - 8.55 Jt (1.5%)
        ['min' => 8550000.01, 'max' => 9650000,    'rate' => 0.0175], // 8.55 Jt - 9.65 Jt (1.75%)
        ['min' => 9650000.01, 'max' => 10050000,   'rate' => 0.02],   // 9.65 Jt - 10.05 Jt (2%)
        ['min' => 10050000.01, 'max' => 10350000,  'rate' => 0.0225], // 10.05 Jt - 10.35 Jt (2.25%)
        ['min' => 10350000.01, 'max' => 10700000,  'rate' => 0.025],  // 10.35 Jt - 10.70 Jt (2.5%)
        ['min' => 10700000.01, 'max' => 11050000,  'rate' => 0.03],   // 10.70 Jt - 11.05 Jt (3%)
        ['min' => 11050000.01, 'max' => 9999999999,'rate' => 0.05],   // Fallback dummy > 11 Jt (5%)
    ];

    /**
     * Kategori B: (TK/2, TK/3, K/1, K/2)
     */
    public static $categoryB = [
        ['min' => 0,          'max' => 6200000,    'rate' => 0.00],   // 0 - 6.2 Juta
        ['min' => 6200000.01, 'max' => 6500000,    'rate' => 0.0025], // 6.2 Jt - 6.5 Jt (0.25%)
        ['min' => 6500000.01, 'max' => 6850000,    'rate' => 0.005],  // 6.5 Jt - 6.85 Jt (0.5%)
        ['min' => 6850000.01, 'max' => 7300000,    'rate' => 0.0075], // 6.85 Jt - 7.3 Jt (0.75%)
        ['min' => 7300000.01, 'max' => 9200000,    'rate' => 0.015],  // 7.3 Jt - 9.2 Jt (1.5%)
        ['min' => 9200000.01, 'max' => 9999999999, 'rate' => 0.04],   // Fallback dummy > 9.2 Jt (4%)
    ];

    /**
     * Kategori C: (K/3)
     */
    public static $categoryC = [
        ['min' => 0,          'max' => 6600000,    'rate' => 0.00],   // 0 - 6.6 Juta
        ['min' => 6600000.01, 'max' => 6950000,    'rate' => 0.0025], // 6.6 Jt - 6.95 Jt (0.25%)
        ['min' => 6950000.01, 'max' => 7350000,    'rate' => 0.005],  // 6.95 Jt - 7.35 Jt (0.5%)
        ['min' => 7350000.01, 'max' => 7800000,    'rate' => 0.0075], // 7.35 Jt - 7.80 Jt (0.75%)
        ['min' => 7800000.01, 'max' => 9999999999, 'rate' => 0.015],  // Fallback dummy > 7.8 Jt (1.5%)
    ];
}
