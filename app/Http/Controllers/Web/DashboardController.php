<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\PayrollItem;
use App\Models\PayrollPeriod;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        // ─── Stats Master Data ───────────────────────────────────────────────
        $totalEmployees       = Employee::where('is_active', true)->count();
        $totalBranches        = DB::table('branch')->count();
        $totalDepartments     = DB::table('departments')->count();
        $totalPositions       = DB::table('positions')->count();
        $inactiveEmployees    = Employee::where('is_active', false)->count();

        // Karyawan per cabang (top 5)
        $employeesByBranch = DB::table('employees')
            ->join('branch', 'branch.id', '=', 'employees.branch_id')
            ->where('employees.is_active', true)
            ->groupBy('branch.name')
            ->orderByDesc('count')
            ->limit(5)
            ->selectRaw('branch.name, COUNT(*) as count')
            ->get();

        // Karyawan per departemen (top 5)
        $employeesByDept = DB::table('employees')
            ->join('departments', 'departments.id', '=', 'employees.department_id')
            ->where('employees.is_active', true)
            ->groupBy('departments.name')
            ->orderByDesc('count')
            ->limit(5)
            ->selectRaw('departments.name, COUNT(*) as count')
            ->get();

        // ─── Stats Payroll ───────────────────────────────────────────────────
        $lastPaidPeriod = PayrollPeriod::where('status', 'paid')
            ->orderByDesc('pay_date')
            ->first(['id', 'code', 'start_date', 'end_date', 'pay_date', 'status']);

        $lastPaidSummary = null;
        if ($lastPaidPeriod) {
            $lastPaidSummary = PayrollItem::where('payroll_period_id', $lastPaidPeriod->id)
                ->selectRaw('COUNT(*) as employees, SUM(total_bruto) as bruto, SUM(total_deduction) as deduction, SUM(total_netto) as netto')
                ->first();
        }

        // Daftar periode aktif (draft/review/approved)
        $activePeriods = PayrollPeriod::whereIn('status', ['draft', 'reviewed', 'approved'])
            ->withCount('items')
            ->orderByDesc('start_date')
            ->limit(5)
            ->get(['id', 'code', 'start_date', 'end_date', 'status']);

        // Tren netto 6 periode paid terakhir (untuk chart/tabel)
        $payrollTrend = PayrollPeriod::where('status', 'paid')
            ->orderByDesc('pay_date')
            ->limit(6)
            ->get(['id', 'code', 'pay_date'])
            ->map(function ($p) {
                $sum = PayrollItem::where('payroll_period_id', $p->id)
                    ->selectRaw('SUM(total_netto) as netto, COUNT(*) as employees')
                    ->first();
                return [
                    'code'      => $p->code,
                    'pay_date'  => $p->pay_date,
                    'netto'     => $sum?->netto ?? 0,
                    'employees' => $sum?->employees ?? 0,
                ];
            })->reverse()->values();

        return Inertia::render('Dashboard', [
            'stats' => [
                'total_employees'    => $totalEmployees,
                'inactive_employees' => $inactiveEmployees,
                'total_branches'     => $totalBranches,
                'total_departments'  => $totalDepartments,
                'total_positions'    => $totalPositions,
            ],
            'employeesByBranch' => $employeesByBranch,
            'employeesByDept'   => $employeesByDept,
            'lastPaidPeriod'    => $lastPaidPeriod,
            'lastPaidSummary'   => $lastPaidSummary,
            'activePeriods'     => $activePeriods,
            'payrollTrend'      => $payrollTrend,
        ]);
    }
}
