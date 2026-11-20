<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Jenis ujian "Ujian Praktik" → "Ujian Praktik/Keterampilan" (Exam::TYPE_PRAKTIK), termasuk judul ujiannya
// (mis. "… - Ujian Praktik V2" → "… - Ujian Praktik/Keterampilan V2"). Judul yang sudah memuat "Keterampilan" dibiarkan.
return new class extends Migration
{
    private const LAMA = 'Ujian Praktik';
    private const BARU = 'Ujian Praktik/Keterampilan';

    public function up(): void
    {
        DB::table('exams')->where('type', self::LAMA)->where('title', 'like', '%' . self::LAMA . '%')
            ->where('title', 'not like', '%Keterampilan%')
            ->update(['title' => DB::raw("REPLACE(title, '" . self::LAMA . "', '" . self::BARU . "')")]);
        DB::table('exams')->where('type', self::LAMA)->update(['type' => self::BARU]);
    }

    public function down(): void
    {
        DB::table('exams')->where('type', self::BARU)
            ->update(['title' => DB::raw("REPLACE(title, '" . self::BARU . "', '" . self::LAMA . "')")]);
        DB::table('exams')->where('type', self::BARU)->update(['type' => self::LAMA]);
    }
};
