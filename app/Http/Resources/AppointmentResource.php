<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentResource extends JsonResource
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
        'patient_first_name' => $this->patient->Firstname ?? 'Unknown',
        'patient_id' => $this->patient->id ?? 'Unknown',
        'patient_last_name' => $this->patient->Lastname ?? 'Unknown',
        'patient_Date_Of_Birth' => $this->patient->dateOfBirth ?? 'Unknown',
        'phone' => $this->patient->phone ?? 'N/A',
        'doctor_name' => optional($this->doctor->user)->name ?? 'Unknown',
        'appointment_date' => $this->appointment_date,
        'appointment_time' => $this->appointment_time,
        'status' => [
            'name' => $this->status->name ?? 'Unknown',
            'color' => $this->status?->color() ?? 'default',
            'icon' => $this->status?->icon() ?? 'default',
            'value' => $this->status?->value ?? null,  // Add the value of the status enum here
        ],
        ];
    }
}
