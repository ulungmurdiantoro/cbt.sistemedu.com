<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * File jawaban/tugas yang diunggah peserta ujian.
 *
 * Disimpan di disk `private` (tidak bisa diakses lewat /storage dan tidak bisa
 * dieksekusi web server) dan hanya disajikan lewat controller yang mengecek
 * hak akses. File lama hasil upload sebelum perubahan ini masih ada di disk
 * `public` — pindahkan dengan `php artisan answer-files:move-private`.
 */
class AnswerFile
{
    public const DISK = 'private';

    public const EXTENSIONS = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png', 'zip', 'rar'];

    public const MAX_KB = 20480;

    /** Tipe yang bisa dipratinjau di browser tanpa diunduh → MIME yang dikirim. */
    public const PREVIEW_MIME = [
        'pdf'  => 'application/pdf',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];

    public static function rules(): array
    {
        $list = implode(',', self::EXTENSIONS);

        return ['required', 'file', 'max:' . self::MAX_KB, "mimes:{$list}", "extensions:{$list}"];
    }

    public static function messages(string $field = 'file'): array
    {
        $types = strtoupper(implode(', ', self::EXTENSIONS));

        return [
            "{$field}.required"   => 'File wajib dipilih.',
            "{$field}.max"        => 'Ukuran file maksimal ' . (self::MAX_KB / 1024) . ' MB.',
            "{$field}.mimes"      => "Tipe file tidak diizinkan. Gunakan: {$types}.",
            "{$field}.extensions" => "Tipe file tidak diizinkan. Gunakan: {$types}.",
        ];
    }

    /** Nilai atribut accept="" untuk <input type="file">. */
    public static function accept(): string
    {
        return implode(',', array_map(fn ($ext) => ".{$ext}", self::EXTENSIONS));
    }

    /** Simpan file ke disk private dengan nama aman; ekstensi sudah divalidasi oleh rules(). */
    public static function store(UploadedFile $file, string $directory): string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $safeName  = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'file';
        $filename  = $safeName . '-' . now()->format('YmdHis') . '-' . Str::random(6) . '.' . $extension;

        return $file->storeAs($directory, $filename, self::DISK);
    }

    /** Disk tempat file berada: private, atau public untuk file lama yang belum dipindah. */
    public static function diskFor(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        foreach ([self::DISK, 'public'] as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                return $disk;
            }
        }

        return null;
    }

    public static function size(?string $path): ?int
    {
        $disk = self::diskFor($path);

        return $disk ? Storage::disk($disk)->size($path) : null;
    }

    public static function delete(?string $path): void
    {
        if ($disk = self::diskFor($path)) {
            Storage::disk($disk)->delete($path);
        }
    }

    public static function download(?string $path): StreamedResponse
    {
        $disk = self::diskFor($path);

        abort_unless($disk, 404, 'File tidak ditemukan');

        return Storage::disk($disk)->download($path, basename($path));
    }

    public static function extension(?string $path): string
    {
        return strtolower(pathinfo((string) $path, PATHINFO_EXTENSION));
    }

    public static function previewable(?string $path): bool
    {
        return isset(self::PREVIEW_MIME[self::extension($path)]);
    }

    /**
     * Sajikan file untuk modal pratinjau (inline, tidak di-cache). Hanya tipe di
     * PREVIEW_MIME — tipe lain tidak dikirim sama sekali karena tidak bisa ditampilkan.
     */
    public static function preview(?string $path): StreamedResponse
    {
        $disk = self::diskFor($path);

        abort_unless($disk, 404, 'File tidak ditemukan.');
        abort_unless(self::previewable($path), 415, 'Format file ini tidak dapat dipratinjau.');

        return Storage::disk($disk)->response($path, null, [
            'Content-Type'           => self::PREVIEW_MIME[self::extension($path)],
            'Cache-Control'          => 'no-store, private',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
