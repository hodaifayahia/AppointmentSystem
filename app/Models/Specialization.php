<?php

namespace App\Models;

use App\Models\Doctor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Specialization extends Model
{
    use SoftDeletes;



    protected $fillable = [
        'id',
        'name',
        'photo',
        'description',
    ];

    public function doctors()
{
    return $this->belongsToMany(Doctor::class);
}
}
