<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Payroll\Services\GeneratePayrollService;
use Illuminate\Http\Request;

class PayrollGenerationController extends Controller
{
    public function generate(Request $request, GeneratePayrollService $service)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'pay_date' => 'required|date',
            'period_type' => 'required|string',
        ]);

        try {
            $period = $service->generate($validated);

            return response()->json([
                'success' => true,
                'message' => 'Draft Payroll berhasil digenerate.',
                'data' => $period
            ], 201);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function regenerateItem(Request $request, $periodId, $itemId, GeneratePayrollService $service)
    {
        try {
            $item = $service->regenerateItem($periodId, $itemId);

            return response()->json([
                'success' => true,
                'message' => 'Data komponen master Karyawan berhasil diperbarui (Regenerate).',
                'data' => $item
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function regeneratePeriod(Request $request, $id, GeneratePayrollService $service)
    {
        try {
            $period = $service->regeneratePeriod($id);

            return response()->json([
                'success' => true,
                'message' => 'Seluruh data karyawan pada periode ini berhasil disinkronisasi dengan Master Data terbaru.',
                'data' => $period
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
