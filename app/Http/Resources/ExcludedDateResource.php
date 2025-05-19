<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ExcludedDateResource extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray($request): array
    {
        return $this->collection
            ->groupBy('start_date')
            ->map(function ($dates, $dateKey) {
                return [
                    'start_date' => $dateKey,
                    'end_date' => optional($dates->first()->end_date)?->format('Y-m-d'),
                    'apply_for_all_years' => $dates->first()->apply_for_all_years,
                    'reason' => $dates->first()->reason,
                    'is_active' => $dates->first()->is_active,
                    'exclusionType' => $dates->first()->exclusionType,
                    'created_at' => optional($dates->first()->created_at)?->format('Y-m-d H:i:s'),
                    'updated_at' => optional($dates->first()->updated_at)?->format('Y-m-d H:i:s'),
                    'doctor_id' => $dates->first()->doctor_id,
                    'doctor_name' => optional($dates->first()->doctor?->user)->name ?? "ALL DOCTORS",
                    'shifts' => $dates->map(function ($date) {
                        return [
                            'shift_period' => $date->shift_period,
                            'start_time' => $date->start_time,
                            'end_time' => $date->end_time,
                            'number_of_patients_per_day' => $date->number_of_patients_per_day,
                        ];
                    })->values()->all(),
                ];
            })
            ->values()
            ->all();
    }
}
