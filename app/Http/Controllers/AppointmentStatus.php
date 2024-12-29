<?php

namespace App\Http\Controllers;

use App\AppointmentSatatusEnum;
use App\Models\Appointment;
use Illuminate\Http\Request;

class AppointmentStatus extends Controller
{
    function appointmentStatus()  {

        $casces = AppointmentSatatusEnum::cases();

        return collect($casces)->map(function ($status) {
            return [
                'name'=>$status->name,
                'value'=>$status->value,
                'count'=>Appointment::where('status',$status->value)->count(),
                'color'=>AppointmentSatatusEnum::from($status->value)->color(),
                'icon' => $status->icon(), // Include icon in the response

            ];
        });
    }
}
