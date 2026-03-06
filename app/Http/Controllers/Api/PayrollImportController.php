<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Payroll\Services\VariableImportService;
use Illuminate\Http\Request;

class PayrollImportController extends Controller
{
    public function import(Request $request, int $periodId, VariableImportService $service)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt'
        ]);

        try {
            $result = $service->import($periodId, $request->file('file'));

            if (!$result['success']) {
                return response()->json([
                    'message' => 'Import gagal karena ada data yang tidak valid.',
                    'errors' => $result['errors']
                ], 422);
            }

            return response()->json([
                'message' => $result['message'],
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function downloadTemplate(int $periodId)
    {
        $period = \App\Models\PayrollPeriod::with('items.employee')->findOrFail($periodId);
        
        $fileName = "template_import_payroll_{$period->code}.csv";
        
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename={$fileName}",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['nik_internal', 'nama_karyawan', 'component_code', 'amount'];

        $callback = function() use ($period, $columns) {
            $file = fopen('php://output', 'w');
            
            // Tulis Header Kolom
            fputcsv($file, $columns);
            
            // Loop data karyawan sebagai baris bantuan (dummy row)
            foreach ($period->items as $item) {
                if ($item->employee && $item->employee->nik_internal) {
                    fputcsv($file, [$item->employee->nik_internal, $item->employee->name, 'KODE_KOMPONEN (MISAL: BNS)', '0']);
                }
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
