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
            'first_name' => $this->Firstname,
            'last_name' => $this->Lastname,
            'Parent' => $this->Parent,
            'phone' => $this->phone,
            'dateOfBirth' => $this->dateOfBirth,
            'Idnum' => $this->Idnum,
            
        ];
    }
}
