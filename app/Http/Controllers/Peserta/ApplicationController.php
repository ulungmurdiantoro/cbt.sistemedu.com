<?php

namespace App\Http\Controllers\Peserta;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\SaveApplicationFormRequest;
use App\Http\Requests\StoreSkemaRequest;
use App\Mail\ApplicationSubmittedMail;
use App\Models\AssessmentApplication;
use App\Models\ExamSession;
use App\Models\Participant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ApplicationController extends Controller
{
    // Step 1: tampilkan sesi aktif yang tersedia, dikelompokkan per skema
    public function chooseSkema()
    {
        $enrolledSessionIds = AssessmentApplication::where('participant_id', auth()->guard('participant')->id())
            ->pluck('exam_session_id');

        $activeSessions = ExamSession::with('exam.classroom')
            ->active()
            ->get();

        // group by classroom_id, hasilkan 1 entry per skema dengan list sesinya
        $grouped = $activeSessions
            ->groupBy(fn($s) => $s->exam->classroom_id)
            ->map(function ($sessions) use ($enrolledSessionIds) {
                $classroom = $sessions->first()->exam->classroom;
                return [
                    'classroom_id' => $classroom->id,
                    'kode_skema'   => $classroom->kode_skema,
                    'title'        => $classroom->title,
                    'sessions'     => $sessions->map(fn($s) => [
                        'id'              => $s->id,
                        'title'           => $s->title,
                        'start_time'      => $s->start_time,
                        'end_time'        => $s->end_time,
                        'konteks_asesmen' => $s->konteks_asesmen,
                        'tempat_ujian'    => $s->tempat_ujian,
                        'kode_batch'      => $s->kode_batch,
                        'enrolled'        => $enrolledSessionIds->contains($s->id),
                    ])->values(),
                ];
            })
            ->values();

        return inertia('Peserta/Application/ChooseSkema', [
            'skema_list' => $grouped,
        ]);
    }

    // Step 1: simpan pilihan skema
    public function storeSkema(StoreSkemaRequest $request)
    {

        $participantId = auth()->guard('participant')->id();

        // cegah duplikat
        $exists = AssessmentApplication::where('participant_id', $participantId)
            ->where('exam_session_id', $request->exam_session_id)
            ->exists();

        if ($exists) {
            return back()->with('error', 'Anda sudah mendaftar untuk sesi ujian ini.');
        }

        $session = ExamSession::with('exam')->findOrFail($request->exam_session_id);

        $application = AssessmentApplication::create([
            'code'            => 'APL-' . strtoupper(Str::random(8)),
            'participant_id'  => $participantId,
            'classroom_id'    => $session->exam->classroom_id,
            'exam_session_id' => $session->id,
            // diambil dari sesi yang ditetapkan admin
            'konteks_asesmen' => $session->konteks_asesmen,
            'tempat_ujian'    => $session->tempat_ujian,
            'kode_batch'      => $session->kode_batch ?? '-',
            'tujuan_asesmen'  => $request->tujuan_asesmen,
            'status'          => ApplicationStatus::Draft,
        ]);

        return redirect()->route('peserta.application.form', $application->id)
            ->with('success', 'Skema berhasil dipilih. Lengkapi formulir FR.APL.01.');
    }

    // Step 2: form FR.APL.01
    public function showForm(AssessmentApplication $application)
    {
        $this->authorizeApplication($application);

        $application->load(['classroom', 'examSession.exam']);
        $participant = auth()->guard('participant')->user();

        return inertia('Peserta/Application/Form', [
            'application' => $application,
            'participant' => $participant,
        ]);
    }

    // Step 2: simpan form FR.APL.01
    public function saveForm(SaveApplicationFormRequest $request, AssessmentApplication $application)
    {
        $this->authorizeApplication($application);

        // simpan ke tabel participants (profile peserta)
        /** @var Participant $participant */
        $participant = auth()->guard('participant')->user();
        $participant->update($request->only([
            'name', 'nik', 'tempat_lahir', 'tanggal_lahir', 'jenis_kelamin', 'kebangsaan',
            'alamat_rumah', 'kode_pos_rumah', 'telp_rumah', 'hp', 'email_alt',
            'kualifikasi_pendidikan', 'institusi', 'jabatan', 'alamat_kantor',
            'kode_pos_kantor', 'telp_kantor', 'fax_kantor', 'email_kantor',
        ]));

        return redirect()->route('peserta.application.pakta', $application->id)
            ->with('success', 'Data berhasil disimpan. Silakan baca dan tandatangani pakta integritas.');
    }

    // Step 2b: simpan TTD formulir FR.APL.01
    public function saveFormSignature(Request $request, AssessmentApplication $application)
    {
        $this->authorizeApplication($application);
        abort_if(!$application->isDraft(), 403, 'Permohonan sudah disubmit.');

        $path = $this->storeSignature($request, 'ttd_form_' . $application->id);

        if ($application->signature_form_path) {
            Storage::disk('private')->delete($application->signature_form_path);
        }

        $application->update(['signature_form_path' => $path]);

        return back()->with('success', 'Tanda tangan formulir berhasil disimpan.');
    }

    // Step 3: pakta integritas (FR.AK.01)
    public function showPakta(AssessmentApplication $application)
    {
        $this->authorizeApplication($application);
        $application->load(['classroom', 'examSession']);
        $participant = auth()->guard('participant')->user();

        return inertia('Peserta/Application/PaktaIntegritas', [
            'application' => $application,
            'participant' => $participant,
        ]);
    }

    public function savePakta(Request $request, AssessmentApplication $application)
    {
        $this->authorizeApplication($application);
        abort_if(!$application->isDraft(), 403, 'Permohonan sudah disubmit.');

        $path = $this->storeSignature($request, 'ttd_' . $application->id);

        if ($application->signature_path) {
            Storage::disk('private')->delete($application->signature_path);
        }

        $application->update([
            'signature_path'  => $path,
            'pakta_signed_at' => now(),
        ]);

        return redirect()->route('peserta.application.documents', $application->id)
            ->with('success', 'Pakta integritas berhasil ditandatangani. Silakan upload dokumen persyaratan.');
    }

    // Sajikan file tanda tangan secara privat (hanya pemilik permohonan)
    public function serveSignature(AssessmentApplication $application, string $type)
    {
        $this->authorizeApplication($application);

        $path = match ($type) {
            'form'  => $application->signature_form_path,
            'pakta' => $application->signature_path,
            default => null,
        };

        abort_if(!$path || !Storage::disk('private')->exists($path), 404);

        return response()->file(Storage::disk('private')->path($path));
    }

    // Step 3b: revisi permohonan yang ditolak (reset ke draft)
    public function revisi(AssessmentApplication $application)
    {
        $this->authorizeApplication($application);

        abort_if(!$application->isRejected(), 403, 'Hanya permohonan berstatus ditolak yang dapat direvisi.');

        $application->update([
            'status'       => ApplicationStatus::Draft,
            'submitted_at' => null,
        ]);

        return redirect()->route('peserta.application.form', $application->id)
            ->with('success', 'Permohonan dikembalikan ke draft. Perbaiki dan submit ulang.');
    }

    // Step 4: submit permohonan
    public function submit(AssessmentApplication $application)
    {
        $this->authorizeApplication($application);

        if (!$application->isDraft()) {
            return back()->with('error', 'Permohonan sudah disubmit.');
        }

        $participant = auth()->guard('participant')->user();

        // validasi data pribadi & pekerjaan wajib sudah terisi
        $requiredFields = ['nik', 'tempat_lahir', 'tanggal_lahir', 'jenis_kelamin', 'hp', 'kualifikasi_pendidikan', 'institusi', 'jabatan'];
        foreach ($requiredFields as $field) {
            if (empty($participant->$field)) {
                return back()->with('error', 'Lengkapi data pribadi dan pekerjaan terlebih dahulu.');
            }
        }

        // validasi semua dokumen wajib sudah diupload
        $requiredDocs = $application->classroom->documentRequirements()->where('is_required', true)->pluck('id');
        $uploadedDocs = $application->documents()->pluck('classroom_document_requirement_id');
        $missing = $requiredDocs->diff($uploadedDocs);

        if ($missing->isNotEmpty()) {
            return back()->with('error', 'Masih ada dokumen wajib yang belum diupload.');
        }

        if (empty($application->signature_form_path)) {
            return back()->with('error', 'Tanda tangan formulir FR.APL.01 belum disimpan.');
        }

        if (empty($application->pakta_signed_at)) {
            return back()->with('error', 'Pakta integritas belum ditandatangani. Kembali ke halaman FR.AK.01 terlebih dahulu.');
        }

        // snapshot data untuk audit trail
        $application->update([
            'status'           => ApplicationStatus::Submitted,
            'submitted_at'     => now(),
            'snapshot_pribadi' => [
                'name'                   => $participant->name,
                'nik'                    => $participant->nik,
                'tempat_lahir'           => $participant->tempat_lahir,
                'tanggal_lahir'          => $participant->tanggal_lahir?->format('Y-m-d'),
                'jenis_kelamin'          => $participant->jenis_kelamin,
                'kebangsaan'             => $participant->kebangsaan,
                'alamat_rumah'           => $participant->alamat_rumah,
                'kode_pos_rumah'         => $participant->kode_pos_rumah,
                'telp_rumah'             => $participant->telp_rumah,
                'hp'                     => $participant->hp,
                'email'                  => $participant->email,
                'email_alt'              => $participant->email_alt,
                'kualifikasi_pendidikan' => $participant->kualifikasi_pendidikan,
            ],
            'snapshot_pekerjaan' => [
                'institusi'       => $participant->institusi,
                'jabatan'         => $participant->jabatan,
                'alamat_kantor'   => $participant->alamat_kantor,
                'kode_pos_kantor' => $participant->kode_pos_kantor,
                'telp_kantor'     => $participant->telp_kantor,
                'fax_kantor'      => $participant->fax_kantor,
                'email_kantor'    => $participant->email_kantor,
            ],
        ]);

        try {
            $application->load(['participant', 'classroom', 'examSession']);
            Mail::to($participant->email)->send(new ApplicationSubmittedMail($application));
        } catch (\Exception) {
            // email gagal, tidak menghentikan alur aplikasi
        }

        return redirect()->route('peserta.dashboard')
            ->with('success', 'Permohonan berhasil disubmit. Menunggu verifikasi admin.');
    }

    // Simpan tanda tangan ke private disk, kembalikan path-nya
    private function storeSignature(Request $request, string $prefix): string
    {
        if ($request->filled('signature_data')) {
            $request->validate(['signature_data' => 'required|string']);
            $raw  = preg_replace('/^data:image\/\w+;base64,/', '', $request->signature_data);
            $raw  = base64_decode($raw);
            $name = $prefix . '_' . time() . '.png';
            Storage::disk('private')->put('signatures/' . $name, $raw);
            return 'signatures/' . $name;
        }

        $request->validate(['signature_file' => 'required|file|mimes:jpg,jpeg,png|max:2048']);
        $file = $request->file('signature_file');

        // Validasi MIME aktual
        $finfo    = new \finfo(FILEINFO_MIME_TYPE);
        $realMime = $finfo->file($file->getRealPath());
        abort_if(!in_array($realMime, ['image/jpeg', 'image/png']), 422, 'Format file tidak valid.');

        $ext  = $file->getClientOriginalExtension();
        $name = $prefix . '_' . time() . '.' . $ext;
        return $file->storeAs('signatures', $name, 'private');
    }

    private function authorizeApplication(AssessmentApplication $application): void
    {
        abort_if(
            $application->participant_id !== auth()->guard('participant')->id(),
            403
        );
    }
}
