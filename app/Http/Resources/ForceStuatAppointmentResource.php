<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ForceStuatAppointmentResource extends JsonResource
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
            'doctor_id' => $this->doctor_id,
            'user_id' => $this->user_id,
            'is_able_to_force' => $this->is_able_to_force,
            'doctor_name' => optional($this->doctor)->name, // Prevents errors if doctor is null
        ];
    }
}
