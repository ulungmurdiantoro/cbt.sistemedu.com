<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\SignatureImageProcessor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::orderBy('role')->orderBy('name')
            ->when($request->q, function ($query) use ($request) {
                $query->where(function ($sub) use ($request) {
                    $sub->where('name', 'like', '%' . $request->q . '%')
                        ->orWhere('email', 'like', '%' . $request->q . '%')
                        ->orWhere('users_code', 'like', '%' . $request->q . '%');
                });
            })
            ->paginate(15)
            ->withQueryString();
        $users->each(fn($u) => $u->makeVisible(['users_code']));

        return inertia('Admin/Users/Index', [
            'users'   => $users,
            'filters' => $request->only('q'),
        ]);
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
        $user->makeVisible(['users_code', 'signature_path', 'signature_name']);
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

    public function saveSignature(Request $request, User $user)
    {
        $hasNewSig = $request->signature_data || $request->hasFile('signature_file');
        abort_if(!$hasNewSig, 422, 'Tanda tangan wajib diisi (gambar atau upload).');

        $request->validate([
            'signature_name' => 'nullable|string|max:255',
            'signature_data' => 'nullable|string',
            'signature_file' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
        ]);

        $sigPath = $this->storeUserSignature($request, $user);

        $user->update([
            'signature_path' => $sigPath,
            'signature_name' => $request->signature_name ?: $user->name,
        ]);

        return back()->with('success', 'Tanda tangan berhasil disimpan.');
    }

    public function serveSignature(User $user)
    {
        abort_if(!$user->signature_path || !Storage::disk('private')->exists($user->signature_path), 404);

        return response()->file(Storage::disk('private')->path($user->signature_path), [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma'        => 'no-cache',
        ]);
    }

    private function storeUserSignature(Request $request, User $user): string
    {
        $disk = Storage::disk('private');
        $dir  = 'user-signatures/' . $user->id;
        $now  = now()->format('YmdHis');
        $path = $dir . '/sig_' . $now . '.png';

        if ($request->hasFile('signature_file')) {
            $raw = file_get_contents($request->file('signature_file')->getRealPath());
            $disk->put($path, SignatureImageProcessor::removeBackground($raw));
            return $path;
        }

        $data = $request->signature_data;
        if (preg_match('/^data:image\/(png|jpe?g);base64,(.+)$/', $data, $m)) {
            $decoded = base64_decode($m[2]);
            $disk->put($path, SignatureImageProcessor::removeBackground($decoded));
            return $path;
        }

        abort(422, 'Format tanda tangan tidak valid.');
    }
}
