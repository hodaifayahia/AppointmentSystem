<?php

namespace App\Models;

use App\AppointmentSatatusEnum;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use App\Models\WaitList;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Appointment extends Model
{

    use SoftDeletes;
    protected $fillable = [
        'doctor_id',
        'patient_id',
        'notes',
        'appointment_booking_window',
        'appointment_date',
        'appointment_time',
        'add_to_waitlist',
        'reason',
        'created_by',
        'canceled_by',
        'status',
    ];

   
protected $casts = [
    'status' => AppointmentSatatusEnum::class
];
    // Define relationships if needed.
    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }
    
// In Appointment model
public function patient()
{
    return $this->belongsTo(Patient::class, 'patient_id');
}

public function waitlist()
{
    return $this->hasOne(WaitList::class, 'patient_id', 'patient_id')
                ->where('doctor_id', $this->doctor_id);
}
// Appointment.php (Model)
public function createdByUser()
{
    return $this->belongsTo(User::class, 'created_by');
}

public function canceledByUser()
{
    return $this->belongsTo(User::class, 'canceled_by');
}
}
