<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'Firstname',
        'Lastname',
        'phone',
        'created_at',
        'updated_at'
    ];
}
