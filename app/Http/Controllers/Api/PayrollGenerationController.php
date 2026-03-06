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
}
