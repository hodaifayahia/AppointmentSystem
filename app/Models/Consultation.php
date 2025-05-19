<?php

namespace App\Models;

use App\Models\Doctor;
use App\Models\Placeholder;
use Illuminate\Database\Eloquent\Model;

class Consultation extends Model
{
    
    protected $fillable = [
        'name',
        'description',
        'doctor_id',
        'placeholder_type_id',
        'placeholder_category_id',
        'specializations_id',
    ];

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }
    public function placeholder()
    {
        return $this->hasMany(Placeholder::class);
    }
 
}
