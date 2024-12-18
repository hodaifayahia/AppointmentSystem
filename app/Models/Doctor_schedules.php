<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Doctor_schedules extends Model
{
    
    protected $fillable = [
        'doctor_id',
      
        'notes',
    ];
    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }
    
}
