<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class SpecializationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, // Include the ID if needed
            'name' => $this->name,
            'description' => $this->description,
            'photo_url' => $this->photo 
            ? asset(Storage::url($this->photo)) 
            : asset('storage/default.png'),
            'created_at' => $this->created_at?->toDateTimeString(), // Format the created_at timestamp
            'updated_at' => $this->updated_at?->toDateTimeString(), // Format the updated_at timestamp
        ];
    }
}