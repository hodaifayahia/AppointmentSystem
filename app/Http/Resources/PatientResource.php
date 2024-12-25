<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientResource extends JsonResource
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
            'firstname' => $this->Firstname,
            'lastname' => $this->Lastname,
            'phone' => $this->phone,
            // Format the date using Carbon
            'created_at' => $this->created_at->format(config('app.date_formate')),
            // Or use a specific format you prefer
            'updated_at' => $this->updated_at->format(config('app.date_formate')), 
        ];
    }
}
