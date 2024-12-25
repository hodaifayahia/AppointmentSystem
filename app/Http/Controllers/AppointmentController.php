<?php

namespace App\Http\Controllers;

use App\AppointmentSatatusEnum;
use App\Enum\AppointmentStatusEnum;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    
    public function index(Request $request)
    {
        $appointment = Appointment::query()
        ->with(['patient', 'doctor:id,user_id', 'doctor.user:id,name'])
        ->whereHas('doctor', function ($query) {
            $query->whereNull('deleted_at')
                  ->whereHas('user');
        })
        ->when($request->status, function ($query, $status) {
            $query->where('status', $status);
        })
        ->when($request->doctor_id, function ($query, $doctor_id) {
            $query->where('doctor_id', $doctor_id);
        })
        ->whereNull('deleted_at')
        ->latest()
        ->get();
    
    return AppointmentResource::collection($appointment);
    }


    public function checkAvailability(Request $request)
    {
        $request->validate([
            'doctor_id' => 'required|exists:doctors,id',
            'date' => 'required|date_format:Y-m-d',
        ]);

        $doctor = Doctor::findOrFail($request->doctor_id);
        $date = $request->date;

        // Get all booked appointments for the doctor on the specified date
        $bookedSlots = Appointment::where('doctor_id', $doctor->id)
            ->whereDate('appointment_date', $date)
            ->pluck('appointment_time')
            ->map(function($time) {
                return Carbon::parse($time)->format('H:i');
            })
            ->toArray();

        return response()->json([
            'booked_slots' => $bookedSlots
        ]);
    }
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'doctor_id' => 'required',
            'start_date' => 'required|date_format:Y-m-d',
            'appointment_time' => 'required|date_format:H:i',
            'description' => 'nullable|string|max:1000',
            'status' => 'required|integer' // Assuming status is required and an integer
        ]);
        // Check if slot is already booked
        $existingAppointment = Appointment::where('doctor_id', $request->doctor_id)
            ->whereDate('appointment_date', $request->start_date)
            ->where('appointment_time', $request->appointment_time)
            ->exists();

        if ($existingAppointment) {
            return response()->json([
                'message' => 'This time slot is already booked.',
                'errors' => ['appointment_time' => ['The selected time slot is no longer available.']]
            ], 422);
        }
        $patient = Patient::firstOrCreate([
            'Firstname' => $validated['first_name'],
            'Lastname' => $validated['last_name'],
            'phone' => $validated['phone'],
        ]);
        
        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            'doctor_id' => $validated['doctor_id'],
            'appointment_date' => $validated['start_date'],
            'appointment_time' => $validated['appointment_time'],
            'notes' => $validated['description'] ?? null,
            'status' => $validated['status'],  // Make sure 'status' is in your validation
            'created_by' => 1
        ]);

        return new AppointmentResource($appointment);
    }
    public function show($id)
    {
        $appointment = Appointment::findOrFail($id);
        return new AppointmentResource($appointment);
    }

    public function update(Request $request, $id)
    {
        $appointment = Appointment::findOrFail($id);
        
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'doctor_id' => 'required|exists:doctors,id',
            'appointment_date' => 'required|date_format:Y-m-d',
            'appointment_time' => 'required|date_format:H:i',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Check if new slot is already booked (excluding current appointment)
        $existingAppointment = Appointment::where('doctor_id', $request->doctor_id)
            ->whereDate('appointment_date', $request->appointment_date)
            ->where('appointment_time', $request->appointment_time)
            ->where('id', '!=', $id)
            ->exists();

        if ($existingAppointment) {
            return response()->json([
                'message' => 'This time slot is already booked.',
                'errors' => ['appointment_time' => ['The selected time slot is no longer available.']]
            ], 422);
        }

        $appointment->update($validated);
        
        return new AppointmentResource($appointment);
    }

}
