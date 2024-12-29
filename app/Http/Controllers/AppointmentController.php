<?php

namespace App\Http\Controllers;

use App\AppointmentSatatusEnum;
use App\DayOfWeekEnum;
use App\Enum\AppointmentStatusEnum;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Schedule;
use Carbon\Carbon;
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
    public function getNextAvailableTimeSlot(Request $request)
    {
        $validated = $request->validate([
            'doctor_id' => 'required|exists:doctors,id',
            'date' => 'required|date_format:Y-m-d',
            'range' => 'nullable|integer|min:0',  // Validate range input (optional)
        ]);
    
        $doctorId = $validated['doctor_id'];
        $date = $validated['date'];
        $range = $validated['range'] ?? 0;  // Default range is 0 if not provided
    
        // Iterate through future dates to find available slots if no range is provided
        $currentDate = Carbon::parse($date);
        
        if ($range > 0) {
            // If range is provided, search both backward and forward
            $availableSlot = $this->findNextAvailableAppointmentWithinRange($currentDate, $doctorId, $range);
        } else {
            // If no range is provided, follow the previous process to find the next available slot forward
            $availableSlot = $this->findNextAvailableAppointment($currentDate, $doctorId);

        }
    
        if ($availableSlot) {
            // Get the doctor's working hours for the available date
            $workingHours = $this->getDoctorWorkingHours($doctorId, $availableSlot->format('Y-m-d'));
    
            // Get the booked slots for the available date
            $bookedSlots = $this->getBookedSlots($doctorId, $availableSlot->format('Y-m-d'));
            
            // Find available slots by subtracting booked slots from working hours
            $availableSlots = array_diff($workingHours, $bookedSlots);
            // If available slots are found, return them
            return response()->json([
                'next_available_date' => $availableSlot->format('Y-m-d'),
                'available_slots' => array_values($availableSlots), // Ensure a clean array format
            ]);
        }
    
        // If no available slot is found, return a message
        return response()->json(['message' => 'No available slots found within the specified range.'], 404);
    }
    
    


    private function getDoctorWorkingHours($doctorId, $date)
    {
        $dayOfWeek = DayOfWeekEnum::cases()[Carbon::parse($date)->dayOfWeek]->value;
        
        // Get all schedules for the doctor on the specific day
        $schedules = Schedule::where('doctor_id', $doctorId)
            ->where('is_active', true)
            ->where('day_of_week', $dayOfWeek)
            ->get();
            
        $doctor = Doctor::find($doctorId, ['patients_based_on_time', 'time_slot']);
        
        if (!$doctor || $schedules->isEmpty()) {
            return [];
        }
    
        $workingHours = [];
        
        // Process morning shift
        $morningSchedule = $schedules->firstWhere('shift_period', 'morning');
        if ($morningSchedule) {
            try {
                $startTime = Carbon::parse($date . ' ' . $morningSchedule->start_time);
                $endTime = Carbon::parse($date . ' ' . $morningSchedule->end_time);
                
                if ($doctor->time_slot) {
                    // Convert time_slot to integer and validate
                    $timeSlotMinutes = (int) $doctor->time_slot;
                    if ($timeSlotMinutes > 0) {
                        $currentTime = clone $startTime;
                        while ($currentTime < $endTime) {
                            $workingHours[] = $currentTime->format('H:i');
                            $currentTime->addMinutes($timeSlotMinutes);
                        }
                    }
                } else {
                    // Calculate based on number of patients for morning
                    $totalMinutes = $endTime->diffInMinutes($startTime);
                    $patientsPerDay = max(1, (int) $morningSchedule->number_of_patients_per_day);
                    $slotDuration = floor($totalMinutes / $patientsPerDay);
                    
                    if ($slotDuration > 0) {
                        $currentTime = clone $startTime;
                        while ($currentTime < $endTime) {
                            $workingHours[] = $currentTime->format('H:i');
                            $currentTime->addMinutes($slotDuration);
                        }
                    }
                }
            } catch (\Exception $e) {
                // Log error and continue
                Log::error('Error processing morning schedule: ' . $e->getMessage());
            }
        }
       
        // Process afternoon shift
        $afternoonSchedule = $schedules->firstWhere('shift_period', 'afternoon');
        if ($afternoonSchedule) {
            try {
                $startTime = Carbon::parse($date . ' ' . $afternoonSchedule->start_time);
                $endTime = Carbon::parse($date . ' ' . $afternoonSchedule->end_time);
                
                if ($doctor->time_slot) {
                    // Convert time_slot to integer and validate
                    $timeSlotMinutes = (int) $doctor->time_slot;
                    if ($timeSlotMinutes > 0) {
                        $currentTime = clone $startTime;
                        while ($currentTime < $endTime) {
                            $workingHours[] = $currentTime->format('H:i');
                            $currentTime->addMinutes($timeSlotMinutes);
                        }
                    }
                } else {
                    // Calculate based on number of patients for afternoon
                    $totalMinutes = $endTime->diffInMinutes($startTime);
                    $patientsPerDay = max(1, (int) $afternoonSchedule->number_of_patients_per_day);
                    $slotDuration = floor($totalMinutes / $patientsPerDay);
                    
                    if ($slotDuration > 0) {
                        $currentTime = clone $startTime;
                        while ($currentTime < $endTime) {
                            $workingHours[] = $currentTime->format('H:i');
                            $currentTime->addMinutes($slotDuration);
                        }
                    }
                }
            } catch (\Exception $e) {
                // Log error and continue
                Log::error('Error processing afternoon schedule: ' . $e->getMessage());
            }
        }
    
        return array_unique($workingHours);
    }

    private function getBookedSlots($doctorId, $date)
{
    // Fetch all booked appointments for the given date and doctor
    $bookedAppointments = Appointment::where('doctor_id', $doctorId)
        ->whereDate('appointment_date', $date)
        ->whereIn('status', ['scheduled', 'confirmed'])
        ->get();

    // Convert appointment times to a simple time format for comparison
    return $bookedAppointments->map(function($appointment) {
        // Assuming appointment_time is stored as a datetime or time string
        return Carbon::parse($appointment->appointment_time)->format('H:i');
    })->toArray();
}

public function checkAvailability(Request $request)
{
    try {
        $validated = $request->validate([
            'doctor_id' => 'required|exists:doctors,id',
            'date' => 'nullable|date_format:Y-m-d',
            'days' => 'nullable|integer|min:1',
            'range' => 'nullable|integer|min:0',
            'include_slots' => 'nullable|in:true,false,1,0' // Modified validation rule
        ]);

        // Ensure doctor_id exists
        $doctorId = $validated['doctor_id'];
        
        // Determine the start date for the search
        if (isset($validated['date'])) {
            $startDate = Carbon::parse($validated['date']);
        } else {
            $days = isset($validated['days']) ? (int) $validated['days'] : 0;
            $startDate = Carbon::now()->addDays($days);
        }

        // Get range if provided, default to 0
        $range = isset($validated['range']) ? (int) $validated['range'] : 0;

        // Find next available appointment based on range
        if ($range > 0) {
            $nextAvailableDate = $this->findNextAvailableAppointmentWithinRange($startDate, $doctorId, $range);
        } else {
            $nextAvailableDate = $this->findNextAvailableAppointment($startDate, $doctorId);
        }
    
        if (!$nextAvailableDate) {
            return response()->json([
                'message' => 'No available slots found within the specified range.'
            ]);
        }

        // Calculate period difference from current date
        $daysDifference = abs($nextAvailableDate->diffInDays(Carbon::now()));
        $period = $this->calculatePeriod($daysDifference);
        // Build the response
        $response = [
            'current_date' => Carbon::now()->format('Y-m-d'),
            'next_available_date' => $nextAvailableDate->format('Y-m-d'),
            'period' => $period
        ];

        // Convert string boolean to actual boolean
        $includeSlots = isset($validated['include_slots']) && 
            in_array($validated['include_slots'], ['true', '1'], true);
        
        if ($includeSlots) {
            $workingHours = $this->getDoctorWorkingHours($doctorId, $nextAvailableDate->format('Y-m-d'));
            $bookedSlots = $this->getBookedSlots($doctorId, $nextAvailableDate->format('Y-m-d'));
            
            // Find available slots by subtracting booked slots from working hours
            $availableSlots = array_diff($workingHours, $bookedSlots);
            
            // Add slots information to response
            $response['available_slots'] = array_values($availableSlots);
        }

        return response()->json($response);
        
    } catch (\Exception $e) {
        // Log the error
        Log::error('Error in checkAvailability: ' . $e->getMessage());
        
        // Return a user-friendly error response
        return response()->json([
            'message' => 'An error occurred while checking availability.',
            'error' => $e->getMessage()
        ], 500);
    }
}

private function findNextAvailableAppointmentWithinRange(Carbon $startDate, $doctorId, $range)
{
    // First check the start date itself
    $currentDate = clone $startDate;
    if ($this->isDateAvailable($doctorId, $currentDate)) {
        return $currentDate;
    }

    // If range is provided, search backward with decreasing range
    if ($range > 0) {
        // Start with maximum range and decrease
        for ($currentRange = $range; $currentRange > 0; $currentRange--) {
            $checkDate = clone $startDate;
            $checkDate->subDays($currentRange);
            
            if ($this->isDateAvailable($doctorId, $checkDate)) {
                return $checkDate;
            }
        }
    }

    // If no slots found backward, search forward but only up to range
    $currentDate = clone $startDate;
    // Only search forward up to the range value
    for ($i = 1; $i <= $range; $i++) {
        $currentDate->addDay();
        if ($this->isDateAvailable($doctorId, $currentDate)) {
            return $currentDate;
        }
    }

    // If no appointment found within the range, return null
    // This is important - don't continue searching beyond the range
    return null;
}

/**
 * Find the next available appointment starting from a given date.
 * This is used when no range is specified.
 */
private function findNextAvailableAppointment($startDate, $doctorId)
{
    $currentDate = Carbon::parse($startDate);
    $maxDaysToSearch = 365; // Add a reasonable limit to prevent infinite loop
    $daysSearched = 0;

    while ($daysSearched < $maxDaysToSearch) {
        if ($this->isDateAvailable($doctorId, $currentDate)) {
            return $currentDate;
        }
        $currentDate->addDay();
        $daysSearched++;
    }

    return null;
}

 /**
     * Check if a specific date is available.
     *
     * @param int $doctorId
     * @param Carbon $date
     * @return bool
     */
    private function isDateAvailable($doctorId, Carbon $date)
    {
        // Retrieve available slots for the given doctor on the specified date
        $workingHours = $this->getDoctorWorkingHours($doctorId, $date->format('Y-m-d'));
        $bookedSlots = $this->getBookedSlots($doctorId, $date->format('Y-m-d'));

        // If there are any available slots, the date is available
        $availableSlots = array_diff($workingHours, $bookedSlots);
        return !empty($availableSlots);
    }

    /**
     * Find the next available appointment starting from a given date.
     *
     * @param mixed $startDate
     * @param int $doctorId
     * @return Carbon|null
     */


  /**
 * Calculate period in days/months/years.
 *
 * @param int $days
 * @return string
 */

 private function calculatePeriod($days)
 {
     // Ensure that $days is an integer before processing
     $days = (int) $days;
 
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
            'appointment_date' => 'required|date_format:Y-m-d',
            'appointment_time' => 'required|date_format:H:i',
            'description' => 'nullable|string|max:1000',
            'status' => 'required|integer' // Assuming status is required and an integer
        ]);
        // Check if slot is already booked
        $existingAppointment = Appointment::where('doctor_id', $request->doctor_id)
            ->whereDate('appointment_date', $request->appointment_date)
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
            'appointment_date' => $validated['appointment_date'],
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


    public function AiailableAppointments(Request $request) {
        // Validate the doctor_id
        $validated = $request->validate([
            'doctor_id' => 'required|exists:doctors,id',
        ]);
    
        $doctorId = $validated['doctor_id'];
    
        // Get canceled appointments grouped by date
        $canceledAppointments = Appointment::where('doctor_id', $doctorId)
            ->where('status', AppointmentSatatusEnum::CANCELED->value)
            ->with(['patient', 'doctor:id,user_id', 'doctor.user:id,name'])
            ->get();
    
        // Group by the date, you can use the Carbon package to remove the time part for comparison
        $groupedAppointments = $canceledAppointments->groupBy(function ($appointment) {
            return Carbon::parse($appointment->appointment_date)->format('Y-m-d'); // Group by date only
        });
    
        // Prepare the result
        $result = [];
    
        foreach ($groupedAppointments as $date => $appointments) {
            // Create a list of available time slots for the given date
            $timeSlots = $appointments->map(function ($appointment) {
                return Carbon::parse($appointment->appointment_time)->format('H:i'); // Extract appointment time
            })->unique(); // Get unique time slots
    
            // Add the date with available time slots to the result
            $result[] = [
                'date' => $date,
                'available_times' => $timeSlots
            ];
        }
    
        return response()->json($result);
    }

    public function changeAppointmentStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|integer|in:' . implode(',', array_column(AppointmentSatatusEnum::cases(), 'value')),
        ]);
    
        // Ensure the status is valid and corresponds to the enum values
        $status = $validated['status'];
    
        $appointment = Appointment::findOrFail($id);
    
        // Assign the validated status (casted to integer)
        $appointment->status = $status;
        $appointment->save();
    
        return response()->json([
            'message' => 'Appointment status updated successfully.',
            'appointment' => new AppointmentResource($appointment),
        ]);
    }
    public function destroy($id)
    {
        $appointment = Appointment::findOrFail($id);
        $appointment->delete();

        return response()->json([
            'message' => 'Appointment deleted successfully.'
        ]);
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
