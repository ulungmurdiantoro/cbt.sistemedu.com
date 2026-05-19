<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NumberingCounter extends Model
{
    protected $fillable = ['type', 'scope', 'year', 'last_number'];
}
