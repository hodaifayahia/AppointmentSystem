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
 // Get the waitlist importance from the eager-loaded relationship
        return [
            'id' => $this->id,
            'patient_first_name' => $this->patient->Firstname ?? 'Unknown',
            'patient_id' => $this->patient->id ?? 'Unknown',
            'patient_last_name' => $this->patient->Lastname ?? 'Unknown',
            'patient_Date_Of_Birth' => $this->patient->dateOfBirth ?? 'Unknown',
            'created_by' => $this->createdByUser->name ?? 'Unknown',
            'canceled_by' => $this->canceledByUser->name ?? 'Unknown',
            'updated_by' => $this->updatedByUser->name ?? 'Unknown',
            'created_at' => $this->created_at ?? 'Unknown',
            'updated_at' => $this->updated_at ?? 'Unknown',
            'Parent' => $this->patient->Parent ?? '',
            'phone' => $this->patient->phone ?? 'N/A',
            'doctor_name' => optional($this->doctor->user)->name ?? 'Unknown',
            'doctor_id' => $this->doctor->id ?? 'Unknown',
            'appointment_date' => $this->appointment_date,
            'appointment_time' => $this->appointment_time,
            'add_to_waitlist' => $this->add_to_waitlist,
            'reason' => $this->reason,
            'description' => $this->notes,
            'status' => [
                'name' => $this->status->name ?? 'Unknown',
                'color' => $this->status?->color() ?? 'default',
                'icon' => $this->status?->icon() ?? 'default',
                'value' => $this->status?->value ?? null,  // Add the value of the status enum here
            ],
            'importance' => $this->waitlist->importance ?? 'Unknown',
            'specialization_id' => $this->doctor->specialization_id ?? 'Unknown',
            'is_Daily' => $this->waitlist->is_Daily ?? 'Unknown',];
    }
}
