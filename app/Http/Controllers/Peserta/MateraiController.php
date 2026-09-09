<?php

namespace App\Http\Controllers\Peserta;

use App\Http\Controllers\Controller;
use App\Jobs\StampFrAk01Job;
use App\Models\AssessmentApplication;

// Materai elektronik FR.AK.01 — gratis untuk peserta, dibubuhkan otomatis
// hanya setelah ketiga tanda tangan lengkap: Asesi (savePakta), LSP/Admin
// (ApplicationController::approve), dan Asesor (PenilaianDokumenController::
// finalVerify / TtdAk01Controller::sign) — lihat AssessmentApplication::
// maybeTriggerAk01Stamping(), dipanggil dari ketiga titik tersebut. Controller
// ini murni untuk menampilkan status & memicu ulang kalau gagal.
//
// Catatan: kolom materai_status memakai enum lama (peninggalan alur bayar
// Midtrans yang sudah dihapus) — nilai 'pending_payment'/'paid' sekarang
// dipakai ulang untuk berarti "sedang diproses", bukan berarti pembayaran.
// Status 'none' berarti belum ketiga tanda tangan lengkap — bisa berlangsung
// lama (menunggu approval & penugasan asesor), bukan berarti macet.
class MateraiController extends Controller
{
    private function authorizeApplication(AssessmentApplication $application): void
    {
        abort_if(
            $application->participant_id !== auth()->guard('participant')->id(),
            403
        );
    }

    public function show(AssessmentApplication $application)
    {
        $this->authorizeApplication($application);
        abort_if(!$application->pakta_signed_at, 422, 'Tanda tangani pakta integritas terlebih dahulu.');

        return inertia('Peserta/Application/Materai', [
            'application' => $application->only([
                'id', 'code', 'materai_status', 'materai_stamped_at', 'materai_failure_reason',
            ]),
        ]);
    }

    public function retry(AssessmentApplication $application)
    {
        $this->authorizeApplication($application);
        abort_unless($application->materai_status === 'failed', 422, 'Materai tidak dalam status gagal.');

        $application->update(['materai_status' => 'pending_payment', 'materai_failure_reason' => null]);
        StampFrAk01Job::dispatch($application->id);

        return back()->with('success', 'Pembubuhan materai dicoba ulang.');
    }
}
