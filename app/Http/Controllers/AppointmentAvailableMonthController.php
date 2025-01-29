<?php

namespace App\Http\Controllers;

use App\Models\AppointmentAvailableMonth;
use Illuminate\Http\Request;

class AppointmentAvailableMonthController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index($doctorid)
    {
        return AppointmentAvailableMonth::where('doctor_id',$doctorid)->get();
    }
}