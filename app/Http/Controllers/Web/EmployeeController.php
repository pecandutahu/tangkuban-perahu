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
        $query = Employee::with(['department', 'position', 'branch'])->orderBy('nik_internal');

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
        ]);

        Employee::create($validated);

        return redirect()->route('employees.index')->with('success', 'Employee created successfully.');
    }

    public function edit($id)
    {
        $employee = Employee::findOrFail($id);
        return Inertia::render('Master/Employee/Form', [
            'departments' => DB::table('departments')->select('id', 'name')->get(),
            'positions' => DB::table('positions')->select('id', 'name')->get(),
            'branches' => DB::table('branch')->select('id', 'name')->get(),
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
        ]);

        $employee->update($validated);

        return redirect()->route('employees.index')->with('success', 'Employee updated successfully.');
    }

    public function destroy($id)
    {
        Employee::findOrFail($id)->delete();
        return redirect()->back()->with('success', 'Employee deleted.');
    }
}
