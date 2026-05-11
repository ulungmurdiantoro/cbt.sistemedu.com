<?php

namespace App\Models;

use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Participant extends Authenticatable implements CanResetPasswordContract
{
    use HasFactory, CanResetPassword;

    protected $fillable = [
        'name',
        'email',
        'password',
        'email_verified_at',
        'nik',
        'tempat_lahir',
        'tanggal_lahir',
        'jenis_kelamin',
        'kebangsaan',
        'alamat_rumah',
        'kode_pos_rumah',
        'telp_rumah',
        'hp',
        'email_alt',
        'kualifikasi_pendidikan',
        'institusi',
        'jabatan',
        'alamat_kantor',
        'kode_pos_kantor',
        'telp_kantor',
        'fax_kantor',
        'email_kantor',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'tanggal_lahir'     => 'date',
            'password'          => 'hashed',
        ];
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new \App\Notifications\ParticipantResetPasswordNotification($token));
    }

    public function assessmentApplications()
    {
        return $this->hasMany(AssessmentApplication::class);
    }

    public function students()
    {
        return $this->hasMany(Student::class);
    }
}
