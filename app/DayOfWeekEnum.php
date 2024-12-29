<?php

namespace App;

enum DayOfWeekEnum: string
{
    case SUNDAY = 'sunday';
    case MONDAY = 'monday';
    case TUESDAY = 'tuesday';
    case WEDNESDAY = 'wednesday';
    case THURSDAY = 'thursday';
    case FRIDAY = 'friday';
    case SATURDAY = 'saturday';

    public static function getValues(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function getLabels(): array
    {
        return [
            self::SUNDAY->value => 'Sunday',
            self::MONDAY->value => 'Monday',
            self::TUESDAY->value => 'Tuesday',
            self::WEDNESDAY->value => 'Wednesday',
            self::THURSDAY->value => 'Thursday',
            self::FRIDAY->value => 'Friday',
            self::SATURDAY->value => 'Saturday',
        ];
    }

    public function label(): string
    {
        return self::getLabels()[$this->value];
    }
}