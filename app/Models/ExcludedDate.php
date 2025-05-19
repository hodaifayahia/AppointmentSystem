<?php

namespace App\Models;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExcludedDate extends Model
{
    protected $fillable = [
        'doctor_id',
        'end_date',
        'start_date',
        'start_time',
        'end_time',
        'exclusionType',
        'number_of_patients_per_day',
        'shift_period',
        'is_active',
        'created_by',
        'updated_by',
        'deleted_by',
        'apply_for_all_years',
        'reason',
    ];
    
    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];
    
    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }
   
    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }
}
