<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DepartmentController extends Controller
{
    public function index(Request $request)
    {
        $query = DB::table('departments')->orderBy('name');
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'ilike', '%' . $search . '%')
                  ->orWhere('code', 'ilike', '%' . $search . '%');
            });
        }

        $departments = $query->paginate(10)->withQueryString();

        return Inertia::render('Master/Department/Index', [
            'departments' => $departments,
            'filters' => $request->only(['search'])
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->can('create-master-data'), 403, 'Unauthorized action.');

        $request->validate([
            'code' => 'required|string|max:50|unique:departments,code',
            'name' => 'required|string|max:100',
        ]);

        DB::table('departments')->insert([
            'code' => $request->code,
            'name' => $request->name,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Department created.');
    }

    public function update(Request $request, $id)
    {
        abort_unless(auth()->user()->can('edit-master-data'), 403, 'Unauthorized action.');

        $request->validate([
            'code' => 'required|string|max:50|unique:departments,code,' . $id,
            'name' => 'required|string|max:100',
        ]);

        DB::table('departments')->where('id', $id)->update([
            'code' => $request->code,
            'name' => $request->name,
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Department updated.');
    }

    public function destroy($id)
    {
        abort_unless(auth()->user()->can('delete-master-data'), 403, 'Unauthorized action.');

        DB::table('departments')->where('id', $id)->delete();
        return redirect()->back()->with('success', 'Department deleted.');
    }
}
