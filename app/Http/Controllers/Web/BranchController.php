<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class BranchController extends Controller
{
    public function index(Request $request)
    {
        $query = DB::table('branch')->orderBy('name');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', '%' . $search . '%')
                  ->orWhere('code', 'ilike', '%' . $search . '%');
            });
        }

        $branches = $query->paginate(10)->withQueryString();

        return Inertia::render('Master/Branch/Index', [
            'branches' => $branches,
            'filters'  => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user() && $request->user()->can('create-master-data'), 403, 'Unauthorized action.');

        $request->validate([
            'code'       => 'required|string|max:50|unique:branch,code',
            'name'       => 'required|string|max:100',
            'umr_amount' => 'nullable|numeric|min:0',
        ]);

        DB::table('branch')->insert([
            'code'       => $request->code,
            'name'       => $request->name,
            'umr_amount' => $request->umr_amount ?? 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Branch created.');
    }

    public function update(Request $request, $id)
    {
        abort_unless($request->user() && $request->user()->can('edit-master-data'), 403, 'Unauthorized action.');

        $request->validate([
            'code'       => 'required|string|max:50|unique:branch,code,' . $id,
            'name'       => 'required|string|max:100',
            'umr_amount' => 'nullable|numeric|min:0',
        ]);

        DB::table('branch')->where('id', $id)->update([
            'code'       => $request->code,
            'name'       => $request->name,
            'umr_amount' => $request->umr_amount ?? 0,
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Branch updated.');
    }

    public function destroy(Request $request, $id)
    {
        abort_unless($request->user() && $request->user()->can('delete-master-data'), 403, 'Unauthorized action.');

        DB::table('branch')->where('id', $id)->delete();

        return redirect()->back()->with('success', 'Branch deleted.');
    }
}
