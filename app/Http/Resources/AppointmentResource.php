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
            'patient_first_name'=>$this->patient->Firstname,
            'patient_last_name'=>$this->patient->Lastname,
            'phone'=>$this->patient->phone,
            'doctor_name'=>$this->doctor->user->name,
            'appointment_date' => $this->appointment_date,
            'appointment_time' => $this->appointment_time,
            'status' => [
                'name' => $this->status->name,
                'color' => $this->status->color(),
            ],

        ];
    }
}
