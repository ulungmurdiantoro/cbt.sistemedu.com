<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Classroom extends Model
{
    use HasFactory;

    /**
     * fillable
     *
     * @var array
     */
    protected $fillable = [
        'classrooms_code',
        'kode_skema',
        'gelar',
        'title',
    ];

    public function documentRequirements()
    {
        return $this->hasMany(ClassroomDocumentRequirement::class)->orderBy('order');
    }

    public function assessmentApplications()
    {
        return $this->hasMany(AssessmentApplication::class);
    }
}
