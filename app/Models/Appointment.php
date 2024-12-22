<?php

namespace App\Models;

use App\AppointmentSatatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Appointment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'doctor_id',
        'patient_id',
        'notes',
        'appointment_date',
        'appointment_time',
        'status',
    ];

   
protected $casts = [
    'appointment_date' => 'datetime',
    'appointment_time' => 'datetime',
    'status' => AppointmentSatatusEnum::class
];
    // Define relationships if needed.
    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }
    

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}
