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
        'updated_by',
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
public function updatedByUser()
{
    return $this->belongsTo(User::class, 'updated_by');
}
public static function isSlotAvailable($doctorId, $date, $time, $excludedStatuses)
    {
        return !self::where('doctor_id', $doctorId)
            ->whereDate('appointment_date', $date)
            ->where('appointment_time', $time)
            ->whereNotIn('status', $excludedStatuses)
            ->exists();
    }
     // Method to check if a slot is available (excluding the current appointment)
     public static function isSlotAvailableForUpdate($doctorId, $date, $time, $excludedStatuses, $appointmentId)
     {
         return !self::where('doctor_id', $doctorId)
             ->whereDate('appointment_date', $date)
             ->where('appointment_time', $time)
             ->whereNotIn('status', $excludedStatuses)
             ->where('id', '!=', $appointmentId) // Exclude the current appointment
             ->exists();
     }
// In App\Models\Appointment.php

public function scopeFilterByDate($query, $date = null)
{
    if ($date) {
        return $query->whereDate('appointment_date', $date);
    }
    
    // Default to today's date if no date is provided
    return $query->whereDate('appointment_date', now()->toDateString());
}

public function scopeFilterByStatus($query, $status)
{
    if ($status && $status !== 'ALL') {
        return $query->where('status', $status);
    }
    
    return $query;
}

public function scopeFilterFuture($query)
{
    return $query->whereDate('appointment_date', '>=', now()->startOfDay());
}

public function scopeFilterToday($query)
{
    return $query->whereDate('appointment_date', now()->toDateString())
                ->whereIn('status', [0, 1]); // Assuming these are valid status codes
}
}
