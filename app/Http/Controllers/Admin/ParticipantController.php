<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Participant;
use Illuminate\Http\Request;

class ParticipantController extends Controller
{
    public function resetPassword(Request $request, Participant $participant)
    {
        $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        // Kolom password punya cast 'hashed' pada model Participant —
        // jangan di-bcrypt() manual di sini, nanti ter-hash dua kali.
        $participant->update([
            'password' => $request->password,
        ]);

        return back()->with('success', 'Password peserta berhasil diganti.');
    }
}
