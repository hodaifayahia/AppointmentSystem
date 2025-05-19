<?php

namespace App\Models;

use App\Models\Doctor;
use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    protected $fillable = [
        'doctor_id',
        'day_of_week',
        'date',
        'number_of_patients_per_day',
        'start_time',
        'end_time',
        'shift_period',
        'is_active',
        'created_by',
        'updated_by',
        'deleted_by',

    ];

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }
    public static function getSchedulesForDoctor(int $doctorId, string $dayOfWeek)
    {
        return self::select('start_time', 'end_time', 'number_of_patients_per_day', 'shift_period')
            ->where('doctor_id', $doctorId)
            ->where('is_active', true)
            ->where('day_of_week', $dayOfWeek)
            ->get();
    }
}
