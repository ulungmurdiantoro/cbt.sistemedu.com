<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Model;

class UserRoleAssignment extends Model
{
    protected $table = 'user_roles';

    protected $fillable = ['user_id', 'role'];

    protected function casts(): array
    {
        return ['role' => UserRole::class];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
