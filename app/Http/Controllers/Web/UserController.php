<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Inertia\Inertia;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('roles');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'ilike', '%' . $search . '%')
                  ->orWhere('email', 'ilike', '%' . $search . '%');
        }

        $users = $query->paginate(10)->withQueryString();

        return Inertia::render('Master/User/Index', [
            'users' => $users,
            'filters' => $request->only(['search'])
        ]);
    }

    public function create()
    {
        $roles = Role::orderBy('name')->get();
        return Inertia::render('Master/User/Form', [
            'userModel' => new User(), // passing as userModel to avoid conflict with auth.user
            'roles' => $roles,
            'userRole' => null
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => 'required|exists:roles,name'
        ]);

        $user = clone new User();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = Hash::make($request->password);
        $user->save();

        $user->assignRole($request->role);

        return redirect()->route('users.index')->with('success', 'User berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $user = User::findOrFail($id);
        $roles = Role::orderBy('name')->get();
        $userRole = $user->roles->first()?->name;

        return Inertia::render('Master/User/Form', [
            'userModel' => $user,
            'roles' => $roles,
            'userRole' => $userRole
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        // Safety check to prevent changing the first user's HR Admin role if they are the only one
        if ($user->id === 1 && $request->role !== 'HR Admin') {
             return redirect()->back()->withErrors(['role' => 'Akun Root (ID 1) harus tetap menjadi HR Admin.']);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:users,email,' . $user->id,
            'role' => 'required|exists:roles,name'
        ]);

        $user->name = $request->name;
        $user->email = $request->email;

        // Only update password if provided
        if ($request->filled('password')) {
            $request->validate([
                'password' => ['confirmed', Rules\Password::defaults()]
            ]);
            $user->password = Hash::make($request->password);
        }

        $user->save();

        $user->syncRoles([$request->role]);

        return redirect()->route('users.index')->with('success', 'User berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);

        if ($user->id === 1 || $user->id === auth()->id()) {
            return redirect()->back()->withErrors(['error' => 'Tidak dapat menghapus akun root atau akun Anda sendiri!']);
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'User berhasil dihapus.');
    }
}
