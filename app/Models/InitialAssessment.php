<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InitialAssessment extends Model
{
    use HasFactory;

    protected $fillable = [
        'assessment_application_id',
        'classroom_id',
        'answers',
        'total_score',
        'threshold',
        'is_eligible',
        'assessed_by',
        'assessed_at',
    ];

    protected function casts(): array
    {
        return [
            'answers'     => 'array',
            'is_eligible' => 'boolean',
            'assessed_at' => 'datetime',
        ];
    }

    public function application()
    {
        return $this->belongsTo(AssessmentApplication::class, 'assessment_application_id');
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }

    public function assessor()
    {
        return $this->belongsTo(User::class, 'assessed_by');
    }
}
