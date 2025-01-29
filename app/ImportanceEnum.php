<?php

namespace App;

enum ImportanceEnum: int
{
    case Urgent = 0;
    case Normal= 1;
   

    public function label(): string
    {
        return match ($this) {
            self::Urgent => 'Urgent',
            self::Normal => 'Normal',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Urgent => 'danger', // Gray
            self::Normal => 'primary', // Blue
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Normal => 'fa fa-circle', // Bootstrap Icon for Normal
            self::Urgent => 'fa fa-exclamation-triangle', // Bootstrap Icon for Urgent
        };
    }
}