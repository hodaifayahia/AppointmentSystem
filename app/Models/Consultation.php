<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Consultation extends Model
{
    protected $fillable = [
        'template_id',
        'patient_id',
        'doctor_id',
        'appointment_id'
    ];

    public function template()
    {
        return $this->belongsTo(Template::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function placeholderAttributes()
    {
        return $this->hasMany(ConsultationPlaceholderAttributes::class);
    }
}
