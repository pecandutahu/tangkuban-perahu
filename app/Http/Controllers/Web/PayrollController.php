<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\PayrollPeriod;
use App\Models\PayrollItem;
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
        $period = PayrollPeriod::with([
            'auditLogs.user:id,name'
        ])->findOrFail($id);

        $period->auditLogs = $period->auditLogs->sortByDesc('created_at')->values();

        $itemsQuery = PayrollItem::with([
            'employee' => fn($q) => $q->select('id', 'name', 'nik_internal'),
            'components'
        ])
        ->withSum(['components as import_earning_total' => fn($q) => $q->where('source', 'IMPORT')->where('component_type', 'earning')], 'amount')
        ->withSum(['components as import_deduction_total' => fn($q) => $q->where('source', 'IMPORT')->where('component_type', 'deduction')], 'amount')
        ->where('payroll_period_id', $period->id);

        $search = $request->input('search');
        if (!empty($search)) {
            $itemsQuery->whereHas('employee', fn($q) =>
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('nik_internal', 'ilike', "%{$search}%")
            );
        }

        $sort = $request->input('sort');
        if ($sort === 'tinggi_earning') {
            $itemsQuery->orderByRaw('import_earning_total DESC NULLS LAST');
        } elseif ($sort === 'tinggi_deduction') {
            $itemsQuery->orderByRaw('import_deduction_total DESC NULLS LAST');
        } else {
            $itemsQuery->join('employees', 'payroll_items.employee_id', '=', 'employees.id')
                       ->orderBy('employees.nik_internal', 'asc')
                       ->select('payroll_items.*');
        }

        $items = $itemsQuery->paginate(15)->withQueryString();

        return Inertia::render('Payroll/Show', [
            'period'  => $period,
            'items'   => $items,
            'filters' => ['search' => $search, 'sort' => $sort],
        ]);
    }

    /**
     * Halaman Rekap mandiri dengan filter periode (dropdown pilih periode).
     */
    public function rekapIndex(Request $request)
    {
        // Daftar semua periode untuk dropdown filter
        $periods = PayrollPeriod::where('status', 'paid')->orderBy('start_date', 'desc')
            ->get(['id', 'code', 'start_date', 'end_date', 'status']);

        $selectedId = $request->input('period_id', optional($periods->first())->id);

        $summary = null;
        $grouped = collect();
        $selected = null;

        if ($selectedId) {
            $selected = $periods->firstWhere('id', $selectedId);
            $items = PayrollItem::where('payroll_period_id', $selectedId)
                ->with('employee.position:id,name')
                ->get(['id', 'employee_id', 'branch_name', 'department_name', 'employee_name',
                       'total_bruto', 'total_deduction', 'total_netto']);

            $summary = [
                'total_employees'  => $items->count(),
                'total_bruto'      => $items->sum('total_bruto'),
                'total_deduction'  => $items->sum('total_deduction'),
                'total_netto'      => $items->sum('total_netto'),
            ];

            $grouped = $items
                ->groupBy(fn($i) => $i->branch_name ?: '(Tanpa Cabang)')
                ->map(function ($branchItems, $branchName) {
                    $byDept = $branchItems
                        ->groupBy(fn($i) => $i->department_name ?: '(Tanpa Departemen)')
                        ->map(function ($deptItems, $deptName) {
                            $byPosition = $deptItems
                                ->groupBy(fn($i) => optional($i->employee?->position)->name ?? '(Tanpa Jabatan)')
                                ->map(fn($posItems, $posName) => [
                                    'name'            => $posName,
                                    'employee_count'  => $posItems->count(),
                                    'total_bruto'     => $posItems->sum('total_bruto'),
                                    'total_deduction' => $posItems->sum('total_deduction'),
                                    'total_netto'     => $posItems->sum('total_netto'),
                                ])->sortBy('name')->values();

                            return [
                                'name'            => $deptName,
                                'employee_count'  => $deptItems->count(),
                                'total_bruto'     => $deptItems->sum('total_bruto'),
                                'total_deduction' => $deptItems->sum('total_deduction'),
                                'total_netto'     => $deptItems->sum('total_netto'),
                                'positions'       => $byPosition,
                            ];
                        })->sortBy('name')->values();

                    return [
                        'name'            => $branchName,
                        'employee_count'  => $branchItems->count(),
                        'total_bruto'     => $branchItems->sum('total_bruto'),
                        'total_deduction' => $branchItems->sum('total_deduction'),
                        'total_netto'     => $branchItems->sum('total_netto'),
                        'departments'     => $byDept,
                    ];
                })->sortBy('name')->values();
        }

        return Inertia::render('Payroll/RekapIndex', [
            'periods'    => $periods,
            'selectedId' => $selectedId ? (int) $selectedId : null,
            'selected'   => $selected,
            'summary'    => $summary,
            'grouped'    => $grouped->values(),
        ]);
    }

    /**
     * Halaman rekap agregasi per cabang/departemen/jabatan untuk satu periode.
     */
    public function recap($id)
    {
        $period = PayrollPeriod::findOrFail($id);

        $items = PayrollItem::where('payroll_period_id', $id)
            ->with('employee.position:id,name')
            ->get(['id', 'employee_id', 'branch_name', 'department_name', 'employee_name',
                   'total_bruto', 'total_deduction', 'total_netto']);

        // Summary keseluruhan
        $summary = [
            'total_employees'  => $items->count(),
            'total_bruto'      => $items->sum('total_bruto'),
            'total_deduction'  => $items->sum('total_deduction'),
            'total_netto'      => $items->sum('total_netto'),
        ];

        // Grouping hierarkis: Cabang → Departemen → Jabatan
        $grouped = $items
            ->groupBy(fn($i) => $i->branch_name ?: '(Tanpa Cabang)')
            ->map(function ($branchItems, $branchName) {
                $byDept = $branchItems
                    ->groupBy(fn($i) => $i->department_name ?: '(Tanpa Departemen)')
                    ->map(function ($deptItems, $deptName) {
                        $byPosition = $deptItems
                            ->groupBy(fn($i) => optional($i->employee?->position)->name ?? '(Tanpa Jabatan)')
                            ->map(function ($posItems, $posName) {
                                return [
                                    'name'            => $posName,
                                    'employee_count'  => $posItems->count(),
                                    'total_bruto'     => $posItems->sum('total_bruto'),
                                    'total_deduction' => $posItems->sum('total_deduction'),
                                    'total_netto'     => $posItems->sum('total_netto'),
                                ];
                            })->sortBy('name')->values();

                        return [
                            'name'            => $deptName,
                            'employee_count'  => $deptItems->count(),
                            'total_bruto'     => $deptItems->sum('total_bruto'),
                            'total_deduction' => $deptItems->sum('total_deduction'),
                            'total_netto'     => $deptItems->sum('total_netto'),
                            'positions'       => $byPosition,
                        ];
                    })->sortBy('name')->values();

                return [
                    'name'            => $branchName,
                    'employee_count'  => $branchItems->count(),
                    'total_bruto'     => $branchItems->sum('total_bruto'),
                    'total_deduction' => $branchItems->sum('total_deduction'),
                    'total_netto'     => $branchItems->sum('total_netto'),
                    'departments'     => $byDept,
                ];
            })->sortBy('name')->values();

        return Inertia::render('Payroll/Recap', [
            'period'  => $period,
            'summary' => $summary,
            'grouped' => $grouped,
        ]);
    }

    /**
     * Export CSV rekap detail per karyawan.
     */
    public function exportCsv($id)
    {
        $period = PayrollPeriod::findOrFail($id);

        $items = PayrollItem::where('payroll_period_id', $id)
            ->with('employee:id,nik_internal,name,bank_name,bank_account,payment_method')
            ->orderBy('employee_name')
            ->get();

        $filename = "rekap_payroll_{$period->code}.csv";

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($items) {
            $out = fopen('php://output', 'w');
            // BOM agar Excel bisa buka UTF-8
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                'NIK', 'Nama Karyawan', 'Cabang', 'Departemen',
                'Total Bruto', 'Total Potongan', 'Total Netto'
            ]);

            foreach ($items as $item) {
                fputcsv($out, [
                    $item->employee?->nik_internal ?? '-',
                    $item->employee_name,
                    $item->branch_name,
                    $item->department_name,
                    $item->total_bruto,
                    $item->total_deduction,
                    $item->total_netto,
                ]);
            }

            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export CSV format transfer bank.
     */
    public function exportBankTransfer($id)
    {
        $period = PayrollPeriod::findOrFail($id);

        $items = PayrollItem::where('payroll_period_id', $id)
            ->with('employee:id,name,bank_name,bank_account,payment_method')
            ->whereHas('employee', function ($query) {
                $query->where('payment_method', 'transfer');
            })
            ->orderBy('employee_name')
            ->get();

        $filename = "transfer_bank_{$period->code}.csv";

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($items) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, ['Nama Karyawan', 'Bank', 'Nomor Rekening', 'Nominal Transfer']);

            foreach ($items as $item) {
                $emp = $item->employee;
                // Hanya karyawan dengan payment method transfer
                fputcsv($out, [
                    $item->employee_name,
                    $emp?->bank_name ?? '-',
                    $emp?->bank_account ?? '-',
                    $item->total_netto,
                ]);
            }

            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Delete Draft Payroll Period
     */
    public function destroy($id)
    {
        $period = PayrollPeriod::findOrFail($id);

        if ($period->status !== 'draft') {
            return redirect()->back()->withErrors(['error' => 'Hanya periode berstatus Draft yang dapat dihapus.']);
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($period) {
            foreach ($period->items as $item) {
                // Hapus komponen spesifik tiap item
                $item->components()->delete();
                $item->delete();
            }
            // Hapus log period ini jika ada cascade opsional
            $period->auditLogs()->delete();
            $period->delete();
        });

        return redirect()->route('payroll.index')->with('success', 'Periode Draft berhasil dihapus selamanya.');
    }
}
