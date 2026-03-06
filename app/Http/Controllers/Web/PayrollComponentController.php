<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\PayrollComponent;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PayrollComponentController extends Controller
{
    public function index(Request $request)
    {
        $query = PayrollComponent::orderBy('code');
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'ilike', '%' . $search . '%')
                  ->orWhere('code', 'ilike', '%' . $search . '%');
            });
        }

        $components = $query->paginate(10)->withQueryString();

        return Inertia::render('Master/PayrollComponent/Index', [
            'components' => $components,
            'filters' => $request->only(['search'])
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->can('create-master-data'), 403, 'Unauthorized action.');

        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:payroll_components,code',
            'name' => 'required|string|max:100',
            'component_type' => 'required|in:earning,deduction',
            'is_variable' => 'required|boolean',
            'default_amount' => 'required|numeric|min:0',
            'is_active' => 'required|boolean',
        ]);

        PayrollComponent::create($validated);

        return redirect()->back()->with('success', 'Component created.');
    }

    public function update(Request $request, $id)
    {
        abort_unless(auth()->user()->can('edit-master-data'), 403, 'Unauthorized action.');

        $component = PayrollComponent::findOrFail($id);
        
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:payroll_components,code,' . $id,
            'name' => 'required|string|max:100',
            'component_type' => 'required|in:earning,deduction',
            'is_variable' => 'required|boolean',
            'default_amount' => 'required|numeric|min:0',
            'is_active' => 'required|boolean',
        ]);

        $component->update($validated);

        return redirect()->back()->with('success', 'Component updated.');
    }

    public function destroy($id)
    {
        abort_unless(auth()->user()->can('delete-master-data'), 403, 'Unauthorized action.');

        PayrollComponent::findOrFail($id)->delete();
        return redirect()->back()->with('success', 'Component deleted.');
    }
}
