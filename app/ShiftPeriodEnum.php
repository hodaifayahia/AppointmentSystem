<?php

namespace App;

enum ShiftPeriodEnum: string
{
    case MORNING = 'morning';
    case AFTERNOON = 'afternoon';

    public static function getValues(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function getLabels(): array
    {
        return [
            self::MORNING->value => 'Morning (8:00 - 12:00)',
            self::AFTERNOON->value => 'Afternoon (14:00 - 17:00)'
        ];
    }

    public function getTimeRange(): array
    {
        return match($this) {
            self::MORNING => [
                'start' => '08:00:00',
                'end' => '12:00:00'
            ],
            self::AFTERNOON => [
                'start' => '14:00:00',
                'end' => '17:00:00'
            ]
        };
    }

    public function label(): string
    {
        return self::getLabels()[$this->value];
    }
}

// // Get all days
// $days = DayOfWeek::getValues();

// // Get human readable label
// $monday = DayOfWeek::MONDAY;
// echo $monday->label(); // "Monday"

// // Get time range for a shift
// $morning = ShiftPeriod::MORNING;
// $timeRange = $morning->getTimeRange();
// echo $timeRange['start']; // "08:00:00"
// echo $timeRange['end'];   // "12:00:00"

// // Use in your model
// protected $casts = [
//     'day_of_week' => DayOfWeek::class,
//     'shift_period' => ShiftPeriod::class
// ];