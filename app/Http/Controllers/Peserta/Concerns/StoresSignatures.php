<?php

namespace App\Http\Controllers\Peserta\Concerns;

use App\Support\SignatureImageProcessor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

// Dipakai oleh controller peserta manapun yang butuh simpan TTD (canvas
// digambar atau file diupload) ke disk privat. Diekstrak dari
// ApplicationController supaya FrAk14Controller bisa pakai logic yang sama.
trait StoresSignatures
{
    private function storeSignature(Request $request, string $prefix): string
    {
        $name = $prefix . '_' . time() . '.png';

        if ($request->filled('signature_data')) {
            $request->validate(['signature_data' => 'required|string']);
            $raw = preg_replace('/^data:image\/\w+;base64,/', '', $request->signature_data);
            $raw = base64_decode($raw);
            Storage::disk('private')->put('signatures/' . $name, SignatureImageProcessor::removeBackground($raw));
            return 'signatures/' . $name;
        }

        $request->validate(['signature_file' => 'required|file|mimes:jpg,jpeg,png|max:2048']);
        $file = $request->file('signature_file');

        // Validasi MIME aktual
        $finfo    = new \finfo(FILEINFO_MIME_TYPE);
        $realMime = $finfo->file($file->getRealPath());
        abort_if(!in_array($realMime, ['image/jpeg', 'image/png']), 422, 'Format file tidak valid.');

        $raw = SignatureImageProcessor::removeBackground(file_get_contents($file->getRealPath()));
        Storage::disk('private')->put('signatures/' . $name, $raw);
        return 'signatures/' . $name;
    }
}
