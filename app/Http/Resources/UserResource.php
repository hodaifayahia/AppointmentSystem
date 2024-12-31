<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'role' => $this->role,
            // Format the date if it exists, otherwise return an empty string or null
          'created_at' => $this->created_at->format(config('app.date_format', 'Y-m-d H:i:s')),
'updated_at' => $this->updated_at->format(config('app.date_format', 'Y-m-d H:i:s')),
        ];
    }
}
