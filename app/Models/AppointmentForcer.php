<?php

namespace App\Models;

use App\Models\Doctor;
use Illuminate\Database\Eloquent\Model;

class AppointmentForcer extends Model
{
    protected $fillable =[
        'doctor_id',
        'start_time',
        'end_time',
        'number_of_patients',
        'user_id',
        'is_able_to_force',
    ];

    // Define the relationship to the Doctor model
    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }
   
}
