<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Specialization;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboradController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
{
    $doctorId = $request->input('doctorId');
    
    // Base queries with optional doctor filter
    $appointmentsQuery = Appointment::query();
    
    // Apply doctor filter to all queries if doctorId is provided
    if ($doctorId) {
        $appointmentsQuery->where('doctor_id', $doctorId);
    }
    
    // Get counts with the appropriate filter
    $todayAppointmentsCount = (clone $appointmentsQuery)
        ->whereDate('appointment_date', Carbon::today())
        ->count();
    
    $totalDoctorsCount = Doctor::count();
    $totalPatientsCount = Patient::count();
    $totalAppointmentsCount = $appointmentsQuery->count();
    
    $upcomingAppointmentsCount = (clone $appointmentsQuery)
        ->whereDate('appointment_date', '>', Carbon::today())
        ->count();
    
    // Get counts for each status
    $scheduledAppointmentsCount = (clone $appointmentsQuery)->where('status', 0)->count();
    $confirmedAppointmentsCount = (clone $appointmentsQuery)->where('status', 1)->count();
    $cancelledAppointmentsCount = (clone $appointmentsQuery)->where('status', 2)->count();
    $pendingAppointmentsCount = (clone $appointmentsQuery)->where('status', 3)->count();
    $doneAppointmentsCount = (clone $appointmentsQuery)->where('status', 4)->count();
    
    return response()->json([
        'todayAppointmentsCount' => $todayAppointmentsCount,
        'totalDoctorsCount' => $totalDoctorsCount,
        'totalPatientsCount' => $totalPatientsCount,
        'totalAppointmentsCount' => $totalAppointmentsCount,
        'upcomingAppointmentsCount' => $upcomingAppointmentsCount,
        'scheduledAppointmentsCount' => $scheduledAppointmentsCount,
        'confirmedAppointmentsCount' => $confirmedAppointmentsCount,
        'cancelledAppointmentsCount' => $cancelledAppointmentsCount,
        'pendingAppointmentsCount' => $pendingAppointmentsCount,
        'doneAppointmentsCount' => $doneAppointmentsCount,
    ]);
}

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
