<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function index(Request $request)
    {
        $query = Permission::orderBy('name', 'asc');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'ilike', '%' . $search . '%');
        }

        $permissions = $query->paginate(10)->withQueryString();

        return Inertia::render('Master/Permission/Index', [
            'permissions' => $permissions,
            'filters' => $request->only(['search'])
        ]);
    }

    public function create()
    {
        return Inertia::render('Master/Permission/Form', [
            'permission' => new Permission()
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name',
        ]);

        // Format name to lowercase and replace spaces with hyphens for standardization
        $formattedName = strtolower(str_replace(' ', '-', $request->name));

        // Ensure it doesn't conflict after formatting
        if (Permission::where('name', $formattedName)->exists()) {
            return back()->withErrors(['name' => 'Kunci Permission ini sudah terdaftar.']);
        }

        Permission::create(['name' => $formattedName]);

        return redirect()->route('permissions.index')->with('success', 'Permission berhasil ditambahkan.');
    }

    // Usually, we don't 'edit' permission names because it breaks hardcoded codebase gates. 
    // It's safer to delete and recreate if REALLY needed (and if no roles depend on it).
    // But we'll provide edit just in case.
    public function edit($id)
    {
        $permission = Permission::findOrFail($id);

        return Inertia::render('Master/Permission/Form', [
            'permission' => $permission
        ]);
    }

    public function update(Request $request, $id)
    {
        $permission = Permission::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name,' . $id,
        ]);

        $formattedName = strtolower(str_replace(' ', '-', $request->name));

        if (Permission::where('name', $formattedName)->where('id', '!=', $id)->exists()) {
            return back()->withErrors(['name' => 'Kunci Permission ini sudah terdaftar di entri lain.']);
        }

        $permission->update(['name' => $formattedName]);

        return redirect()->route('permissions.index')->with('success', 'Permission berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $permission = Permission::findOrFail($id);
        
        // Prevent deleting core master data permissions to avoid breaking the UI layout heavily
        $corePermissions = ['view-master-data', 'create-master-data', 'edit-master-data', 'delete-master-data'];
        if (in_array($permission->name, $corePermissions)) {
             return back()->withErrors(['error' => 'Permission inti (Core System) tidak boleh dihapus.']);
        }

        $permission->delete();

        return redirect()->route('permissions.index')->with('success', 'Permission berhasil dihapus.');
    }
}
