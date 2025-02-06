<?php

namespace App\Http\Controllers;

use App\Http\Resources\ScheduleResource;
use App\Models\Doctor;
use App\Models\Schedule;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // Fetch schedules for the specified doctor
        $schedules = Schedule::where('doctor_id', $request->doctor_id)->get();
    
        // Fetch doctor details including name (assuming 'name' is the correct column name)
        $doctor = Doctor::where('id', $request->doctor_id)
        ->with('user:id,name') // Only select id and name from users table
        ->first();
        // If you want to return both schedules and doctor information
        return [
            'doctor_name' => $doctor ?$doctor->user->name : null, // Assuming you have a DoctorResource
            'patients_based_on_time' => $doctor ?$doctor->patients_based_on_time : null, // Assuming you have a DoctorResource
            'schedules' => ScheduleResource::collection($schedules)
        ];
    }
}
