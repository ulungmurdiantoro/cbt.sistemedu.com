<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Support\SignatureImageProcessor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * TTD self-service untuk Pengambil Keputusan — sama pola dengan
 * Asesor\LaporanAsesmenController::saveSignature/serveSignature. Selalu
 * beroperasi pada auth()->user(), jadi tidak perlu otorisasi tambahan.
 * Tersimpan di users.signature_path, dipakai otomatis untuk membubuhkan TTD
 * Pengambil Keputusan di Keputusan Sertifikasi.
 */
class SignatureController extends Controller
{
    public function show()
    {
        $user = auth()->user();

        return inertia('Manager/Signature/Show', [
            'has_signature'  => (bool) $user->signature_path,
            'signature_name' => $user->signature_name ?: $user->name,
        ]);
    }

    public function save(Request $request)
    {
        $user = auth()->user();

        $hasNewSig = $request->signature_data || $request->hasFile('signature_file');
        abort_if(!$hasNewSig, 422, 'Tanda tangan wajib diisi (gambar atau upload).');

        $request->validate([
            'signature_data' => 'nullable|string',
            'signature_file' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
        ]);

        $disk = Storage::disk('private');
        $path = 'user-signatures/' . $user->id . '/sig_' . now()->format('YmdHis') . '.png';

        if ($request->hasFile('signature_file')) {
            $raw = file_get_contents($request->file('signature_file')->getRealPath());
            $disk->put($path, SignatureImageProcessor::removeBackground($raw));
        } elseif (preg_match('/^data:image\/(png|jpe?g);base64,(.+)$/', (string) $request->signature_data, $m)) {
            $disk->put($path, SignatureImageProcessor::removeBackground(base64_decode($m[2])));
        } else {
            abort(422, 'Format tanda tangan tidak valid.');
        }

        $user->update([
            'signature_path' => $path,
            'signature_name' => $user->signature_name ?: $user->name,
        ]);

        return back()->with('success', 'Tanda tangan berhasil disimpan.');
    }

    public function serve()
    {
        $user = auth()->user();
        abort_if(!$user->signature_path || !Storage::disk('private')->exists($user->signature_path), 404);

        return response()->file(Storage::disk('private')->path($user->signature_path), [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma'        => 'no-cache',
        ]);
    }
}
