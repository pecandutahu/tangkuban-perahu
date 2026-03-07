<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\PayrollTemplate;
use App\Models\PayrollComponent;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PayrollTemplateController extends Controller
{
    public function index(Request $request)
    {
        $query = PayrollTemplate::with(['position'])->withCount('components')->orderBy('name');
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'ilike', '%' . $search . '%');
        }

        $templates = $query->paginate(10)->withQueryString();

        return Inertia::render('Master/PayrollTemplate/Index', [
            'templates' => $templates,
            'filters' => $request->only(['search'])
        ]);
    }

    public function create()
    {
        // Fetch all active components to assign to template
        $components = PayrollComponent::where('is_active', true)->orderBy('name')->get();
        $positions = \App\Models\Position::orderBy('name')->get();

        return Inertia::render('Master/PayrollTemplate/Create', [
            'components' => $components,
            'positions' => $positions
        ]);
    }

    public function store(Request $request)
    {
         $validated = $request->validate([
            'name' => 'required|string|max:100',
            'employment_type' => 'required|string|max:50',
            'position_id' => 'nullable|exists:positions,id',
            'components' => 'array',
            'components.*' => 'exists:payroll_components,id',
        ]);

        $template = PayrollTemplate::create([
            'name' => $validated['name'],
            'employment_type' => $validated['employment_type'],
            'position_id' => $validated['position_id'],
        ]);

        if (!empty($validated['components'])) {
             // Create individual template components
             foreach ($validated['components'] as $compId) {
                 $template->components()->create([
                     'payroll_component_id' => $compId
                 ]);
             }
        }

        return redirect()->route('templates.index')->with('success', 'Template created successfully.');
    }

    public function edit($id)
    {
        $template = PayrollTemplate::with('components:id,payroll_template_id,payroll_component_id')->findOrFail($id);
        $components = PayrollComponent::where('is_active', true)->orderBy('name')->get();
        $positions = \App\Models\Position::orderBy('name')->get();

        return Inertia::render('Master/PayrollTemplate/Edit', [
            'template' => $template,
            'components' => $components,
            'positions' => $positions,
        ]);
    }

    public function update(Request $request, $id)
    {
        $template = PayrollTemplate::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'employment_type' => 'required|string|max:50',
            'position_id' => 'nullable|exists:positions,id',
            'components' => 'array',
            'components.*' => 'exists:payroll_components,id',
        ]);

        $template->update([
            'name' => $validated['name'],
            'employment_type' => $validated['employment_type'],
            'position_id' => $validated['position_id'],
        ]);

        // Sync components: delete old and recreate new
        $template->components()->delete();
        if (!empty($validated['components'])) {
             foreach ($validated['components'] as $compId) {
                 $template->components()->create([
                     'payroll_component_id' => $compId
                 ]);
             }
        }

        return redirect()->route('templates.index')->with('success', 'Template updated successfully.');
    }

    public function destroy($id)
    {
        PayrollTemplate::findOrFail($id)->delete();
        return redirect()->back()->with('success', 'Template deleted.');
    }
}
