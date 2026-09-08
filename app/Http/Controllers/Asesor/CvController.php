<?php

namespace App\Http\Controllers\Asesor;

use App\Http\Controllers\Controller;
use App\Models\AsesorCv;
use App\Services\DocumentGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

// CV Asesor — diisi & diperbarui sendiri oleh asesor lewat portalnya,
// diterbitkan sebagai PDF resmi LSP. Lihat docs plan "CV Asesor" untuk konteks.
class CvController extends Controller
{
    public function show()
    {
        $user = auth()->user();
        $cv   = $user->cv;

        return inertia('Asesor/Cv/Edit', [
            'cv' => [
                'tempat_tanggal_lahir'   => $cv->tempat_tanggal_lahir ?? '',
                'jenis_kelamin'          => $cv->jenis_kelamin ?? '',
                'alamat_rumah'           => $cv->alamat_rumah ?? '',
                'nama_institusi'         => $cv->nama_institusi ?? '',
                'alamat_institusi'       => $cv->alamat_institusi ?? '',
                'no_handphone'           => $cv->no_handphone ?? '',
                'pendidikan_formal'      => $cv->pendidikan_formal ?? [],
                'pelatihan'              => $cv->pelatihan ?? [],
                'pengalaman_kerja'       => $cv->pengalaman_kerja ?? [],
                'keahlian'               => $cv->keahlian ?? [],
                'pengalaman_profesional' => $cv->pengalaman_profesional ?? [],
                'sertifikasi_kompetensi' => $cv->sertifikasi_kompetensi ?? [],
                'photo_url'              => ($cv && $cv->photo_path) ? route('asesor.cv.photo') : null,
                'updated_at'             => $cv?->updated_at,
            ],
        ]);
    }

    public function save(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'tempat_tanggal_lahir' => 'nullable|string|max:255',
            'jenis_kelamin'        => 'nullable|string|max:50',
            'alamat_rumah'         => 'nullable|string',
            'nama_institusi'       => 'nullable|string|max:255',
            'alamat_institusi'     => 'nullable|string',
            'no_handphone'         => 'nullable|string|max:50',
            'photo_file'           => 'nullable|image|mimes:jpg,jpeg,png|max:2048',

            'pendidikan_formal'                 => 'nullable|array',
            'pendidikan_formal.*.jenjang'        => 'nullable|string|max:255',
            'pendidikan_formal.*.sekolah'        => 'nullable|string|max:255',
            'pendidikan_formal.*.bidang_ilmu'    => 'nullable|string|max:255',
            'pendidikan_formal.*.tahun_lulus'    => 'nullable|string|max:50',

            'pelatihan'                     => 'nullable|array',
            'pelatihan.*.judul_kegiatan'    => 'nullable|string|max:255',
            'pelatihan.*.penyelenggara'     => 'nullable|string|max:255',
            'pelatihan.*.tahun'             => 'nullable|string|max:50',
            'pelatihan.*.lokasi'            => 'nullable|string|max:255',

            'pengalaman_kerja'               => 'nullable|array',
            'pengalaman_kerja.*.jabatan'     => 'nullable|string|max:255',
            'pengalaman_kerja.*.tahun'       => 'nullable|string|max:50',
            'pengalaman_kerja.*.perusahaan'  => 'nullable|string|max:255',
            'pengalaman_kerja.*.lokasi'      => 'nullable|string|max:255',

            'keahlian'   => 'nullable|array',
            'keahlian.*' => 'nullable|string|max:500',

            'pengalaman_profesional'                 => 'nullable|array',
            'pengalaman_profesional.*.pengalaman'    => 'nullable|string|max:1000',
            'pengalaman_profesional.*.penyelenggara' => 'nullable|string|max:255',
            'pengalaman_profesional.*.tahun'         => 'nullable|string|max:50',

            'sertifikasi_kompetensi'                     => 'nullable|array',
            'sertifikasi_kompetensi.*.jenis_sertifikasi' => 'nullable|string|max:255',
            'sertifikasi_kompetensi.*.bidang_ilmu'       => 'nullable|string|max:255',
            'sertifikasi_kompetensi.*.penyelenggara'     => 'nullable|string|max:255',
            'sertifikasi_kompetensi.*.tahun'             => 'nullable|string|max:50',
            'sertifikasi_kompetensi.*.masa_berlaku'      => 'nullable|string|max:50',
        ]);

        $data = $request->only([
            'tempat_tanggal_lahir', 'jenis_kelamin', 'alamat_rumah',
            'nama_institusi', 'alamat_institusi', 'no_handphone',
        ]);

        // Buang baris yang sama sekali kosong (semua field blank / string kosong)
        // supaya tabel PDF tidak dipenuhi baris hampa.
        $notAllBlank = fn($row) => is_array($row) && collect($row)->contains(fn($v) => filled($v));

        $data['pendidikan_formal']      = array_values(array_filter($request->input('pendidikan_formal', []), $notAllBlank));
        $data['pelatihan']              = array_values(array_filter($request->input('pelatihan', []), $notAllBlank));
        $data['pengalaman_kerja']       = array_values(array_filter($request->input('pengalaman_kerja', []), $notAllBlank));
        $data['pengalaman_profesional'] = array_values(array_filter($request->input('pengalaman_profesional', []), $notAllBlank));
        $data['sertifikasi_kompetensi'] = array_values(array_filter($request->input('sertifikasi_kompetensi', []), $notAllBlank));
        $data['keahlian']               = array_values(array_filter($request->input('keahlian', []), fn($v) => filled($v)));

        if ($request->hasFile('photo_file')) {
            $existing = $user->cv?->photo_path;
            if ($existing && Storage::disk('private')->exists($existing)) {
                Storage::disk('private')->delete($existing);
            }

            $ext  = $request->file('photo_file')->getClientOriginalExtension();
            $path = 'asesor-cv/' . $user->id . '/foto_' . now()->format('YmdHis') . '.' . $ext;
            Storage::disk('private')->put($path, file_get_contents($request->file('photo_file')->getRealPath()));
            $data['photo_path'] = $path;
        }

        AsesorCv::updateOrCreate(['user_id' => $user->id], $data);

        return back()->with('success', 'CV berhasil disimpan.');
    }

    public function downloadPdf(DocumentGeneratorService $generator)
    {
        $pdf = $generator->generateCvAsesor(auth()->user());

        return response($pdf, 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="CV_' . str_replace(' ', '_', auth()->user()->name) . '.pdf"');
    }

    public function servePhoto()
    {
        $path = auth()->user()->cv?->photo_path;
        abort_if(!$path || !Storage::disk('private')->exists($path), 404);

        return response()->file(Storage::disk('private')->path($path), [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma'        => 'no-cache',
        ]);
    }
}
