<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AsesmenReport extends Model
{
    protected $fillable = [
        'exam_session_id',
        'user_id',
        'tanggal_asesmen',
        'aspek_negatif_positif',
        'pencatatan_penolakan',
        'saran_perbaikan',
        'catatan',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_asesmen' => 'date',
            'submitted_at'    => 'datetime',
        ];
    }

    public function examSession()
    {
        return $this->belongsTo(ExamSession::class);
    }

    public function asesor()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
