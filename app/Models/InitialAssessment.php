<?php

namespace App\Models;

use App\Support\InitialAssessmentRubric;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InitialAssessment extends Model
{
    use HasFactory;

    protected $fillable = [
        'assessment_application_id',
        'classroom_id',
        'revision',
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

    protected $appends = ['result_sentence'];

    /** Rubrik FR.APL.03 sesuai revisi saat penilaian ini disimpan (bukan revisi terbaru). */
    public function rubric(): array
    {
        return InitialAssessmentRubric::for($this->classroom_id, $this->revision ?? 'Rev.03');
    }

    protected function resultSentence(): Attribute
    {
        return Attribute::get(fn () => InitialAssessmentRubric::resultSentence(
            (int) $this->total_score, (int) $this->threshold, $this->revision ?? 'Rev.03'
        ));
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
