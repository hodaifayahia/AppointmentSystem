<?php

namespace App\Models;

use App\Models\Consultation;
use App\Models\Doctor;
use App\Models\Placeholder;
use Illuminate\Database\Eloquent\Model;

class Placeholder_attribute extends Model
{
    protected $fillable = [
        'name',
        'value',
        'placeholder_id',
    ];

    public function placeholder()
    {
        return $this->belongsTo(Placeholder::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }
   
    public function consultation()
    {
        return $this->hasMany(Consultation::class);
    }
}
