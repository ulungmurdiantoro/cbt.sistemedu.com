<?php

namespace App\Http\Controllers\Peserta;

use App\Http\Controllers\Controller;
use App\Models\AssessmentApplication;
use App\Services\MidtransService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

// Materai elektronik FR.AK.01 — khusus bagian tanda tangan peserta (asesi).
// Alur: peserta bayar via Midtrans -> notifikasi server-to-server masuk ->
// status jadi "paid" -> (menyusul) dibubuhkan lewat API Peruri e-Meterai.
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
            'application'    => $application->only([
                'id', 'code', 'materai_status', 'materai_amount',
                'materai_paid_at', 'materai_stamped_at', 'materai_failure_reason',
            ]),
            'client_key'     => config('materai.midtrans.client_key'),
            'is_production'  => (bool) config('materai.midtrans.is_production'),
            'price'          => config('materai.price'),
        ]);
    }

    public function pay(AssessmentApplication $application, MidtransService $midtrans)
    {
        $this->authorizeApplication($application);
        abort_if(!$application->pakta_signed_at, 422, 'Tanda tangani pakta integritas terlebih dahulu.');
        abort_if(in_array($application->materai_status, ['paid', 'stamped']), 422, 'Materai sudah dibayar.');

        $participant = auth()->guard('participant')->user();
        $amount      = (float) config('materai.price');
        $orderId     = 'MTR-' . $application->id . '-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(4));

        $snapToken = $midtrans->createSnapToken(
            $orderId,
            $amount,
            [
                'first_name' => $participant->name,
                'email'      => $participant->email,
                'phone'      => $participant->hp,
            ],
            [[
                'id'       => 'materai-fr-ak-01',
                'price'    => (int) $amount,
                'quantity' => 1,
                'name'     => 'Materai Elektronik FR.AK.01',
            ]]
        );

        $application->update([
            'materai_status'   => 'pending_payment',
            'materai_order_id' => $orderId,
            'materai_amount'   => $amount,
        ]);

        return response()->json(['snap_token' => $snapToken]);
    }

    // Webhook server-to-server dari Midtrans — tanpa auth peserta & tanpa CSRF
    // (lihat pengecualian di bootstrap/app.php).
    public function notification(Request $request, MidtransService $midtrans)
    {
        $notification = $midtrans->readNotification();

        $application = AssessmentApplication::where('materai_order_id', $notification->order_id)->first();
        abort_if(!$application, 404);

        $status = $notification->transaction_status;
        $fraud  = $notification->fraud_status ?? null;

        if (in_array($status, ['capture', 'settlement']) && ($fraud === null || $fraud === 'accept')) {
            if ($application->materai_status !== 'paid' && $application->materai_status !== 'stamped') {
                $application->update([
                    'materai_status'  => 'paid',
                    'materai_paid_at' => now(),
                ]);

                // TODO: setelah kredensial Peruri e-Meterai tersedia, panggil
                // API pembubuhan di sini dan set materai_status = 'stamped'
                // + materai_stamped_at + materai_document_path.
            }
        } elseif (in_array($status, ['deny', 'cancel', 'expire'])) {
            $application->update([
                'materai_status'          => 'failed',
                'materai_failure_reason'  => $status,
            ]);
        }

        return response()->json(['status' => 'ok']);
    }
}
