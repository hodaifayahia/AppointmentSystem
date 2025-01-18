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
   
  // AppointmentController.php

 public function index(Request $request, $doctorId)
{
    try {
        // Start building the query
        $query = Appointment::query()
            ->with(['patient', 'doctor:id,user_id', 'doctor.user:id,name'])
            ->whereHas('doctor', function ($query) {
                $query->whereNull('deleted_at')
                    ->whereHas('user');
            })
            ->where('doctor_id', $doctorId)
            ->whereNull('deleted_at')
            // Add filter for appointments from today onwards
            ->whereDate('appointment_date', '>=', now()->startOfDay())
            // Order by appointment date and time
            ->orderBy('appointment_date', 'asc')
            ->orderBy('appointment_time', 'asc');

        // Apply status filter if provided and valid
        if ($request->filled('status') && $request->status !== 'ALL') {
            $query->where('status', $request->status);
        }

        // Apply date filter if provided
        if ($request->filled('date')) {
            $query->whereDate('appointment_date', $request->date);
        }

        // Filter for today's appointments if requested
        if ($request->filled('filter') && $request->filter === 'today') {
            $query->whereDate('appointment_date', now()->toDateString());
        }

        // Fetch the filtered appointments
        $appointments = $query->get();

        return response()->json([
            'success' => true,
            'data' => AppointmentResource::collection($appointments)
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch appointments',
            'error' => $e->getMessage()
        ], 500);
    }
}
public function GetAllAppointments(Request $request, $doctorId = null)
{
    try {
        // Start building the query
        $query = Appointment::query()
            ->with(['patient', 'doctor:id,user_id', 'doctor.user:id,name'])
            ->whereHas('doctor', function ($query) {
                $query->whereNull('deleted_at')
                    ->whereHas('user');
            })
            ->whereNull('deleted_at')
            // Add filter for appointments from today onwards
            ->whereDate('appointment_date', '>=', now()->startOfDay())
            // Order by appointment date and time
            ->orderBy('appointment_date', 'asc')
            ->orderBy('appointment_time', 'asc');
        // Apply doctor_id filter only if provided
        if (!is_null($doctorId)) {
            $query->where('doctor_id', $doctorId);
        }

        // Filter for today's appointments if requested
        if ($request->filled('filter') && $request->filter === 'today') {
            $query->whereDate('appointment_date', now()->toDateString());
        }

        // Additional filters (if provided)
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('patient_name')) {
            $query->whereHas('patient', function ($q) use ($request) {
                $q->where('firstname', 'like', '%' . $request->patient_name . '%')
                  ->orWhere('lastname', 'like', '%' . $request->patient_name . '%');
            });
        }

        if ($request->filled('date')) {
            $query->whereDate('appointment_date', $request->date);
        }

        // Fetch the filtered appointments
        $appointments = $query->get();

        return response()->json([
            'success' => true,
            'data' => AppointmentResource::collection($appointments)
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch appointments',
            'error' => $e->getMessage()
        ], 500);
    }
}

  public function search(Request $request)
  {

    $query = $request->input('query');
      $doctorId = $request->input('doctor_id');
  
      $appointments = Appointment::query()
          ->when($query, function ($queryBuilder) use ($query) {
              $queryBuilder->whereHas('patient', function ($patientQuery) use ($query) {
                  $patientQuery->where('Firstname', 'like', "%{$query}%")
                      ->orWhere('Lastname', 'like', "%{$query}%")
                      ->orWhere('dateOfBirth', 'like', "%{$query}%");
              });
          })
          ->when($doctorId, function ($queryBuilder) use ($doctorId) {
              $queryBuilder->where('doctor_id', $doctorId);
          })
          ->with('patient') // Eager load the patient relationship
          ->paginate(10);
  
      return AppointmentResource::collection($appointments);
  }
  private function getDoctorWorkingHours($doctorId, $date)
{
    $dayOfWeek = DayOfWeekEnum::cases()[Carbon::parse($date)->dayOfWeek]->value;
    
    $schedules = Schedule::where('doctor_id', $doctorId)
        ->where('is_active', true)
        ->where('day_of_week', $dayOfWeek)
        ->get();
    
    $doctor = Doctor::find($doctorId, ['patients_based_on_time', 'time_slot']);
    
    if (!$doctor || $schedules->isEmpty()) {
        return [];
    }
    
    $workingHours = [];
    
    foreach (['morning', 'afternoon'] as $shift) {
        $schedule = $schedules->firstWhere('shift_period', $shift);
        if ($schedule) {
            try {
                $startTime = Carbon::parse($date . ' ' . $schedule->start_time);
                $endTime = Carbon::parse($date . ' ' . $schedule->end_time);
                
                if ($doctor->time_slot !== null) {
                    // Time slot based calculation
                    $timeSlotMinutes = (int) $doctor->time_slot;
                    if ($timeSlotMinutes > 0) {
                        $currentTime = clone $startTime;
                        while ($currentTime < $endTime) {
                            $workingHours[] = $currentTime->format('H:i');
                            $currentTime->addMinutes($timeSlotMinutes);
                        }
                    }
                } else {
                    // Patient count based calculation
                    $totalMinutes = $endTime->diffInMinutes($startTime);
                    $patientsPerShift = (int) $schedule->number_of_patients_per_day;
                    // Ensure we have valid numbers to work with
                    if (abs($totalMinutes) > 0 && abs($patientsPerShift) > 0) {
                        // Calculate minutes per patient
                       $slotDuration = (int) ceil($totalMinutes / $patientsPerShift);
                        
                        $currentTime = clone $startTime;
                        // Generate time slots until we either reach the end time or have created slots for all patients
                        $slotsCreated = 0;
                        while ($currentTime < $endTime && $slotsCreated < $patientsPerShift) {
                            $workingHours[] = $currentTime->format('H:i');
                            $currentTime->addMinutes(abs($slotDuration));
                            $slotsCreated++;
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::error("Error processing {$shift} schedule: " . $e->getMessage());
            }
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
public function ForceAppointment(Request $request)
{
    $validated = $request->validate([
        'doctor_id' => 'required|exists:doctors,id',
        'days' => 'nullable|integer|min:1',
    ]);

    $doctorId = $validated['doctor_id'];
    $days = (int)($validated['days'] ?? 0); // Ensure days is an integer

    $date = Carbon::now()->addDays($days)->format('Y-m-d');
    $gapSlots = [];
    $additionalSlots = [];

    // Fetch doctor details
    $doctor = Doctor::find($doctorId, ['time_slot']);
    $timeSlotMinutes = is_numeric($doctor->time_slot) ? (int) $doctor->time_slot : 0;

    if ($timeSlotMinutes <= 0) {
        return response()->json([
            'error' => 'Invalid time slot duration for the doctor.',
        ], 400);
    }

    // Fetch schedules
    $schedules = Schedule::where('doctor_id', $doctorId)
        ->where('is_active', true)
        ->where('day_of_week', DayOfWeekEnum::cases()[Carbon::parse($date)->dayOfWeek]->value)
        ->get();

    $morningSchedule = $schedules->firstWhere('shift_period', 'morning');
    $afternoonSchedule = $schedules->firstWhere('shift_period', 'afternoon');

    if ($morningSchedule && $afternoonSchedule) {
        try {
            // Create Carbon instances with explicit date-time strings
            $morningEndDateTime = $date . ' ' . $morningSchedule->end_time;
            $afternoonStartDateTime = $date . ' ' . $afternoonSchedule->start_time;
            $afternoonEndDateTime = $date . ' ' . $afternoonSchedule->end_time;

            $morningEndTime = Carbon::parse($morningEndDateTime);
            $afternoonStartTime = Carbon::parse($afternoonStartDateTime);
            $afternoonEndTime = Carbon::parse($afternoonEndDateTime);

            // Validate parsed times
            if (!$morningEndTime || !$afternoonStartTime || !$afternoonEndTime) {
                throw new \Exception('Invalid time format in schedule');
            }

            // Calculate gap slots
            $currentTime = clone $morningEndTime;
            while ($currentTime < $afternoonStartTime) {
                $gapSlots[] = $currentTime->format('H:i');
                $currentTime->addMinutes($timeSlotMinutes);
            }

            // Calculate additional slots
            $currentTime = clone $afternoonEndTime;
            for ($i = 0; $i < 5; $i++) {
                $currentTime->addMinutes($timeSlotMinutes);
                $additionalSlots[] = $currentTime->format('H:i');
            }
        } catch (\Exception $e) {
            Log::error('Error calculating slots: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error calculating appointment slots: ' . $e->getMessage(),
            ], 500);
        }
    }

    return response()->json([
        'gap_slots' => $gapSlots,
        'additional_slots' => $additionalSlots,
        'next_available_date' => $date,
    ]);
}
 /**
     * Retrieve a specific appointment for a doctor.
     *
     * @param  int  $doctorId
     * @param  int  $appointmentId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAppointment($doctorId, $appointmentId)
    {
        $appointment = Appointment::where('doctor_id', $doctorId)
            ->where('id', $appointmentId)
            ->first();

        if (!$appointment) {
            return response()->json([
                'success' => false,
                'message' => 'Appointment not found.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new AppointmentResource($appointment)
        ]);
    }

    public function checkAvailability(Request $request)
    {
        try {
            $validated = $request->validate([
                'doctor_id' => 'required|exists:doctors,id',
                'date' => 'nullable|date_format:Y-m-d',
                'days' => 'nullable|integer|min:1',
                'range' => 'nullable|integer|min:0',
                'include_slots' => 'nullable|in:true,false,1,0'
            ]);
    
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
    
            // Check if the doctor has any dates in the Schedule model
            $doctorHasSchedule = Schedule::where('doctor_id', $doctorId)
            ->where('date', $startDate->format('Y-m-d')) // Add date condition
            ->where('is_active', true) // Ensure the schedule is active
            ->exists();    
            // Find next available appointment based on range
            if ($range > 0) {
                $nextAvailableDate = $this->findNextAvailableAppointmentWithinRange($startDate, $doctorId, $range, $doctorHasSchedule);
            } else {
                $nextAvailableDate = $this->findNextAvailableAppointment($startDate, $doctorId, $doctorHasSchedule);
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
    private function findNextAvailableAppointmentWithinRange(Carbon $startDate, $doctorId, $range, $doctorHasSchedule)
    {
        // First check the start date itself
        $currentDate = clone $startDate;
        if ($this->isDateAvailable($doctorId, $currentDate, $doctorHasSchedule)) {
            return $currentDate;
        }
    
        // If range is provided, search backward with decreasing range
        if ($range > 0) {
            // Start with maximum range and decrease
            for ($currentRange = $range; $currentRange > 0; $currentRange--) {
                $checkDate = clone $startDate;
                $checkDate->subDays($currentRange);
    
                if ($this->isDateAvailable($doctorId, $checkDate, $doctorHasSchedule)) {
                    return $checkDate;
                }
            }
        }
    
        // If no slots found backward, search forward but only up to range
        $currentDate = clone $startDate;
        // Only search forward up to the range value
        for ($i = 1; $i <= $range; $i++) {
            $currentDate->addDay();
            if ($this->isDateAvailable($doctorId, $currentDate, $doctorHasSchedule)) {
                return $currentDate;
            }
        }
    
        // If no appointment found within the range, return null
        return null;
    }
/**
 * Find the next available appointment starting from a given date.
 * This is used when no range is specified.
 */
private function findNextAvailableAppointment($startDate, $doctorId, $doctorHasSchedule)
{
    $currentDate = Carbon::parse($startDate);
    $maxDaysToSearch = 365; // Add a reasonable limit to prevent infinite loop
    $daysSearched = 0;

    while ($daysSearched < $maxDaysToSearch) {
        if ($this->isDateAvailableforthisdate($doctorId, $currentDate, $doctorHasSchedule)) {
            return $currentDate;
        }
        $currentDate->addDay();
        $daysSearched++;
    }

    return null;
}
private function isDateAvailableforthisdate($doctorId, Carbon $date, $doctorHasSchedule)
{
    // Check if the date is in the past
    if ($date->startOfDay()->lt(Carbon::now()->startOfDay())) {
        return false;
    }

    // Check if doctor has any specific dates in their schedule
    $hasSpecificDates = Schedule::where('doctor_id', $doctorId)
        ->where('is_active', true)
        ->whereNotNull('date')
        ->exists();

    if ($hasSpecificDates) {
        // If doctor has specific dates, only show slots if the requested date matches one of those dates
        $isDateScheduled = Schedule::where('doctor_id', $doctorId)
            ->where('date', $date->format('Y-m-d'))
            ->where('is_active', true)
            ->exists();

        if (!$isDateScheduled) {
            return false;
        }

        return $this->isDateAvailable($doctorId, $date);
    } 
    // If doctor doesn't have any specific dates, use regular schedule logic
    else if ($doctorHasSchedule) {
        // Check if the date exists in the Schedule model for the given doctor
        $isScheduled = Schedule::where('doctor_id', $doctorId)
            ->where('date', $date->format('Y-m-d'))
            ->where('is_active', true)
            ->exists();

        // If the date is not in the schedule, it's not available
        if (!$isScheduled) {
            return false;
        }

        // If the date is in the schedule, check for available slots
        return $this->isDateAvailable($doctorId, $date);
    } 
    // Default availability logic for doctors without any schedules
    else {
        return $this->isDateAvailable($doctorId, $date);
    }
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
        ]);
        // Check if slot is already booked
        $excludedStatuses = [
            AppointmentSatatusEnum::CANCELED->value, // Add CANCELED here
        ];
        
        $existingAppointment = Appointment::where('doctor_id', $request->doctor_id)
            ->whereDate('appointment_date', $request->appointment_date)
            ->where('appointment_time', $request->appointment_time)
            ->whereNotIn('status', $excludedStatuses)
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
            'status' => 0,  // Make sure 'status' is in your validation
            'created_by' => 1
        ]);

        return new AppointmentResource($appointment);
    }
    public function show($id)
    {
        $appointment = Appointment::findOrFail($id);
        return new AppointmentResource($appointment);
    }
    public function AvailableAppointments(Request $request)
    {
        // Validate the doctor_id
        $validated = $request->validate([
            'doctor_id' => 'required|exists:doctors,id',
        ]);
    
        $doctorId = $validated['doctor_id'];
        $now = Carbon::now();
    
        // Get canceled appointments grouped by date, excluding past appointments
        $canceledAppointments = Appointment::where('doctor_id', $doctorId)
            ->where('status', AppointmentSatatusEnum::CANCELED->value)
            ->where(function ($query) use ($now) {
                $query->where('appointment_date', '>', $now->format('Y-m-d'))
                    ->orWhere(function ($query) use ($now) {
                        $query->where('appointment_date', '=', $now->format('Y-m-d'))
                            ->where('appointment_time', '>', $now->format('H:i'));
                    });
            })
            ->with(['patient', 'doctor:id,user_id', 'doctor.user:id,name'])
            ->get();
    
        // Group canceled appointments by date
        $groupedCanceledAppointments = $canceledAppointments->groupBy(function ($appointment) {
            return Carbon::parse($appointment->appointment_date)->format('Y-m-d');
        });
    
        // Prepare result for canceled appointments
        $canceledAppointmentsResult = [];
        foreach ($groupedCanceledAppointments as $date => $appointments) {
            // Create a list of available time slots for the given date
            $timeSlots = $appointments->map(function ($appointment) {
                return [
                    'time' => Carbon::parse($appointment->appointment_time)->format('H:i'),
                    'id' => $appointment->id, // Keep track of the appointment ID
                ];
            })->unique('time'); // Ensure unique time slots
    
            // For today, filter out past times
            if ($date === $now->format('Y-m-d')) {
                $timeSlots = $timeSlots->filter(function ($slot) use ($now) {
                    return Carbon::parse($slot['time'])->format('H:i') > $now->format('H:i');
                });
            }
    
            // Check if the canceled slots are rebooked
            $availableSlots = [];
            foreach ($timeSlots as $slot) {
                // Check if there is any appointment (not canceled) for the same date and time
                $isRebooked = Appointment::where('doctor_id', $doctorId)
                    ->where('appointment_date', $date)
                    ->where('appointment_time', $slot['time'])
                    ->where('status', '!=', AppointmentSatatusEnum::CANCELED->value)
                    ->exists();
    
                // If not rebooked, add to available slots
                if (!$isRebooked) {
                    $availableSlots[] = $slot['time'];
                }
            }
    
            // Only add dates that have available time slots
            if (!empty($availableSlots)) {
                $canceledAppointmentsResult[] = [
                    'date' => $date,
                    'available_times' => $availableSlots,
                ];
            }
        }
    
        // Find the closest available non-canceled appointment
        $closestAvailableAppointment = $this->findClosestAvailableAppointment($doctorId);
    
        // Prepare result for normal available appointments (closest)
        $normalAppointmentsResult = $closestAvailableAppointment ? [
            'date' => $closestAvailableAppointment['date'],
            'time' => $closestAvailableAppointment['time'],
        ] : null;
    
        return response()->json([
            'canceled_appointments' => $canceledAppointmentsResult,
            'normal_appointments' => $normalAppointmentsResult,
        ]);
    }
    
    private function findClosestAvailableAppointment($doctorId)
    {
        $currentDate = Carbon::now();
        $currentTime = $currentDate->format('H:i');
    
        // Default logic for doctors without a schedule
        // Check today's working hours
        $workingHours = $this->getDoctorWorkingHours($doctorId, $currentDate->format('Y-m-d'));
    
        // Filter out past hours for today
        $todayWorkingHours = collect($workingHours)->filter(function ($time) use ($currentTime) {
            return $time > $currentTime;
        });
    
        foreach ($todayWorkingHours as $time) {
            $appointmentExists = Appointment::where('doctor_id', $doctorId)
                ->where('appointment_date', $currentDate->format('Y-m-d'))
                ->where('appointment_time', $time)
                ->where('status', '!=', AppointmentSatatusEnum::CANCELED->value)
                ->exists();
    
            if (!$appointmentExists) {
                return [
                    'date' => $currentDate->format('Y-m-d'),
                    'time' => $time
                ];
            }
        }
    
        // If no available slots today, check future days
        for ($i = 1; $i < 30; $i++) { // Limit to 30 days
            $date = $currentDate->copy()->addDays($i)->format('Y-m-d');
            $workingHours = $this->getDoctorWorkingHours($doctorId, $date);
    
            foreach ($workingHours as $time) {
                $appointmentExists = Appointment::where('doctor_id', $doctorId)
                    ->where('appointment_date', $date)
                    ->where('appointment_time', $time)
                    ->where('status', '!=', AppointmentSatatusEnum::CANCELED->value)
                    ->exists();
    
                if (!$appointmentExists) {
                    return [
                        'date' => $date,
                        'time' => $time
                    ];
                }
            }
        }
    
        return null; // No available appointment found within the range
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
