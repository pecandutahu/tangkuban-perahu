<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SettingController extends Controller
{
    /**
     * Tampilkan halaman Pengaturan.
     */
    public function index()
    {
        $settings = Setting::all()->keyBy('key')->map(fn($i) => $i->value)->toArray();

        // --- PPh 21 defaults ---
        if (!isset($settings['pph21_calculator_version'])) {
            $settings['pph21_calculator_version'] = 'ter_2024';
        }

        if (isset($settings['pph21_excluded_components'])) {
            $settings['pph21_excluded_components'] = json_decode($settings['pph21_excluded_components'], true);
        } else {
            $settings['pph21_excluded_components'] = [];
        }

        // --- BPJS rates defaults ---
        $bpjsDefaults = [
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

        if (isset($settings['bpjs_rates'])) {
            $stored = json_decode($settings['bpjs_rates'], true) ?? [];
            $settings['bpjs_rates'] = array_merge($bpjsDefaults, $stored);
        } else {
            $settings['bpjs_rates'] = $bpjsDefaults;
        }

        // Kirimkan master "Deduction" Components untuk dicentang di UI
        $components = \App\Models\PayrollComponent::where('component_type', 'deduction')
                        ->orderBy('name')
                        ->get(['id', 'code', 'name']);

        return Inertia::render('Settings/Index', [
            'settings'   => $settings,
            'components' => $components,
        ]);
    }

    /**
     * Simpan perubahan pengaturan massal.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'settings'                                  => 'required|array',
            'settings.pph21_calculator_version'         => 'required|string',
            'settings.pph21_excluded_components'        => 'nullable|array',
            'settings.pph21_excluded_components.*'      => 'exists:payroll_components,id',
            // BPJS rates validation
            'settings.bpjs_rates'                       => 'nullable|array',
            'settings.bpjs_rates.jht_employee'          => 'nullable|numeric|min:0|max:100',
            'settings.bpjs_rates.jp_employee'           => 'nullable|numeric|min:0|max:100',
            'settings.bpjs_rates.kes_employee'          => 'nullable|numeric|min:0|max:100',
            'settings.bpjs_rates.jht_company'           => 'nullable|numeric|min:0|max:100',
            'settings.bpjs_rates.jp_company'            => 'nullable|numeric|min:0|max:100',
            'settings.bpjs_rates.jkk_company'           => 'nullable|numeric|min:0|max:100',
            'settings.bpjs_rates.jkm_company'           => 'nullable|numeric|min:0|max:100',
            'settings.bpjs_rates.kes_company'           => 'nullable|numeric|min:0|max:100',
            'settings.bpjs_rates.kes_salary_cap'        => 'nullable|numeric|min:0',
        ]);

        foreach ($validated['settings'] as $key => $value) {
            if (is_array($value)) {
                $value = json_encode($value);
            }
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        return redirect()->back()->with('message', 'Pengaturan sistem berhasil diperbarui.');
    }
}
