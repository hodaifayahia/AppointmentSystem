<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

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
        'avatar' => $this->avatar
            ? asset(Storage::url($this->avatar)) // Use Storage::url() for proper storage path resolution
            : asset('storage/default.png'), // Default avatar
        'created_at' => $this->created_at ? $this->created_at->format(config('app.date_format', 'Y-m-d H:i:s')) : null,
        'updated_at' => $this->updated_at ? $this->updated_at->format(config('app.date_format', 'Y-m-d H:i:s')) : null,
    ];
}

}
