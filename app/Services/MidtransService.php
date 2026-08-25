<?php

namespace App\Services;

use Midtrans\Config;
use Midtrans\Notification;
use Midtrans\Snap;

class MidtransService
{
    public function __construct()
    {
        Config::$serverKey    = config('materai.midtrans.server_key');
        Config::$isProduction = (bool) config('materai.midtrans.is_production');
        Config::$isSanitized  = (bool) config('materai.midtrans.is_sanitized');
        Config::$is3ds        = (bool) config('materai.midtrans.is_3ds', true);
    }

    /**
     * Buat transaksi Snap & kembalikan snap token untuk dipakai di frontend
     * (window.snap.pay(token, ...)).
     */
    public function createSnapToken(string $orderId, float $amount, array $customerDetails, array $itemDetails): string
    {
        $params = [
            'transaction_details' => [
                'order_id'     => $orderId,
                'gross_amount' => (int) $amount,
            ],
            'customer_details' => $customerDetails,
            'item_details'     => $itemDetails,
        ];

        return Snap::getSnapToken($params);
    }

    /**
     * Baca & verifikasi notifikasi server-to-server dari Midtrans.
     * Config::$serverKey (di-set di constructor) dipakai Notification untuk
     * verifikasi signature key secara internal.
     */
    public function readNotification(): Notification
    {
        return new Notification();
    }
}
