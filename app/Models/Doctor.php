<?php

namespace App\Models;

use App\Models\Schedule;
use App\Models\Specialization;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

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
        'patients_based_on_time',
        'notes',
        'avatar',
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
        'number_of_patients_per_day' => 'integer',
    ];

    /**
     * Get the working dates for the current month.
     *
     * @return array
     */
    public function getWorkingDatesForMonth($month): array
    {
        $schedules = $this->schedules;
        dd($schedules);
        $workingDates = [];
    
        // Parse the month into year and month
        $year = substr($month, 0, 4);
        $monthNumber = substr($month, 5, 2);
    
        foreach ($schedules as $schedule) {
            if ($schedule->date) {
                // If a specific date is provided, use it as a working date
                $workingDates[] = $schedule->date;
            } else {
                // If no specific date is provided, generate dates based on the day_of_week
                $dayOfWeek = $schedule->day_of_week;
                $currentMonth = Carbon::create($year, $monthNumber, 1)->startOfMonth();
                $endOfMonth = Carbon::create($year, $monthNumber, 1)->endOfMonth();
    
                while ($currentMonth->lte($endOfMonth)) {
                    if ($currentMonth->isDayOfWeek($dayOfWeek)) {
                        $workingDates[] = $currentMonth->toDateString();
                    }
                    $currentMonth->addDay();
                }
            }
        }
    
        return $workingDates;
    }
// Doctor.php
public function schedules()
{
    return $this->hasMany(Schedule::class);
}
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
  

    /**
     * Check if doctor is available on a specific day.
     *
     * @param string $day
     * @return bool
     */
    public function isAvailableOn(string $day): bool
    {
        // Replace this with logic to check availability
        return in_array($day, $this->getWorkingDatesForMonth());
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

    /**
     * Get the specialization of the doctor.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
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