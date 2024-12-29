<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DoctorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->user->name,
            'email' => $this->user->email,
            'phone' => $this->user->phone,
            'specialization' => $this->specialization->name,
            'time_slots' => $this->time_slot,
            // 'start_time' => $this->start_time->format(config('app.time_format')),
            // 'end_time' => $this->end_time->format(config('app.time_format')),
            'frequency' => $this->frequency,
            // 'number_of_patients_per_day' => $this->number_of_patients_per_day, // Changed from number_of_patient
            'specific_date' => $this->specific_date,
            'appointment_booking_window' => $this->appointment_booking_window,
            'created_at' => $this->created_at->format(config('app.date_formate')),
            'updated_at' => $this->updated_at->format(config('app.date_formate')),
        ];
    }
}
