<?php

namespace App\Models;

use App\Support\AnswerFile;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;

class StudentTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'exam_session_id',
        'file_path',
        'original_filename',
        'file_size',
        'uploaded_at',
    ];

    protected function casts(): array
    {
        return [
            'uploaded_at' => 'datetime',
        ];
    }

    /**
     * Simpan (atau ganti) file tugas peserta untuk satu sesi — dipakai portal ujian
     * (student/tugas) dan portal peserta (dashboard). File baru disimpan dulu, baru
     * file lama dihapus, supaya upload yang gagal tidak menghilangkan tugas lama.
     */
    public static function storeUpload(int $studentId, int $sessionId, UploadedFile $file): self
    {
        $oldPath = static::where('student_id', $studentId)
            ->where('exam_session_id', $sessionId)
            ->value('file_path');

        $task = static::updateOrCreate(
            ['student_id' => $studentId, 'exam_session_id' => $sessionId],
            [
                'file_path'         => AnswerFile::store($file, "student_tasks/{$sessionId}/{$studentId}"),
                'original_filename' => $file->getClientOriginalName(),
                'file_size'         => $file->getSize(),
                'uploaded_at'       => now(),
            ]
        );

        if ($oldPath && $oldPath !== $task->file_path) {
            AnswerFile::delete($oldPath);
        }

        return $task;
    }

    /** Data untuk halaman pratinjau tugas (portal asesor & admin). */
    public function previewProps(): array
    {
        return [
            'original_filename' => $this->original_filename,
            'file_size'         => $this->file_size,
            'uploaded_at'       => $this->uploaded_at,
            'type'              => AnswerFile::extension($this->file_path),
            'previewable'       => AnswerFile::previewable($this->file_path),
        ];
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function examSession()
    {
        return $this->belongsTo(ExamSession::class);
    }
}
