<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\PtkpStatus;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PtkpStatusController extends Controller
{
    /**
     * Tampilkan grid Master Data PTKP.
     */
    public function index()
    {
        $statuses = PtkpStatus::orderBy('amount')->get();
        return Inertia::render('PtkpStatus/Index', [
            'statuses' => $statuses
        ]);
    }

    /**
     * Simpan PTKP baru.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:ptkp_statuses,code',
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:255',
        ]);

        // Standarisasi UPPERCASE
        $validated['code'] = strtoupper(trim($validated['code']));

        PtkpStatus::create($validated);

        return redirect()->back()->with('message', 'Status PTKP berhasil ditambahkan.');
    }

    /**
     * Perbarui data PTKP.
     */
    public function update(Request $request, PtkpStatus $ptkp_status)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:ptkp_statuses,code,' . $ptkp_status->id,
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:255',
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));
        $ptkp_status->update($validated);

        return redirect()->back()->with('message', 'Data Status PTKP berhasil diperbarui.');
    }

    /**
     * Hapus PTKP.
     */
    public function destroy(PtkpStatus $ptkp_status)
    {
        // Tolak jika 'TK/0' karena ini default base calculation / safety net
        if (strtoupper($ptkp_status->code) === 'TK/0') {
            return redirect()->back()->withErrors(['message' => 'Status dasar TK/0 tidak dapat dihapus!']);
        }

        $ptkp_status->delete();

        return redirect()->back()->with('message', 'Status PTKP berhasil dihapus.');
    }
}
