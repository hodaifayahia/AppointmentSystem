<?php

namespace App\Http\Controllers;

use App\AppointmentSatatusEnum;
use App\Enum\AppointmentStatusEnum;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

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

    
    public function getTimeSlots(Request $request)
    {
        $request->validate([
            'date' => 'required|date_format:Y-m-d',
            'doctor_id' => 'required|exists:doctors,id'
        ]);

        try {
            $date = Carbon::parse($request->date);
            $doctor = Doctor::findOrFail($request->doctor_id);
            
            // Validate if the date is not in the past
            if ($date->startOfDay()->lt(Carbon::today())) {
                return response()->json(['error' => 'Cannot book appointments for past dates'], 422);
            }

            // Get doctor's schedule
            $startTime = Carbon::parse($doctor->start_time);
            $endTime = Carbon::parse($doctor->end_time);
            $slotDuration = $doctor->time_slots ?? 30; // minutes
            
            // Get booked appointments for the day
            $bookedSlots = Appointment::where('doctor_id', $doctor->id)
                ->whereDate('appointment_date', $date)
                ->whereIn('status', ['scheduled', 'confirmed'])
                ->pluck('appointment_time')
                ->map(fn($time) => Carbon::parse($time)->format('H:i'))
                ->toArray();

            // Calculate available slots
            $slots = [];
            $currentTime = $date->copy()->setTimeFrom($startTime);
            $endDateTime = $date->copy()->setTimeFrom($endTime);

            while ($currentTime < $endDateTime) {
                $timeString = $currentTime->format('H:i');
                
                // Check if slot is available
                $isAvailable = !in_array($timeString, $bookedSlots) && 
                             $this->isWithinWorkingHours($currentTime, $doctor) &&
                             $this->hasntReachedDailyLimit($date, $doctor);

                $slots[] = [
                    'time' => $timeString,
                    'available' => $isAvailable
                ];
                
                $currentTime->addMinutes($slotDuration);
            }

            return response()->json([
                'slots' => $slots,
                'slot_duration' => $slotDuration
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in getTimeSlots: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch time slots'], 500);
        }
    }

    private function isWithinWorkingHours(Carbon $time, Doctor $doctor)
    {
        // Convert time to minutes for easier comparison
        $timeInMinutes = $time->hour * 60 + $time->minute;
        $startInMinutes = Carbon::parse($doctor->start_time)->hour * 60 + Carbon::parse($doctor->start_time)->minute;
        $endInMinutes = Carbon::parse($doctor->end_time)->hour * 60 + Carbon::parse($doctor->end_time)->minute;

        // Check if time is within working hours
        if ($timeInMinutes < $startInMinutes || $timeInMinutes >= $endInMinutes) {
            return false;
        }

        // Add break time logic if needed
        if ($doctor->break_start && $doctor->break_end) {
            $breakStartMinutes = Carbon::parse($doctor->break_start)->hour * 60 + Carbon::parse($doctor->break_start)->minute;
            $breakEndMinutes = Carbon::parse($doctor->break_end)->hour * 60 + Carbon::parse($doctor->break_end)->minute;
            
            if ($timeInMinutes >= $breakStartMinutes && $timeInMinutes < $breakEndMinutes) {
                return false;
            }
        }

        return true;
    }

    private function hasntReachedDailyLimit(Carbon $date, Doctor $doctor)
    {
        if (!$doctor->daily_appointment_limit) {
            return true;
        }

        $appointmentCount = Appointment::where('doctor_id', $doctor->id)
            ->whereDate('appointment_date', $date)
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->count();

        return $appointmentCount < $doctor->daily_appointment_limit;
    }



    public function checkAvailability(Request $request) 
    {
        // Validate the request data
        $data = $request->validate([
            'days' => 'required|integer|min:1',
        ]);
    
        try {
            // Ensure the days are treated as an integer
            $days = (int) $data['days'];
    
            $currentDate = Carbon::now();
            $nextAppointment = $currentDate->copy()->addDays($days);
            $period = $this->calculatePeriod($days);
    
            return response()->json([
                'current_date' => $currentDate->format('Y-m-d'),
                'next_appointment' => $nextAppointment->format('Y-m-d'),
                'period' => $period,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error checking availability: ' . $e->getMessage());
            return response()->json(['error' => 'An error occurred while checking availability.'], 500);
        }
    }
    

    private function calculatePeriod($days)
    {
        if ($days >= 365) {
            $years = floor($days / 365);
            $remainingDays = $days % 365;
            return $years . ' year(s)' . ($remainingDays ? ' and ' . $remainingDays . ' day(s)' : '');
        }
        
        if ($days >= 30) {
            $months = floor($days / 30);
            $remainingDays = $days % 30;
            return $months . ' month(s)' . ($remainingDays ? ' and ' . $remainingDays . ' day(s)' : '');
        }
        
        return $days . ' day(s)';
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
