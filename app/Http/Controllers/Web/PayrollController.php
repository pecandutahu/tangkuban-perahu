<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\PayrollPeriod;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PayrollController extends Controller
{
    public function index()
    {
        $periods = PayrollPeriod::withCount('items')
            ->orderBy('start_date', 'desc')
            ->paginate(10);
            
        return Inertia::render('Payroll/Index', [
            'periods' => $periods
        ]);
    }

    public function show(Request $request, $id)
    {
        // 1. Ambil detail periodes beserta relasi AuditLogs (tapi tanpa meload semua items)
        $period = PayrollPeriod::with([
            'auditLogs.user:id,name'
        ])->findOrFail($id);

        $period->auditLogs = $period->auditLogs->sortByDesc('created_at')->values();

        // 2. Siapkan query Items beserta agregasi nilai khusus IMPORT
        $itemsQuery = \App\Models\PayrollItem::with([
            'employee' => function($q) {
                $q->select('id', 'name', 'nik_internal');
            },
            'components'
        ])
        ->withSum(['components as import_earning_total' => function($query) {
            $query->where('source', 'IMPORT')->where('component_type', 'earning');
        }], 'amount')
        ->withSum(['components as import_deduction_total' => function($query) {
            $query->where('source', 'IMPORT')->where('component_type', 'deduction');
        }], 'amount')
        ->where('payroll_period_id', $period->id);

        // 3. Tambahkan pencarian (Search) jika ada
        $search = $request->input('search');
        if (!empty($search)) {
            $itemsQuery->whereHas('employee', function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('nik_internal', 'ilike', "%{$search}%");
            });
        }

        // 4. Tambahkan Filter Sorting khusus (jika ada)
        $sort = $request->input('sort');
        if ($sort === 'tinggi_earning') {
            $itemsQuery->orderByRaw('import_earning_total DESC NULLS LAST');
        } elseif ($sort === 'tinggi_deduction') {
            $itemsQuery->orderByRaw('import_deduction_total DESC NULLS LAST');
        } else {
            // Default sort: berdasar NIK Karyawan
            $itemsQuery->join('employees', 'payroll_items.employee_id', '=', 'employees.id')
                       ->orderBy('employees.nik_internal', 'asc')
                       ->select('payroll_items.*');
        }

        // 5. Lakukan paginate per 15 baris data (dan lempar queryString agar paginate tidak hilang)
        $items = $itemsQuery->paginate(15)->withQueryString();

        return Inertia::render('Payroll/Show', [
            'period' => $period,
            'items' => $items,
            'filters' => [
                'search' => $search,
                'sort' => $sort
            ]
        ]);
    }
}
