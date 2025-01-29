<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class WaitListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'doctor_id' => $this->doctor_id,
            'doctor_name' => $this->doctor->user->name ?? 'N/A', // Assuming the doctor's name is stored in the User model
            'patient_id' => $this->patient_id,
            'patient_first_name' => $this->patient->Firstname ?? 'N/A',
            'patient_last_name' => $this->patient->Lastname ?? 'N/A',
            'patient_phone' => $this->patient->phone ?? 'N/A',
            'specialization_id' => $this->specialization_id,
            'specialization_name' => $this->specialization->name ?? 'N/A',
            'is_Daily' => $this->is_Daily,
            'importance' => $this->importance,
            'MoveToEnd' => $this->MoveToEnd,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'created_by' => $this->created_by,
        ];
    }
}