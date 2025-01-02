<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScheduleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array
{
    return [
        'id' => $this->id,
        'day_of_week' => $this->day_of_week,
        'date' => $this->date,
        'number_of_patients_per_day' => $this->number_of_patients_per_day,
        'start_time' => $this->start_time,
        'end_time' => $this->end_time,
        'shift_period' => $this->shift_period,
    ];
}
}
