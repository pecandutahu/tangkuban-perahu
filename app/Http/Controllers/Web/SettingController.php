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
        // Ambil semua setting dan index berdasarkan key
        $settings = Setting::all()->keyBy('key')->map(function ($item) {
            return $item->value;
        })->toArray();

        // Pastikan default version ada
        if (!isset($settings['pph21_calculator_version'])) {
            $settings['pph21_calculator_version'] = 'ter_2024';
        }

        return Inertia::render('Settings/Index', [
            'settings' => $settings
        ]);
    }

    /**
     * Simpan perubahan pengaturan massal.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.pph21_calculator_version' => 'required|string',
        ]);

        foreach ($validated['settings'] as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        return redirect()->back()->with('message', 'Pengaturan sistem berhasil diperbarui.');
    }
}
