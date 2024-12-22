<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Specialization extends Model
{




    protected $fillable = [
        'id',
        'name',
        'description',
    ];

    public function doctors()
{
    return $this->belongsToMany(Doctor::class);
}
}
