<?php

namespace App\Console\Commands;

use App\Services\PeruriService;
use Illuminate\Console\Command;

class PeruriSaldo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'peruri:saldo';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tampilkan sisa saldo (kuota) e-meterai akun Peruri yang dipakai .env (hanya membaca, tidak memakai saldo)';

    public function handle(PeruriService $peruri): int
    {
        if (config('materai.peruri.fake')) {
            $this->warn('PERURI_FAKE=true — tidak ada panggilan ke Peruri, jadi tidak ada saldo untuk ditampilkan.');

            return self::SUCCESS;
        }

        try {
            $saldo = $peruri->saldo();
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $host = parse_url((string) config('materai.peruri.login_url'), PHP_URL_HOST);

        $this->info("Saldo e-meterai: {$saldo['saldo']}   (notstamp: {$saldo['notstamp']})");
        $this->line("Akun: " . config('materai.peruri.username') . "  |  server: {$host}");

        return self::SUCCESS;
    }
}
