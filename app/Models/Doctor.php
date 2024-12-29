<?php

namespace App\Models;

use App\Models\Specialization;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Doctor extends Model 
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'specialization_id',
        'frequency',
        'specific_date',
        'notes',
        'patients_based_on_time',
        'appointment_booking_window',
        'time_slot',
        'created_by',
        'user_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'days' => 'array',
        'patients_based_on_time' => 'boolean',
        'specific_date' => 'date',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'number_of_patients_per_day' => 'integer',
    ];

    /**
     * Get the user that owns the doctor.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Get the available days for appointments.
     *
     * @return array
     */
    public function schedules()
    {
        return $this->hasMany(Schedule::class, 'doctor_id');
    }

    /**
     * Check if doctor is available on a specific day.
     *
     * @param string $day
     * @return bool
     */
    public function isAvailableOn(string $day): bool
    {
        return in_array($day, $this->getAvailableDays());
    }

    /**
     * Get formatted time slot.
     *
     * @return string|null
     */
    public function getTimeSlotAttribute(?string $value): ?string
    {
        return $value;
    }
    public function specialization(): BelongsTo
{
    return $this->belongsTo(Specialization::class);
}

    /**
     * Set frequency attribute.
     *
     * @param string $value
     * @return void
     */
    public function setFrequencyAttribute(string $value): void
    {
        $this->attributes['frequency'] = strtolower($value);
    }
}