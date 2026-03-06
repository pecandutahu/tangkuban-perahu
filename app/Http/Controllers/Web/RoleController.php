<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        $query = Role::withCount('users')->orderBy('id', 'asc');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'ilike', '%' . $search . '%');
        }

        $roles = $query->paginate(10)->withQueryString();

        return Inertia::render('Master/Role/Index', [
            'roles' => $roles,
            'filters' => $request->only(['search'])
        ]);
    }

    public function create()
    {
        $permissions = Permission::orderBy('name')->get();
        return Inertia::render('Master/Role/Form', [
            'role' => new Role(),
            'permissions' => $permissions,
            'rolePermissions' => [] // empty for create
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'permissions' => 'nullable|array'
        ]);

        DB::transaction(function () use ($request, $validated) {
            $role = Role::create(['name' => $validated['name']]);
            
            if ($request->has('permissions')) {
                $role->syncPermissions($request->permissions);
            }
        });

        return redirect()->route('roles.index')->with('success', 'Peran berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $role = Role::findOrFail($id);
        $permissions = Permission::orderBy('name')->get();
        // Get array of names
        $rolePermissions = $role->permissions->pluck('name')->toArray();

        return Inertia::render('Master/Role/Form', [
            'role' => $role,
            'permissions' => $permissions,
            'rolePermissions' => $rolePermissions
        ]);
    }

    public function update(Request $request, $id)
    {
        $role = Role::findOrFail($id);

        if ($role->name === 'HR Admin') {
            return redirect()->back()->withErrors(['name' => 'Role HR Admin (Super Admin) tidak dapat diganti namanya atau dibatasi akses sistem defaultnya.']);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $id,
            'permissions' => 'nullable|array'
        ]);

        DB::transaction(function () use ($role, $request, $validated) {
            $role->update(['name' => $validated['name']]);
            
            if ($request->has('permissions')) {
                $role->syncPermissions($request->permissions);
            } else {
                $role->syncPermissions([]); // Clear if none selected
            }
        });

        return redirect()->route('roles.index')->with('success', 'Peran berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $role = Role::findOrFail($id);

        if ($role->name === 'HR Admin') {
            return redirect()->back()->withErrors(['error' => 'Super Admin Role tidak dapat dihapus!']);
        }

        $role->delete();

        return redirect()->route('roles.index')->with('success', 'Peran berhasil dihapus.');
    }
}
