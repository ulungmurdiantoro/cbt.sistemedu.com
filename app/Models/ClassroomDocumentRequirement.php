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

    /**
     * Persyaratan "Dokumen Identitas Diri (KTP/SIM/Paspor)". Persyaratan dibuat bebas oleh
     * admin per skema (tanpa kode baku), jadi dikenali dari label/kodenya.
     */
    public function isIdentityDocument(): bool
    {
        return (bool) preg_match('/identitas|\bktp\b|paspor/i', $this->label . ' ' . $this->code);
    }
}
