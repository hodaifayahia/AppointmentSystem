<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class DoctorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->user->name ?? '',
            'avatar' => $this->user->avatar 
                ? asset(Storage::url($this->user->avatar)) 
                : asset('storage/default.png'),
            'email' => $this->user->email,
            'phone' => $this->user->phone,
            'specialization' => $this->specialization->name ?? null,
            'time_slots' => $this->time_slot, // If applicable
            'frequency' => $this->frequency,
            'patients_based_on_time' => $this->patients_based_on_time,
            'specific_date' => $this->specific_date,
            'appointment_booking_window' => $this->appointment_booking_window,
            'schedules' => $this->schedules->map(function ($schedule) {
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
            }),
            'created_at' => $this->created_at->format(config('app.date_formate')),
            'updated_at' => $this->updated_at->format(config('app.date_formate')),
        ];
    }

}
