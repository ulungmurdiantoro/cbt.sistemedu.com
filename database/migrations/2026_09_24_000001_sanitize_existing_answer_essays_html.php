<?php

use App\Support\RichText;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Bersihkan jawaban esai yang tersimpan sebelum AnswerEssay::answer memakai cast
 * SanitizedHtml — jawaban lama bisa berisi script/event handler (XSS) yang dirender
 * v-html di halaman asesor & admin. Hanya baris yang berubah yang ditulis ulang;
 * updated_at sengaja tidak disentuh.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('answer_essays')
            ->select('id', 'answer')
            ->where(fn ($q) => $q->where('answer', 'like', '%<%')->orWhere('answer', 'like', '%&%'))
            ->orderBy('id')
            ->chunkById(500, function ($rows) {
                foreach ($rows as $row) {
                    $clean = RichText::clean($row->answer);

                    if ($clean !== $row->answer) {
                        DB::table('answer_essays')->where('id', $row->id)->update(['answer' => $clean]);
                    }
                }
            });
    }

    public function down(): void
    {
        // Tidak bisa dikembalikan: konten berbahaya memang sengaja dibuang.
    }
};
