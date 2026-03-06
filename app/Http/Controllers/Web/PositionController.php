<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PositionController extends Controller
{
    public function index(Request $request)
    {
        $query = DB::table('positions')->orderBy('name');
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'ilike', '%' . $search . '%');
        }

        $positions = $query->paginate(10)->withQueryString();

        return Inertia::render('Master/Position/Index', [
            'positions' => $positions,
            'filters' => $request->only(['search'])
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->can('create-master-data'), 403, 'Unauthorized action.');

        $request->validate([
            'name' => 'required|string|max:100',
        ]);

        DB::table('positions')->insert([
            'name' => $request->name,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Position created.');
    }

    public function update(Request $request, $id)
    {
        abort_unless(auth()->user()->can('edit-master-data'), 403, 'Unauthorized action.');

        $request->validate([
            'name' => 'required|string|max:100',
        ]);

        DB::table('positions')->where('id', $id)->update([
            'name' => $request->name,
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Position updated.');
    }

    public function destroy($id)
    {
        abort_unless(auth()->user()->can('delete-master-data'), 403, 'Unauthorized action.');

        DB::table('positions')->where('id', $id)->delete();
        return redirect()->back()->with('success', 'Position deleted.');
    }
}
