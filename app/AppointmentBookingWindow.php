<?php

namespace App;

enum AppointmentBookingWindow :int
{
    case OneMonth = 1;
    case ThreeMonths = 3;
    case FiveMonths = 5;
}
