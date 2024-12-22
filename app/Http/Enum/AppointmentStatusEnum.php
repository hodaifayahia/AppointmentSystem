<?php

namespace App\Enum;  // Make sure this matches your folder structure

enum AppointmentStatusEnum: int  // Not StatuEnum
{
    case SCHEDULED = 0;
    case DONE = 1;
    case CANCELED = 2;
    case PENDING = 3;

    public function color(): string 
    {
        return match($this) {
            self::SCHEDULED => 'primary',
            self::DONE => 'success',
            self::CANCELED => 'danger',
            self::PENDING => 'warning',
            default => 'secondary'
        };
    }
}