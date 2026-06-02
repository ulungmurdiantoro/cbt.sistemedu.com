<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CertificateTemplate;
use App\Models\Classroom;
use App\Models\ExamSession;
use App\Models\ParticipantResult;
use App\Models\Student;
use App\Services\DocumentGeneratorService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CertificateTemplateController extends Controller
{
    public function show()
    {
        $template = CertificateTemplate::first();
        return inertia('Admin/CertificateTemplate/Show', ['template' => $template]);
    }

    public function preview(string $type, DocumentGeneratorService $generator)
    {
        abort_if(!in_array($type, ['sk', 'sertifikat']), 404);

        // Cari result finalized yang sudah ada
        $result = ParticipantResult::where('is_finalized', true)
            ->when($type === 'sertifikat', fn($q) => $q->where('keputusan', 'LULUS'))
            ->with(['student', 'examSession.examPg.classroom', 'examSession.examEsai.classroom'])
            ->latest()
            ->first();

        // Jika belum ada, buat dummy object (tidak disimpan ke DB)
        if (!$result) {
            $result = $this->makeDummyResult($type);
        }

        $pdf      = $type === 'sk'
            ? $generator->generateSk($result, 'with_kop')
            : $generator->generateSertifikat($result, 'with_kop');

        $filename = $type === 'sk' ? 'Preview_SK.pdf' : 'Preview_Sertifikat.pdf';

        return response($pdf, 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', "inline; filename=\"{$filename}\"");
    }

    private function makeDummyResult(string $type): ParticipantResult
    {
        // Dummy ExamSession
        $session = new ExamSession();
        $session->id            = 0;
        $session->title         = 'Lead Implementer ISO 17025:2017';
        $session->start_time    = Carbon::now()->subDays(3);
        $session->end_time      = Carbon::now()->subDay();
        $session->tempat_ujian  = 'Online (Zoom Meeting)';
        $session->konteks_asesmen = 'Sertifikasi Person';
        $session->kode_batch    = 'BATCH-001';

        // Dummy Classroom
        $classroom = new Classroom();
        $classroom->title       = 'Lead Implementer Standar Laboratorium ISO IEC 17025 2017';
        $classroom->kode_skema  = 'LSP-EGC-001';

        // Dummy Exam (PG)
        $examPg = new \App\Models\Exam();
        $examPg->id          = 0;
        $examPg->title       = 'Soal PG ISO 17025';
        $examPg->type        = 'Pilihan Ganda';
        $examPg->classroom_id = 0;
        $examPg->setRelation('classroom', $classroom);
        $session->setRelation('examPg', $examPg);
        $session->setRelation('examEsai', null);

        // Dummy Student
        $student = new Student();
        $student->id             = 0;
        $student->name           = 'Dr. Budi Santoso, M.Sc.';
        $student->no_participant = 'LSP-PREVIEW-001';

        // Dummy Result
        $result = new ParticipantResult();
        $result->id               = 0;
        $result->exam_session_id  = 0;
        $result->student_id       = 0;
        $result->nilai_pg         = 85.0;
        $result->nilai_esai       = 80.0;
        $result->nilai_wawancara  = 22.5;
        $result->nilai_akhir      = 82.5;
        $result->keputusan        = 'LULUS';
        $result->is_finalized     = true;
        $result->finalized_at     = Carbon::now();
        $result->valid_until      = Carbon::now()->addYears(3);
        $result->sk_number        = 'PREVIEW/SK-SP/LSP-EDUKIA/VI/2026';
        $result->sertifikat_number = $type === 'sertifikat' ? 'PREVIEW-001-01-2026-00001' : null;
        $result->attempt          = 1;
        $result->setRelation('student', $student);
        $result->setRelation('examSession', $session);

        return $result;
    }

    public function save(Request $request)
    {
        $request->validate([
            'kop'                   => 'nullable|image|max:2048',
            'kop_logo2'             => 'nullable|image|max:2048',
            'ttd'                   => 'nullable|image|max:2048',
            'bg_sertifikat'         => 'nullable|image|max:5120',
            'sk_body'               => 'nullable|string',
            'nama_penandatangan'    => 'nullable|string|max:255',
            'jabatan_penandatangan' => 'nullable|string|max:255',
            'kota'                  => 'nullable|string|max:100',
        ]);

        $template = CertificateTemplate::firstOrNew([]);
        $data = [];

        foreach (['kop', 'ttd', 'bg_sertifikat'] as $field) {
            if ($request->hasFile($field)) {
                $old = $template->{$field . '_path'};
                if ($old) Storage::disk('public')->delete($old);
                $data[$field . '_path'] = $request->file($field)->store('templates', 'public');
            }
        }

        // Logo2 (IAF/KAN) stored as kop_logo2_path
        if ($request->hasFile('kop_logo2')) {
            $old = $template->kop_logo2_path;
            if ($old) Storage::disk('public')->delete($old);
            $data['kop_logo2_path'] = $request->file('kop_logo2')->store('templates', 'public');
        }

        $data['sk_body']               = $request->sk_body;
        $data['nama_penandatangan']    = $request->nama_penandatangan;
        $data['jabatan_penandatangan'] = $request->jabatan_penandatangan;
        $data['kota']                  = $request->kota ?? 'Semarang';

        $template->fill($data)->save();

        return redirect()->back()->with('success', 'Template berhasil disimpan.');
    }
}
