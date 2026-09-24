<?php

namespace App\Http\Controllers\Student;

use App\Models\Student;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\RateLimiter;

class LoginController extends Controller
{
    /** Maksimal login gagal per IP per menit. */
    private const MAX_FAILED_ATTEMPTS = 10;

    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        //validate the form data
        $request->validate([
            'no_participant'    => 'required',
            // 'password'          => 'required',
        ]);

        // Batasi percobaan GAGAL per IP untuk mencegah menebak No. Peserta.
        // Hanya yang gagal yang dihitung, supaya satu ruang ujian di balik
        // satu IP (NAT) tetap bisa login bersamaan.
        $throttleKey = 'student-login-failed|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_FAILED_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return redirect()->back()->with('error', "Terlalu banyak percobaan gagal. Coba lagi dalam {$seconds} detik.");
        }

        //cek email dan password
        $student = Student::where([
            'no_participant'    => $request->no_participant,
            // 'password'          => $request->password
        ])->first();

        if(!$student) {
            RateLimiter::hit($throttleKey, 60);

            return redirect()->back()->with('error', 'No. Peserta Salah salah');
        }

        //login the user
        auth()->guard('student')->login($student);
        $request->session()->regenerate();

        //redirect to dashboard
        return redirect()->route('student.dashboard');
    }
}