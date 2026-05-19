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
        'title_en',
    ];

    public function documentRequirements()
    {
        return $this->hasMany(ClassroomDocumentRequirement::class)->orderBy('order');
    }

    public function competencyUnits()
    {
        return $this->hasMany(ClassroomCompetencyUnit::class)->orderBy('order');
    }

    public function assessmentApplications()
    {
        return $this->hasMany(AssessmentApplication::class);
    }

    public function gradingScheme()
    {
        return $this->hasOne(GradingScheme::class);
    }
}
