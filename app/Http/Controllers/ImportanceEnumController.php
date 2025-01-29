<?php

namespace App\Http\Controllers;

use App\ImportanceEnum;
use App\Models\WaitList;
use Illuminate\Http\Request;

class ImportanceEnumController extends Controller
{
    public function index()
    {
        // Fetch the count of each importance level from the database
        $importanceCounts = WaitList::select('importance', \DB::raw('count(*) as count'))
            ->groupBy('importance')
            ->pluck('count', 'importance')
            ->toArray();

        // Map the enum values and include the count
        $importanceLevels = [
            'Urgent' => [
                'value' => ImportanceEnum::Urgent->value,
                'label' => ImportanceEnum::Urgent->label(),
                'color' => ImportanceEnum::Urgent->color(),
                'icon' => ImportanceEnum::Urgent->icon(),
                'count' => $importanceCounts[ImportanceEnum::Urgent->value] ?? 0,
            ],
            'Normal' => [
                'value' => ImportanceEnum::Normal->value,
                'label' => ImportanceEnum::Normal->label(),
                'color' => ImportanceEnum::Normal->color(),
                'icon' => ImportanceEnum::Normal->icon(),
                'count' => $importanceCounts[ImportanceEnum::Normal->value] ?? 0,
            ],
        ];

        return response()->json($importanceLevels);
    }
}