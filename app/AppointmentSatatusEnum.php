<?php

namespace App;

enum AppointmentSatatusEnum:int
{
    case SCHEDULED = 0;
    case CONFIRMED = 1;
    case CANCELED = 2;
    case PENDING = 3;

    public function color(): string 
    {
        return match($this) {
            self::SCHEDULED => 'primary',
            self::CONFIRMED => 'success',
            self::CANCELED => 'danger',
            self::PENDING => 'warning',
            default => 'secondary'
        };
    }
}
