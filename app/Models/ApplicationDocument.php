<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApplicationDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'assessment_application_id',
        'classroom_document_requirement_id',
        'file_path',
        'original_filename',
        'mime_type',
        'file_size',
        'status',
        'reviewer_notes',
    ];

    public function application()
    {
        return $this->belongsTo(AssessmentApplication::class, 'assessment_application_id');
    }

    public function requirement()
    {
        return $this->belongsTo(ClassroomDocumentRequirement::class, 'classroom_document_requirement_id');
    }
}
