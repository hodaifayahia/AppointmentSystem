<?php

namespace App\Models;

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Specialization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WaitList extends Model
{
    use SoftDeletes, HasFactory;

    protected $table = 'waitlist';
    protected $fillable = [
        'id',
        'doctor_id',
        'appointmentId',
        'patient_id',
        'specialization_id', // Ensure this is in the fillable array
        'is_Daily',
        'created_by',
        'importance',
        'notes',
        'MoveToEnd',
        'created_at', // Add this if it's guarded
        'updated_at', // Add this if it's guarded

    ];
    
    protected $nullable = [
        'doctor_id', // Ensure this is included if using $nullable
    ];

    /**
     * Get the doctor associated with the waitlist entry.
     */
    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }

    /**
     * Get the patient associated with the waitlist entry.
     */
    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    /**
     * Get the specialization associated with the waitlist entry.
     */
    public function specialization()
    {
        return $this->belongsTo(Specialization::class, 'specialization_id');
    }
}