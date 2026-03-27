<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $query = Employee::with(['department', 'position', 'branch'])->orderBy('name');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'ilike', '%' . $search . '%')
                  ->orWhere('nik_internal', 'ilike', '%' . $search . '%');
            });
        }

        $employees = $query->paginate(15)->withQueryString();
            
        return Inertia::render('Master/Employee/Index', [
            'employees' => $employees,
            'filters' => $request->only(['search'])
        ]);
    }

    public function create()
    {
        return Inertia::render('Master/Employee/Form', [
            'departments' => DB::table('departments')->select('id', 'name')->get(),
            'positions' => DB::table('positions')->select('id', 'name')->get(),
            'branches' => DB::table('branch')->select('id', 'name')->get(),
            'components' => DB::table('payroll_components')->select('id', 'code', 'name', 'component_type', 'is_active')->get(),
            'ptkpStatuses' => DB::table('ptkp_statuses')->select('code', 'description')->orderBy('code')->get(),
            'employee' => new Employee() // empty for create
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nik_internal' => 'required|string|max:50|unique:employees,nik_internal',
            'name' => 'required|string|max:150',
            'ktp_number' => 'nullable|string',
            'npwp_number' => 'nullable|string',
            'ptkp_status' => 'nullable|string|max:10',
            'department_id' => 'nullable|exists:departments,id',
            'position_id' => 'nullable|exists:positions,id',
            'branch_id' => 'nullable|exists:branch,id',
            'employment_type' => 'required|string|max:50',
            'join_date' => 'required|date',
            'resign_date' => 'nullable|date|after_or_equal:join_date',
            'is_active' => 'boolean',
            'payment_method' => 'required|string|max:10',
            'bank_name' => 'nullable|string|max:50',
            'bank_account' => 'nullable|string',
            'specific_components' => 'nullable|array',
            'specific_components.*.payroll_component_id' => 'required|exists:payroll_components,id',
            'specific_components.*.amount' => 'required|numeric',
        ]);

        DB::beginTransaction();
        try {
            $employee = Employee::create($validated);

            // Auto-create User account for the new Employee
            $userAccount = \App\Models\User::create([
                'name' => $employee->name,
                'email' => strtolower($employee->nik_internal) . '@company.local',
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
                'employee_id' => $employee->id,
            ]);
            $userAccount->assignRole('Karyawan');

            $finalComponents = \App\Modules\Payroll\Services\BpjsCalculatorService::calculateForAdmin($employee, $validated['specific_components'] ?? []);

            if (!empty($finalComponents)) {
                foreach ($finalComponents as $comp) {
                    $employee->specificComponents()->create([
                        'payroll_component_id' => $comp['payroll_component_id'],
                        'amount' => $comp['amount'],
                        'is_active' => true,
                    ]);
                }
                
                \App\Models\AuditLog::create([
                    'user_id' => $request->user() ? $request->user()->id : 1,
                    'entity_type' => Employee::class,
                    'entity_id' => $employee->id,
                    'action' => 'update_employee_components',
                    'before_data' => [],
                    'after_data' => $finalComponents,
                    'notes' => 'Membuat rincian komponen karyawan khusus (Hybrid Auto-Generated BPJS).'
                ]);
            }

            DB::commit();
            return redirect()->route('employees.index')->with('success', 'Employee created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal menyimpan data karyawan. ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $employee = Employee::with('specificComponents.component')->findOrFail($id);
        return Inertia::render('Master/Employee/Form', [
            'departments' => DB::table('departments')->select('id', 'name')->get(),
            'positions' => DB::table('positions')->select('id', 'name')->get(),
            'branches' => DB::table('branch')->select('id', 'name')->get(),
            'components' => DB::table('payroll_components')->select('id', 'code', 'name', 'component_type', 'is_active')->get(),
            'ptkpStatuses' => DB::table('ptkp_statuses')->select('code', 'description')->orderBy('code')->get(),
            'employee' => $employee
        ]);
    }

    public function update(Request $request, $id)
    {
        $employee = Employee::findOrFail($id);
        
        $validated = $request->validate([
            'nik_internal' => 'required|string|max:50|unique:employees,nik_internal,' . $id,
            'name' => 'required|string|max:150',
            'ktp_number' => 'nullable|string',
            'npwp_number' => 'nullable|string',
            'ptkp_status' => 'nullable|string|max:10',
            'department_id' => 'nullable|exists:departments,id',
            'position_id' => 'nullable|exists:positions,id',
            'branch_id' => 'nullable|exists:branch,id',
            'employment_type' => 'required|string|max:50',
            'join_date' => 'required|date',
            'resign_date' => 'nullable|date|after_or_equal:join_date',
            'is_active' => 'boolean',
            'payment_method' => 'required|string|max:10',
            'bank_name' => 'nullable|string|max:50',
            'bank_account' => 'nullable|string',
            'specific_components' => 'nullable|array',
            'specific_components.*.payroll_component_id' => 'required|exists:payroll_components,id',
            'specific_components.*.amount' => 'required|numeric',
        ]);

        DB::beginTransaction();
        try {
            $employee->update($validated);

            // Selalu perbarui komponen (karena jabatan/tipe pegawai mungkin berubah, memengaruhi upah dasar BPJS proporsional)
            $beforeComponents = $employee->specificComponents()->get()->toArray();
            $employee->specificComponents()->delete();

            $finalComponents = \App\Modules\Payroll\Services\BpjsCalculatorService::calculateForAdmin($employee, $validated['specific_components'] ?? []);
            
            $afterComponents = [];
            if (!empty($finalComponents)) {
                foreach ($finalComponents as $comp) {
                    $created = $employee->specificComponents()->create([
                        'payroll_component_id' => $comp['payroll_component_id'],
                        'amount' => $comp['amount'],
                        'is_active' => true,
                    ]);
                    $afterComponents[] = $created->toArray();
                }
            }

            \App\Models\AuditLog::create([
                'user_id' => $request->user() ? $request->user()->id : 1,
                'entity_type' => Employee::class,
                'entity_id' => $employee->id,
                'action' => 'update_employee_components',
                'before_data' => $beforeComponents,
                'after_data' => $afterComponents,
                'notes' => 'Memperbarui rincian tunjangan/potongan khusus spesifik milik karyawan (Hybrid Auto-Generated BPJS).'
            ]);

            DB::commit();
            return redirect()->route('employees.index')->with('success', 'Employee updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal memperbarui data karyawan. ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        Employee::findOrFail($id)->delete();
        return redirect()->back()->with('success', 'Employee deleted.');
    }

    /**
     * Riwayat payroll per karyawan — semua periode yang pernah diikuti.
     */
    public function payrollHistory(Request $request, $id)
    {
        $employee = Employee::with(['department:id,name', 'position:id,name', 'branch:id,name'])->findOrFail($id);

        $historyQuery = \App\Models\PayrollItem::with('period:id,code,start_date,end_date,pay_date,status')
            ->where('employee_id', $id)
            ->orderByDesc('created_at');

        $history = $historyQuery->paginate(12)->withQueryString();

        return Inertia::render('Master/Employee/PayrollHistory', [
            'employee' => $employee,
            'history'  => $history,
        ]);
    }

    public function myPayslips(Request $request)
    {
        $user = $request->user();
        if (!$user->employee_id) {
            return Inertia::render('Employee/MyPayslip', [
                'error' => 'Akun Anda belum ditautkan ke data Karyawan. Silakan hubungi HRD.',
                'employee' => null,
                'history' => null
            ]);
        }

        $employee = Employee::with(['department', 'position', 'branch'])->find($user->employee_id);

        $query = \App\Models\PayrollItem::with(['period' => function ($q) {
                $q->orderBy('start_date', 'desc');
            }])
            ->where('employee_id', $user->employee_id)
            ->whereHas('period', function ($q) {
                // only show non-draft
                $q->where('status', '!=', 'draft');
            });

        $history = $query->paginate(12)->withQueryString();

        return Inertia::render('Employee/MyPayslip', [
            'employee' => $employee,
            'history' => $history,
            'error' => null
        ]);
    }
}
