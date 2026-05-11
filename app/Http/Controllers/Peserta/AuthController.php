<?php

namespace App\Http\Controllers\Peserta;

use App\Http\Controllers\Controller;
use App\Models\Participant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showRegister()
    {
        if (auth()->guard('participant')->check()) {
            return redirect()->route('peserta.dashboard');
        }

        return inertia('Peserta/Auth/Register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name'                  => 'required|string|max:255',
            'email'                 => 'required|email|unique:participants,email',
            'password'              => 'required|min:8|confirmed',
        ]);

        $participant = Participant::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

        auth()->guard('participant')->login($participant);

        return redirect()->route('peserta.dashboard');
    }

    public function showLogin()
    {
        if (auth()->guard('participant')->check()) {
            return redirect()->route('peserta.dashboard');
        }

        return inertia('Peserta/Auth/Login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $participant = Participant::where('email', $request->email)->first();

        if (!$participant || !Hash::check($request->password, $participant->password)) {
            return back()->with('error', 'Email atau password salah.');
        }

        auth()->guard('participant')->login($participant, $request->boolean('remember'));

        return redirect()->route('peserta.dashboard');
    }

    public function logout(Request $request)
    {
        auth()->guard('participant')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('peserta.login');
    }
}
