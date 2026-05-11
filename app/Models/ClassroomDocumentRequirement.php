<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassroomDocumentRequirement extends Model
{
    use HasFactory;

    protected $fillable = [
        'classroom_id',
        'code',
        'label',
        'description',
        'is_required',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
        ];
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }

    public function documents()
    {
        return $this->hasMany(ApplicationDocument::class);
    }
}
