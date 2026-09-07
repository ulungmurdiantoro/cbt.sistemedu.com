<?php

namespace App\Services;

use App\Exceptions\PeruriStampingException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Integrasi Peruri e-Meterai — mode On-Premise.
 *
 * Login & Generate Serial Number tetap ke server Peruri; pembubuhan
 * (stamping) dilakukan LOKAL oleh container "Sign Adapter" yang di-deploy
 * sendiri (lihat docs/deploy-sign-adapter-peruri.md) — dokumen tidak pernah
 * diupload ke Peruri.
 */
class PeruriService
{
    private const CACHE_KEY = 'peruri_jwt_token';

    /**
     * Login ke Peruri, kembalikan token JWT. Di-cache ~23 jam (token asli
     * berlaku 24 jam) supaya tidak login ulang di setiap panggilan.
     */
    public function login(bool $forceFresh = false): string
    {
        if ($forceFresh) {
            Cache::forget(self::CACHE_KEY);
        }

        return Cache::remember(self::CACHE_KEY, now()->addHours(23), function () {
            $response = Http::timeout(30)->post(config('materai.peruri.login_url'), [
                'user'     => config('materai.peruri.username'),
                'password' => config('materai.peruri.password'),
            ]);

            $body = $response->json();

            if (!$response->ok() || ($body['statusCode'] ?? null) !== '00') {
                throw new PeruriStampingException(
                    'Login Peruri gagal: ' . ($body['message'] ?? $response->status())
                );
            }

            return $body['token'];
        });
    }

    /**
     * Minta serial number + QR untuk satu dokumen.
     *
     * @return array{sn: string, qrBase64: string}
     */
    public function generateSerialNumber(array $doc): array
    {
        if (config('materai.peruri.fake')) {
            // 1x1 PNG transparan sebagai QR dummy — cukup untuk rehearsal alur
            // tanpa kredensial/jaringan Peruri sungguhan.
            $dummyPng = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';

            return ['sn' => 'FAKE-' . strtoupper(uniqid()), 'qrBase64' => $dummyPng];
        }

        return $this->callGenerateSn($this->login(), $doc);
    }

    private function callGenerateSn(string $token, array $doc, bool $retried = false): array
    {
        $response = Http::timeout(30)->withToken($token)->post(config('materai.peruri.generate_sn_url'), [
            'isUpload' => false,
            'namadoc'  => $doc['namadoc'] ?? config('materai.peruri.namadoc'),
            'nodoc'    => $doc['nodoc'] ?? '0',
            'tgldoc'   => $doc['tgldoc'],
            'namafile' => $doc['namafile'],
            'snOnly'   => false,
        ]);

        $body = $response->json();

        // Token kadaluarsa/invalid — login ulang sekali lalu coba lagi.
        if (!$retried && ($body['statusCode'] ?? null) === '01') {
            return $this->callGenerateSn($this->login(forceFresh: true), $doc, retried: true);
        }

        if (!$response->ok() || ($body['statusCode'] ?? null) !== '00') {
            throw new PeruriStampingException(
                'Generate Serial Number Peruri gagal: ' . ($body['message'] ?? $response->status())
            );
        }

        return [
            'sn'       => $body['result']['sn'],
            'qrBase64' => $body['result']['Image'] ?? $body['result']['image'] ?? '',
        ];
    }

    /**
     * Bubuhkan e-meterai pada satu dokumen PDF lewat container Sign Adapter
     * lokal, kembalikan bytes PDF yang sudah distempel.
     *
     * $meta wajib berisi: refToken (sn), visLLX, visLLY, visURX, visURY,
     * visSignaturePage. Opsional: reason.
     */
    public function stamp(string $pdfBytes, string $qrBase64, array $meta): string
    {
        if (config('materai.peruri.fake')) {
            return $pdfBytes;
        }

        $share = rtrim(config('materai.peruri.sharefolder'), '/');
        foreach (['UNSIGNED', 'STAMP', 'SIGNED'] as $dir) {
            if (!is_dir("{$share}/{$dir}")) {
                mkdir("{$share}/{$dir}", 0775, true);
            }
        }

        $uuid    = (string) Str::uuid();
        $srcPath  = "{$share}/UNSIGNED/{$uuid}.pdf";
        $qrPath   = "{$share}/STAMP/{$uuid}.png";
        $destPath = "{$share}/SIGNED/{$uuid}.pdf";

        file_put_contents($srcPath, $pdfBytes);
        file_put_contents($qrPath, base64_decode($qrBase64));

        try {
            $token    = $this->login();
            $response = Http::timeout(60)->withToken($token)
                ->post(rtrim(config('materai.peruri.sign_adapter_url'), '/') . '/adapter/pdfsigning/rest/docSigningZ', [
                    'certificatelevel'  => 'NOT_CERTIFIED',
                    'dest'              => $destPath,
                    'docpass'           => '',
                    'jwToken'           => $token,
                    'location'          => config('materai.peruri.location'),
                    'profileName'       => config('materai.peruri.profile_name'),
                    'reason'            => $meta['reason'] ?? 'Persetujuan Dokumen',
                    'refToken'          => $meta['refToken'],
                    'spesimenPath'      => $qrPath,
                    'src'               => $srcPath,
                    'visLLX'            => $meta['visLLX'],
                    'visLLY'            => $meta['visLLY'],
                    'visURX'            => $meta['visURX'],
                    'visURY'            => $meta['visURY'],
                    'visSignaturePage'  => $meta['visSignaturePage'],
                ]);

            $body = $response->json();

            if (!$response->ok() || ($body['errorCode'] ?? null) !== '00') {
                throw new PeruriStampingException(
                    'Stamping Peruri gagal: ' . ($body['errorMessage'] ?? $response->status())
                );
            }

            if (!file_exists($destPath)) {
                throw new PeruriStampingException('Sign Adapter melaporkan sukses tapi file hasil tidak ditemukan di ' . $destPath);
            }

            return file_get_contents($destPath);
        } finally {
            @unlink($srcPath);
            @unlink($qrPath);
            @unlink($destPath);
        }
    }
}
