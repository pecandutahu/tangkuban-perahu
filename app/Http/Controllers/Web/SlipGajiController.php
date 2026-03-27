<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\PayrollItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class SlipGajiController extends Controller
{
    public function download($id)
    {
        $item = PayrollItem::with([
            'employee.position',
            'employee.department',
            'employee.branch',
            'period',
            'components'
        ])->findOrFail($id);

        $user = auth()->user();
        if (!$user->can('view-payroll') && $user->employee_id !== $item->employee_id) {
            abort(403, 'Anda tidak diizinkan membuka slip gaji milik karyawan lain.');
        }

        $earnings = $item->components->where('component_type', 'earning');
        $deductions = $item->components->where('component_type', 'deduction');

        $pdf = Pdf::loadView('pdf.slip_gaji', [
            'item' => $item,
            'earnings' => $earnings,
            'deductions' => $deductions,
            'generated_at' => now()->format('d/m/Y H:i:s')
        ]);

        $filename = "slip_gaji_{$item->period->code}_{$item->employee->nik_internal}.pdf";
        
        return $pdf->stream($filename);
    }
}
