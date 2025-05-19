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
    
        // Get count of appointments grouped by status (Single Query)
        $statusCounts = Appointment::where('doctor_id', $doctorid)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');
    
        // Map status cases
        return collect(AppointmentSatatusEnum::cases())->map(function ($status) use ($statusCounts) {
            return [
                'name' => $status->name,
                'value' => $status->value,
                'count' => $statusCounts[$status->value] ?? 0, // Use count if available, else 0
                'color' => AppointmentSatatusEnum::from($status->value)->color(),
                'icon' => $status->icon(),
            ];
        });
    }
    public function appointmentStatusPatient(Request $request, $patientid) {
        if (!$patientid) {
            return response()->json([
                'success' => false,
                'message' => 'Patient ID is required.'
            ], 400);
        }
    
        // Get count of appointments grouped by status (Single Query)
        $statusCounts = Appointment::where('patient_id', $patientid)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');
    
        // Map status cases
        return collect(AppointmentSatatusEnum::cases())->map(function ($status) use ($statusCounts) {
            return [
                'name' => $status->name,
                'value' => $status->value,
                'count' => $statusCounts[$status->value] ?? 0, // Use count if available, else 0
                'color' => AppointmentSatatusEnum::from($status->value)->color(),
                'icon' => $status->icon(),
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