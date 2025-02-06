<?php

namespace App\Http\Resources;
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class DoctorResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->user->name ?? '',
            'avatar' => $this->getAvatarUrl(),
            'email' => $this->user->email ?? '',
            'phone' => $this->user->phone ?? '',
            'specialization' => $this->specialization->name ?? null,
            'specialization_id' => $this->specialization->id ?? null,
            'time_slots' => $this->time_slot,
            'frequency' => $this->frequency,
            'patients_based_on_time' => $this->patients_based_on_time,
            'specific_date' => $this->specific_date,
            'total_patients_per_day' => $this->getTotalPatientsPerDay(), // Add this line

            'appointment_booking_window' => $this->formatAppointmentBookingWindow(),
            'schedules' => $this->formatSchedules(),
            'created_at' => $this->formatTimestamp($this->created_at),
            'updated_at' => $this->formatTimestamp($this->updated_at),
        ];
    }

    protected function getAvatarUrl()
    {
        return $this->user->avatar
            ? asset(Storage::url($this->user->avatar))
            : asset('storage/default.png');
    }

    protected function getTotalPatientsPerDay()
    {
        $totalPatients = 0;
        
        foreach ($this->schedules as $schedule) {
            if ($schedule->is_active) {
                $totalPatients += $schedule->number_of_patients_per_day;
            }
        }
    
        return $totalPatients;
    }
    protected function formatAppointmentBookingWindow()
    {
        // Ensure the relationship is loaded and not null
        if ($this->appointmentAvailableMonths) {
            return $this->appointmentAvailableMonths->map(function ($month) {
                return [
                    'month' => $month->month,
                    'is_available' => $month->is_available,
                ];
            });
        }

        // Return an empty array if the relationship is not loaded or null
        return [];
    }

    protected function formatSchedules()
    {
        return $this->schedules->map(function ($schedule) {
            return [
                'id' => $schedule->id,
                'day_of_week' => $schedule->day_of_week,
                'date' => $schedule->date,
                'number_of_patients_per_day' => $schedule->number_of_patients_per_day,
                'start_time' => $schedule->start_time,
                'end_time' => $schedule->end_time,
                'shift_period' => $schedule->shift_period,
                'is_active' => $schedule->is_active,
            ];
        });
    }

    /**
     * Format a timestamp using the application's date format.
     *
     * @param  \Carbon\Carbon|null  $timestamp
     * @return string|null
     */
    protected function formatTimestamp($timestamp)
    {
        $format = config('app.date_format', 'Y-m-d'); // Fallback to 'Y-m-d' if config is missing
        return $timestamp ? $timestamp->format($format) : null;
    }
}