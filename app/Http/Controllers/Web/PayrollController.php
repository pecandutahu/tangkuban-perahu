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

    public function show($id)
    {
        $period = PayrollPeriod::with([
            'items.employee' => function($q) {
                // To fetch associated names
                $q->select('id', 'name', 'nik_internal');
            },
            'auditLogs.user:id,name'
        ])->findOrFail($id);

        // Sort descending by created_at since it's a history timeline
        $period->auditLogs = $period->auditLogs->sortByDesc('created_at')->values();

        return Inertia::render('Payroll/Show', [
            'period' => $period
        ]);
    }
}
