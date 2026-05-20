<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $users = User::orderBy('role')->orderBy('name')->paginate(15);
        $users->each(fn($u) => $u->makeVisible(['users_code']));

        return inertia('Admin/Users/Index', ['users' => $users]);
    }

    public function create()
    {
        return inertia('Admin/Users/Create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'users_code' => 'required|string|max:50|unique:users,users_code',
            'name'       => 'required|string|max:255',
            'email'      => 'required|email|unique:users,email',
            'role'       => 'required|in:admin,asesor',
            'password'   => 'required|string|min:8|confirmed',
        ]);

        User::create([
            'users_code' => $request->users_code,
            'name'       => $request->name,
            'email'      => $request->email,
            'role'       => $request->role,
            'password'   => Hash::make($request->password),
        ]);

        return redirect()->route('admin.users.index')->with('success', 'User berhasil ditambahkan.');
    }

    public function edit(User $user)
    {
        $user->makeVisible(['users_code']);
        return inertia('Admin/Users/Edit', ['user' => $user]);
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'users_code' => ['required', 'string', 'max:50', Rule::unique('users', 'users_code')->ignore($user->id)],
            'name'       => 'required|string|max:255',
            'email'      => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'role'       => 'required|in:admin,asesor',
            'password'   => 'nullable|string|min:8|confirmed',
        ]);

        $data = [
            'users_code' => $request->users_code,
            'name'       => $request->name,
            'email'      => $request->email,
            'role'       => $request->role,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return redirect()->route('admin.users.index')->with('success', 'User berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        abort_if($user->id === auth()->id(), 403, 'Tidak dapat menghapus akun sendiri.');
        $user->delete();

        return back()->with('success', 'User berhasil dihapus.');
    }
}
