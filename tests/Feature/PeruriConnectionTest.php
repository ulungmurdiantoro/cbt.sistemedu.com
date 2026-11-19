<?php

namespace Tests\Feature;

use App\Exceptions\PeruriStampingException;
use App\Services\PeruriService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Tests\TestCase;

/** Koneksi ke Peruri putus (mis. cURL 56): login & cek saldo diulang, Generate SN tidak; pesannya terbaca admin. */
class PeruriConnectionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Sleep::fake();
        Cache::flush();
        config([
            'materai.enabled'                => true,
            'materai.peruri.fake'            => false,
            'materai.peruri.login_url'       => 'https://backendservice.example.test/api/users/login',
            'materai.peruri.generate_sn_url' => 'https://stampv2.example.test/chanel/stampv2',
        ]);
    }

    private function reset(): ConnectionException
    {
        return new ConnectionException('cURL error 56: Recv failure: Connection reset by peer');
    }

    public function test_saldo_retries_after_connection_reset(): void
    {
        $saldoCalls = 0;
        Http::fake(function (Request $request) use (&$saldoCalls) {
            if (str_ends_with($request->url(), '/api/users/login')) {
                return Http::response(['statusCode' => '00', 'token' => 'tok']);
            }
            if (++$saldoCalls < 3) {
                throw $this->reset();   // dua kali putus, ketiga berhasil
            }

            return Http::response(['result' => ['status' => '00', 'saldo' => 77, 'notstamp' => 0]]);
        });

        $this->assertSame(['saldo' => 77, 'notstamp' => 0], app(PeruriService::class)->saldo());
        $this->assertSame(3, $saldoCalls);
    }

    public function test_persistent_reset_gives_readable_message(): void
    {
        $calls = 0;
        Http::fake(function () use (&$calls) {
            $calls++;
            throw $this->reset();
        });

        try {
            app(PeruriService::class)->saldo();
            $this->fail('Seharusnya gagal');
        } catch (PeruriStampingException $e) {
            $this->assertStringContainsString('Login Peruri gagal: tidak dapat terhubung ke backendservice.example.test', $e->getMessage());
            $this->assertStringContainsString('IP server terdaftar di Peruri', $e->getMessage());
            $this->assertInstanceOf(ConnectionException::class, $e->getPrevious());
        }
        $this->assertSame(PeruriService::RETRY_TIMES, $calls);   // login dicoba ulang, lalu menyerah
    }

    public function test_generate_serial_number_is_not_retried(): void
    {
        $snCalls = 0;
        Http::fake(function (Request $request) use (&$snCalls) {
            if (str_ends_with($request->url(), '/api/users/login')) {
                return Http::response(['statusCode' => '00', 'token' => 'tok']);
            }
            $snCalls++;
            throw $this->reset();
        });

        try {
            app(PeruriService::class)->generateSerialNumber(['tgldoc' => '2026-11-19', 'namafile' => 'x.pdf']);
            $this->fail('Seharusnya gagal');
        } catch (PeruriStampingException $e) {
            $this->assertStringContainsString('Generate Serial Number Peruri gagal: tidak dapat terhubung ke stampv2.example.test', $e->getMessage());
            $this->assertStringContainsString('saldo bisa sudah terpakai', $e->getMessage());
        }
        $this->assertSame(1, $snCalls);
    }
}
