<?php

namespace App\Http\Controllers\Peserta;

use App\Http\Controllers\Controller;
use App\Models\Participant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class PasswordResetController extends Controller
{
    public function showForgot()
    {
        if (auth()->guard('participant')->check()) {
            return redirect()->route('peserta.dashboard');
        }

        return inertia('Peserta/Auth/ForgotPassword');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        try {
            $status = Password::broker('participants')->sendResetLink(
                $request->only('email')
            );
        } catch (\Exception $e) {
            report($e);
            return back()->withErrors(['email' => 'Gagal mengirim email reset password. Silakan coba lagi beberapa saat lagi.']);
        }

        return match ($status) {
            Password::RESET_LINK_SENT => back()->with('success', 'Link reset password telah dikirim ke email Anda. Periksa inbox atau folder spam.'),
            Password::RESET_THROTTLED => back()->withErrors(['email' => 'Terlalu banyak permintaan reset password. Coba lagi dalam beberapa menit.']),
            default                   => back()->withErrors(['email' => 'Email tidak ditemukan di sistem kami.']),
        };
    }

    public function showReset(Request $request, string $token)
    {
        return inertia('Peserta/Auth/ResetPassword', [
            'token' => $token,
            'email' => $request->email,
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token'                 => 'required',
            'email'                 => 'required|email',
            'password'              => 'required|min:8|confirmed',
            'password_confirmation' => 'required',
        ]);

        $status = Password::broker('participants')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (Participant $user, string $password) {
                $user->update(['password' => Hash::make($password)]);
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('peserta.login')->with('success', 'Password berhasil direset. Silakan login dengan password baru Anda.')
            : back()->withErrors(['email' => 'Link reset tidak valid atau sudah kadaluarsa. Silakan minta link baru.']);
    }
}
