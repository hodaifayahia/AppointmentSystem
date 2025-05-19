<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlaceholderResource extends JsonResource
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
            'description' => $this->description,
            'doctor' => [
                'id' => $this->doctor->id ?? null,
                'name' => $this->doctor->user->name ?? null,
            ],
            'specialization' => [

                'id' => $this->specializations->id ?? null,
                'name' => $this->specializations->name ?? null,
            ],
        ];
    }
}
