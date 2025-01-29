<?php

namespace App\Http\Controllers;

use App\AppointmentSatatusEnum;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AppointmentStatus extends Controller
{
    public function appointmentStatus(Request $request, $doctorid) {
      
        
        if (!$doctorid) {
            return response()->json([
                'success' => false,
                'message' => 'Doctor ID is required.'
            ], 400);
        }
    
        $casces = AppointmentSatatusEnum::cases();
    
        return collect($casces)->map(function ($status) use ($doctorid) {
            return [
                'name' => $status->name,
                'value' => $status->value,
                'count' => Appointment::where('status', $status->value)
                            ->where('doctor_id', $doctorid)
                            ->count(),
                'color' => AppointmentSatatusEnum::from($status->value)->color(),
                'icon' => $status->icon(), // Include icon in the response
            ];
        });
    }
    public function appointmentStatusPatient(Request $request, $patientid) {
      
        
        if (!$patientid) {
            return response()->json([
                'success' => false,
                'message' => 'Doctor ID is required.'
            ], 400);
        }
    
        $casces = AppointmentSatatusEnum::cases();
    
        return collect($casces)->map(function ($status) use ($patientid) {
            return [
                'name' => $status->name,
                'value' => $status->value,
                'count' => Appointment::where('status', $status->value)
                            ->where('patient_id', $patientid)
                            ->count(),
                'color' => AppointmentSatatusEnum::from($status->value)->color(),
                'icon' => $status->icon(), // Include icon in the response
            ];
        });
    }
    public function todaysAppointments(Request $request, $doctorid) {
        // Debugging: Check the value of doctorid
 
        if (!$doctorid) {
            return response()->json([
                'success' => false,
                'message' => 'Doctor ID is required.'
            ], 400);
        }
    
        // Get today's date
        $today = Carbon::today()->toDateString();
    
        // Fetch today's appointments where schedule is 0
        $appointments = Appointment::where('doctor_id', $doctorid)
                            ->where('status', 0) // Filter for schedule = 0
                            ->whereDate('created_at', $today) // Filter for today's appointments
                            ->get();
    
        return response()->json([
            'success' => true,
            'data' => $appointments,
            'count' => $appointments->count(), // Count of today's appointments where schedule is 0
        ]);
    }
}