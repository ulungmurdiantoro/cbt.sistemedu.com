<?php

namespace App\Http\Controllers\Asesor;

use App\Http\Controllers\Controller;
use App\Models\AsesorCv;
use App\Services\DocumentGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

// CV Asesor — diisi & diperbarui sendiri oleh asesor lewat portalnya,
// diterbitkan sebagai PDF resmi LSP. Lihat docs plan "CV Asesor" untuk konteks.
class CvController extends Controller
{
    /**
     * Bagian CV yang tiap barisnya boleh punya bukti dokumen. Bukti disimpan di baris
     * JSON-nya sendiri: `bukti => [id, path, name]`. Browser hanya menerima id + nama
     * (lihat rowsForForm) dan mengirim balik `bukti_id`, jadi path tidak bisa diarahkan
     * ke file lain di disk private.
     */
    private const BUKTI_SECTIONS = [
        'pendidikan_formal', 'pelatihan', 'pengalaman_kerja', 'pengalaman_profesional', 'sertifikasi_kompetensi',
    ];

    private const BUKTI_EXTENSIONS = 'pdf,jpg,jpeg,png';

    private const BUKTI_MAX_KB = 5120;

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
                'pendidikan_formal'      => $this->rowsForForm($cv?->pendidikan_formal),
                'pelatihan'              => $this->rowsForForm($cv?->pelatihan),
                'pengalaman_kerja'       => $this->rowsForForm($cv?->pengalaman_kerja),
                'keahlian'               => $cv->keahlian ?? [],
                'pengalaman_profesional' => $this->rowsForForm($cv?->pengalaman_profesional),
                'sertifikasi_kompetensi' => $this->rowsForForm($cv?->sertifikasi_kompetensi),
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

            ...$this->buktiRules(),
        ], [
            '*.*.bukti_file.max'        => 'Ukuran bukti dokumen maksimal ' . (self::BUKTI_MAX_KB / 1024) . ' MB.',
            '*.*.bukti_file.mimes'      => 'Bukti dokumen harus berformat PDF, JPG atau PNG.',
            '*.*.bukti_file.extensions' => 'Bukti dokumen harus berformat PDF, JPG atau PNG.',
        ]);

        $data = $request->only([
            'tempat_tanggal_lahir', 'jenis_kelamin', 'alamat_rumah',
            'nama_institusi', 'alamat_institusi', 'no_handphone',
        ]);

        // Buang baris yang sama sekali kosong (semua field blank / string kosong)
        // supaya tabel PDF tidak dipenuhi baris hampa.
        $notAllBlank = fn($row) => is_array($row) && collect($row)->contains(fn($v) => filled($v));

        // Bukti lama yang masih dipakai dicari lewat id-nya; file yang baru diunggah
        // disimpan dulu, file yang tidak lagi dirujuk baru dihapus setelah CV tersimpan.
        $oldBukti = $this->buktiById($user->cv()->first());

        foreach (self::BUKTI_SECTIONS as $section) {
            $rows = [];
            foreach ($request->input($section, []) as $i => $row) {
                if (! is_array($row)) {
                    continue;
                }

                $file   = $request->file("{$section}.{$i}.bukti_file");
                $keepId = $row['bukti_id'] ?? null;
                unset($row['bukti_id'], $row['bukti_file'], $row['bukti']);

                $row['bukti'] = match (true) {
                    $file !== null                => $this->storeBukti($user->id, $section, $file),
                    isset($oldBukti[$keepId])     => $oldBukti[$keepId],
                    default                       => null,
                };

                $rows[] = $row;
            }

            $data[$section] = array_values(array_filter($rows, $notAllBlank));
        }

        $data['keahlian'] = array_values(array_filter($request->input('keahlian', []), fn($v) => filled($v)));

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

        $cv = AsesorCv::updateOrCreate(['user_id' => $user->id], $data);

        $stillUsed = $this->buktiById($cv);
        foreach (array_diff_key($oldBukti, $stillUsed) as $bukti) {
            Storage::disk('private')->delete($bukti['path']);
        }

        return back()->with('success', 'CV berhasil disimpan.');
    }

    public function serveBukti(string $id)
    {
        $bukti = $this->buktiById(auth()->user()->cv()->first())[$id] ?? null;
        abort_if(! $bukti || ! Storage::disk('private')->exists($bukti['path']), 404);

        $ext  = strtolower(pathinfo($bukti['path'], PATHINFO_EXTENSION));
        $mime = $ext === 'pdf' ? 'application/pdf' : ($ext === 'png' ? 'image/png' : 'image/jpeg');

        return Storage::disk('private')->response($bukti['path'], $bukti['name'], [
            'Content-Type'           => $mime,
            'Cache-Control'          => 'no-store, private',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function buktiRules(): array
    {
        $rules = [];
        foreach (self::BUKTI_SECTIONS as $section) {
            $rules["{$section}.*.bukti_id"]   = 'nullable|string|max:40';
            $rules["{$section}.*.bukti_file"] = [
                'nullable', 'file', 'max:' . self::BUKTI_MAX_KB,
                'mimes:' . self::BUKTI_EXTENSIONS, 'extensions:' . self::BUKTI_EXTENSIONS,
            ];
        }

        return $rules;
    }

    private function storeBukti(int $userId, string $section, UploadedFile $file): array
    {
        $id   = (string) Str::ulid();
        $ext  = strtolower($file->getClientOriginalExtension());
        $path = $file->storeAs("asesor-cv/{$userId}/bukti", "{$section}-{$id}.{$ext}", 'private');

        return ['id' => $id, 'path' => $path, 'name' => $file->getClientOriginalName()];
    }

    /** Semua bukti di CV, dikunci id. */
    private function buktiById(?AsesorCv $cv): array
    {
        return collect(self::BUKTI_SECTIONS)
            ->flatMap(fn ($section) => $cv?->{$section} ?? [])
            ->pluck('bukti')
            ->filter(fn ($bukti) => isset($bukti['id'], $bukti['path']))
            ->keyBy('id')
            ->all();
    }

    /** Baris untuk form: bukti hanya id + nama, path tidak dikirim ke browser. */
    private function rowsForForm(?array $rows): array
    {
        return array_map(function ($row) {
            $bukti        = $row['bukti'] ?? null;
            $row['bukti'] = isset($bukti['id']) ? ['id' => $bukti['id'], 'name' => $bukti['name']] : null;

            return $row;
        }, $rows ?? []);
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
