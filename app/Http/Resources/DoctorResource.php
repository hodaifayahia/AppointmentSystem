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
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'specialization' => $this->specialization,
            // 'avatar' => $this->avatar ? url($this->avatar) : null,
            'day' =>$this->day,
            'start_time' =>$this->start_time,
            'end_time' =>$this->end_time,
            'frequency' =>$this->frequency,
            'number_of_patient' =>$this->number_of_patient,
            'specific_date' =>$this->specific_date,
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }
}
