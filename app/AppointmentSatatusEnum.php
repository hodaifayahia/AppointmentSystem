<?php

namespace App;

enum AppointmentSatatusEnum:int
{
    case SCHEDULED = 0;
    case CONFIRMED = 1;
    case CANCELED = 2;
    case PENDING = 3;
    case DONE = 4;

    public function color(): string
    {
        return match($this) {
            self::SCHEDULED => 'primary', // Blue
            self::CONFIRMED => 'success', // Green
            self::DONE => 'info',         // Light Blue
            self::CANCELED => 'danger',  // Red
            self::PENDING => 'warning',  // Yellow
            default => 'secondary',      // Gray
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::SCHEDULED => 'fa fa-calendar-check',
            self::DONE => 'fa fa-check-circle',
            self::CANCELED => 'fa fa-ban',
            self::PENDING => 'fa fa-hourglass-half',
            self::CONFIRMED => 'fa fa-check',
            default => 'fa fa-question-circle',
        };
    }
}