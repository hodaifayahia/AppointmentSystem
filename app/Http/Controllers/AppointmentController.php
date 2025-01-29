<?php

namespace App\Http\Controllers;

use App\AppointmentSatatusEnum;
use App\DayOfWeekEnum;
use App\Enum\AppointmentStatusEnum;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Models\AppointmentAvailableMonth;
use App\Models\Doctor;
use App\Models\ExcludedDate;
use App\Models\Patient;
use App\Models\Schedule;
use App\Models\WaitList;
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
              ->with(['patient', 'doctor:id,user_id,specialization_id', 'doctor.user:id,name', 'waitlist'])
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
  
          // Apply status filter if provided and not 'ALL'
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
              // Filter for SCHEDULED (0) and CONFIRMED (1) statuses
              $query->whereIn('status', [0, 1]);
          }
  
          // Fetch the filtered appointments
          $appointments = $query->paginate(50);
  
          return response()->json([
              'success' => true,
              'data' => AppointmentResource::collection($appointments),
              'meta' => [
                  'current_page' => $appointments->currentPage(),
                  'per_page' => $appointments->perPage(),
                  'total' => $appointments->total(),
                  'last_page' => $appointments->lastPage(),
              ]
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
    // Get query parameters
    $query = $request->input('query');
    $doctorId = $request->input('doctor_id');
    $appointmentDate = $request->input('appointment_date');
    $appointmentTime = $request->input('appointment_time');

    // Base query
    $appointments = Appointment::query()
        // Filter by patient name or date of birth
        ->when($query, function ($queryBuilder) use ($query) {
            $queryBuilder->whereHas('patient', function ($patientQuery) use ($query) {
                $patientQuery->where('Firstname', 'like', "%{$query}%")
                    ->orWhere('Lastname', 'like', "%{$query}%")
                    ->orWhere('dateOfBirth', 'like', "%{$query}%");
            });
        })
        // Filter by doctor ID
        ->when($doctorId, function ($queryBuilder) use ($doctorId) {
            $queryBuilder->where('doctor_id', $doctorId);
        })
        // Filter by appointment date
        ->when($appointmentDate, function ($queryBuilder) use ($appointmentDate) {
            $queryBuilder->whereDate('appointment_date', $appointmentDate);
        })
        // Filter by appointment time
        ->when($appointmentTime, function ($queryBuilder) use ($appointmentTime) {
            $queryBuilder->whereTime('appointment_time', $appointmentTime);
        })
        // Eager load the patient relationship
        ->with('patient')
        // Paginate results
        ->paginate(10);

    return AppointmentResource::collection($appointments);
}
private function getDoctorWorkingHours($doctorId, $date)
{
    $dayOfWeek = DayOfWeekEnum::cases()[Carbon::parse($date)->dayOfWeek]->value;

    $schedules = Schedule::where('doctor_id', $doctorId)
        ->where('is_active', true)
        ->where('day_of_week', $dayOfWeek)
        ->join('doctors', 'schedules.doctor_id', '=', 'doctors.id')
        ->select('schedules.*', 'doctors.patients_based_on_time', 'doctors.time_slot')
        ->get();

    if ($schedules->isEmpty()) {
        return [];
    }

    $workingHours = [];
    $doctor = $schedules->first()->doctor; // Access doctor data from the first schedule

    foreach ($schedules as $schedule) {
        try {
            $startTime = Carbon::parse($date . ' ' . $schedule->start_time);
            $endTime = Carbon::parse($date . ' ' . $schedule->end_time);

            if ($doctor->time_slot) {
                // Time slot based calculation
                $timeSlotMinutes = (int) $doctor->time_slot;
                $currentTime = clone $startTime;
                while ($currentTime < $endTime) {
                    $workingHours[] = $currentTime->format('H:i');
                    $currentTime->addMinutes($timeSlotMinutes);
                }
            } else {
                // Patient count based calculation
                $totalMinutes = $endTime->diffInMinutes($startTime);
                $patientsPerShift = (int) $schedule->number_of_patients_per_day;
                if ($totalMinutes > 0 && $patientsPerShift > 0) {
                    $slotDuration = (int) ceil($totalMinutes / $patientsPerShift);
                    $currentTime = clone $startTime;
                    for ($i = 0; $i < $patientsPerShift && $currentTime < $endTime; $i++) {
                        $workingHours[] = $currentTime->format('H:i');
                        $currentTime->addMinutes($slotDuration);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error("Error processing schedule: " . $e->getMessage());
        }
    }

    return $workingHours;
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
        'days' => 'nullable|integer',
        'date' => 'nullable|date_format:Y-m-d', // Accept date directly
    ]);

    $doctorId = $validated['doctor_id'];
    $days = (int)($validated['days'] ?? 0); // Ensure days is an integer
    $dateInput = $validated['date'] ?? null; // Get the date input if provided

    try {
        // Use the provided date if available, otherwise calculate it based on days
        $date = $dateInput ? Carbon::parse($dateInput)->format('Y-m-d') : Carbon::now()->addDays($days)->format('Y-m-d');

        $doctor = $this->fetchDoctorDetails($doctorId);
        $timeSlotMinutes = $this->calculateTimeSlotMinutes($doctor, $date);

        $schedules = $this->fetchSchedules($doctorId, $date);
        $morningSchedule = $schedules->firstWhere('shift_period', 'morning');
        $afternoonSchedule = $schedules->firstWhere('shift_period', 'afternoon');

        $gapSlots = [];
        $additionalSlots = [];

        if (!$morningSchedule && !$afternoonSchedule) {
            // If the doctor does not work on this date, generate slots from 8:00 to 17:00
            $additionalSlots = $this->generateDefaultSlots($doctorId,$date, $timeSlotMinutes);
        } else {
            // Handle morning and afternoon schedules
            list($gapSlots, $additionalSlots) = $this->handleSchedules(
                $morningSchedule,
                $afternoonSchedule,
                $date,
                $timeSlotMinutes
            );
        }

        return response()->json([
            'gap_slots' => $gapSlots,
            'additional_slots' => $additionalSlots,
            'next_available_date' => $date,
            'time_slot_minutes' => $timeSlotMinutes,
        ]);
    } catch (\Exception $e) {
        Log::error('Error calculating slots: ' . $e->getMessage());
        return response()->json([
            'error' => 'Error calculating appointment slots: ' . $e->getMessage(),
        ], 500);
    }
}

/**
 * Fetch doctor details.
 */
private function fetchDoctorDetails($doctorId)
{
    return Doctor::find($doctorId, ['time_slot']);
}

/**
 * Calculate time slot duration dynamically if not set.
 */
private function calculateTimeSlotMinutes($doctor, $date)
{
    $timeSlotMinutes = is_numeric($doctor->time_slot) ? (int) $doctor->time_slot : 0;

    if ($timeSlotMinutes <= 0) {
        $numberOfPatients = Appointment::where('doctor_id', $doctor->id)
            ->whereDate('appointment_date', $date)
            ->count();

        $totalAvailableTime = $this->calculateTotalAvailableTime($doctor->id, $date);

        if ($numberOfPatients > 0 && $totalAvailableTime > 0) {
            $timeSlotMinutes = (int)($totalAvailableTime / $numberOfPatients);
        } else {
            $timeSlotMinutes = 30; // Default to 30 minutes
        }
    }

    return $timeSlotMinutes;
}

/**
 * Calculate total available time for the day.
 */
private function calculateTotalAvailableTime($doctorId, $date)
{
    $schedules = Schedule::where('doctor_id', $doctorId)
        ->where('is_active', true)
        ->where('day_of_week', DayOfWeekEnum::cases()[Carbon::parse($date)->dayOfWeek]->value)
        ->get();

    $totalAvailableTime = 0;

    foreach ($schedules as $schedule) {
        $startTime = Carbon::parse($date . ' ' . $schedule->start_time);
        $endTime = Carbon::parse($date . ' ' . $schedule->end_time);
        $totalAvailableTime += $endTime->diffInMinutes($startTime);
    }

    return $totalAvailableTime;
}

/**
 * Fetch schedules for the given doctor and date.
 */
private function fetchSchedules($doctorId, $date)
{
    return Schedule::where('doctor_id', $doctorId)
        ->where('is_active', true)
        ->where('day_of_week', DayOfWeekEnum::cases()[Carbon::parse($date)->dayOfWeek]->value)
        ->get();
}

/**
 * Generate default slots from 8:00 to 17:00.
 */
private function generateDefaultSlots($doctorId, $date, $timeSlotMinutes)
{
    $defaultStartTime = config('app.default_start_time', '08:00');
    $defaultEndTime = config('app.default_end_time', '17:00');

    $startTime = Carbon::parse($date . ' ' . $defaultStartTime);
    $endTime = Carbon::parse($date . ' ' . $defaultEndTime);
    $slots = [];

    $bookedSlots = $this->getBookedSlots($doctorId, $date);

    $currentTime = clone $startTime;
    while ($currentTime < $endTime) {
        $slotTime = $currentTime->format('H:i');

        if (!in_array($slotTime, $bookedSlots)) {
            $slots[] = $slotTime;
        }

        $currentTime->addMinutes($timeSlotMinutes);
    }

    return $slots;
}

/**
 * Handle morning and afternoon schedules to generate gap and additional slots.
 */
private function handleSchedules($morningSchedule, $afternoonSchedule, $date, $timeSlotMinutes)
{
    $gapSlots = [];
    $additionalSlots = [];

    if ($morningSchedule && !$afternoonSchedule) {
        $morningEndTime = Carbon::parse($date . ' ' . $morningSchedule->end_time);
        $additionalSlots = $this->generateAdditionalSlots($morningEndTime, $timeSlotMinutes);
    } elseif (!$morningSchedule && $afternoonSchedule) {
        $afternoonEndTime = Carbon::parse($date . ' ' . $afternoonSchedule->end_time);
        $additionalSlots = $this->generateAdditionalSlots($afternoonEndTime, $timeSlotMinutes);
    } elseif ($morningSchedule && $afternoonSchedule) {
        $morningEndTime = Carbon::parse($date . ' ' . $morningSchedule->end_time);
        $afternoonStartTime = Carbon::parse($date . ' ' . $afternoonSchedule->start_time);
        $afternoonEndTime = Carbon::parse($date . ' ' . $afternoonSchedule->end_time);

        // Generate gap slots between morning and afternoon shifts
        $currentTime = clone $morningEndTime;
        while ($currentTime < $afternoonStartTime) {
            $gapSlots[] = $currentTime->format('H:i');
            $currentTime->addMinutes($timeSlotMinutes);
        }

        // Generate additional slots after the afternoon shift
        $additionalSlots = $this->generateAdditionalSlots($afternoonEndTime, $timeSlotMinutes);
    }

    return [$gapSlots, $additionalSlots];
}

/**
 * Generate additional slots after a given end time.
 */
private function generateAdditionalSlots($endTime, $timeSlotMinutes)
{
    $slots = [];
    $currentTime = clone $endTime;

    for ($i = 0; $i < 20; $i++) {
        $currentTime->addMinutes($timeSlotMinutes);
        $slots[] = $currentTime->format('H:i');
    }

    return $slots;
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
    
            // Get excluded dates for the specific doctor and for all doctors
            $excludedDates = ExcludedDate::where(function ($query) use ($doctorId) {
                $query->where('doctor_id', $doctorId) // Excluded dates for this doctor
                      ->orWhereNull('doctor_id'); // Excluded dates for all doctors
            })->get();
    
            // Find next available appointment based on range
            if ($range > 0) {
                $nextAvailableDate = $this->findNextAvailableAppointmentWithinRange($startDate, $doctorId, $range, $doctorHasSchedule, $excludedDates);
            } else {
                $nextAvailableDate = $this->findNextAvailableAppointment($startDate, $doctorId, $doctorHasSchedule, $excludedDates);
            }
    
            if (!$nextAvailableDate) {
                return  $response = [
                    'next_available_date' => null,
                ];
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
    
            // Return a user-friendly error response
            return response()->json([
                'message' => 'An error occurred while checking availability.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    private function findNextAvailableAppointmentWithinRange(Carbon $startDate, $doctorId, $range, $doctorHasSchedule, $excludedDates)
    {
        // First check the start date itself
        $currentDate = clone $startDate;
        if ($this->isDateAvailableforthisdate($doctorId, $currentDate, $doctorHasSchedule, $excludedDates)) {
            return $currentDate;
        }
    
        // If range is provided, search backward with decreasing range
        if ($range > 0) {
            // Start with maximum range and decrease
            for ($currentRange = $range; $currentRange > 0; $currentRange--) {
                $checkDate = clone $startDate;
                $checkDate->subDays($currentRange);
    
                // Get the month and year of the check date
                $month = $checkDate->month;
                $year = $checkDate->year;
    
                // Check if the month and year are available for appointments
                $isMonthAvailable = AppointmentAvailableMonth::where('doctor_id', $doctorId)
                    ->where('month', $month)
                    ->where('year', $year) // Check for the specific year
                    ->where('is_available', true)
                    ->exists();
    
                // If the month and year are not available, stop searching backward
                if (!$isMonthAvailable) {
                    break;
                }
    
                if ($this->isDateAvailableforthisdate($doctorId, $checkDate, $doctorHasSchedule, $excludedDates)) {
                    return $checkDate;
                }
            }
        }
    
        // If no slots found backward, search forward but only up to range
        $currentDate = clone $startDate;
        // Only search forward up to the range value
        for ($i = 1; $i <= $range; $i++) {
            $currentDate->addDay();
    
            // Get the month and year of the current date
            $month = $currentDate->month;
            $year = $currentDate->year;
    
            // Check if the month and year are available for appointments
            $isMonthAvailable = AppointmentAvailableMonth::where('doctor_id', $doctorId)
                ->where('month', $month)
                ->where('year', $year) // Check for the specific year
                ->where('is_available', true)
                ->exists();
    
            // If the month and year are not available, stop searching forward
            if (!$isMonthAvailable) {
                break;
            }
    
            if ($this->isDateAvailableforthisdate($doctorId, $currentDate, $doctorHasSchedule, $excludedDates)) {
                return $currentDate;
            }
        }
    
        // If no appointment found within the range, return null
        return null;
    }
    
    private function findNextAvailableAppointment($startDate, $doctorId, $doctorHasSchedule, $excludedDates)
    {
        $currentDate = Carbon::parse($startDate);
        $endOfYear = Carbon::now()->endOfYear(); // End of the current year
    
        // Loop until the end of the current year
        while ($currentDate->lte($endOfYear)) {
            // Get the month and year of the current date
            $month = $currentDate->month;
            $year = $currentDate->year;
    
            // Check if the month and year are available for appointments
            $isMonthAvailable = AppointmentAvailableMonth::where('doctor_id', $doctorId)
                ->where('month', $month)
                ->where('year', $year) // Check for the specific year
                ->where('is_available', true)
                ->exists();
    
            // If the month and year are available, check if the date is available
            if ($isMonthAvailable && $this->isDateAvailableforthisdate($doctorId, $currentDate, $doctorHasSchedule, $excludedDates)) {
                return $currentDate;
            }
    
            // Move to the next day
            $currentDate->addDay();
        }
    
        // If no appointment is found within the current year, return null
        return null;
    }
private function isDateAvailableforthisdate($doctorId, Carbon $date, $doctorHasSchedule, $excludedDates)
{
    // Check if the date is in the past
    if ($date->startOfDay()->lt(Carbon::now()->startOfDay())) {
        return false;
    }

    // Check if the date is excluded
    $isExcluded = $excludedDates->contains(function ($excludedDate) use ($date) {
        return $date->between($excludedDate->start_date, $excludedDate->end_date);
    });

    if ($isExcluded) {
        return false;
    }

    // Get the month and year of the date
    $month = $date->month;
    $year = $date->year;

    // Check if the month and year are available for appointments
    $isMonthAvailable = AppointmentAvailableMonth::where('doctor_id', $doctorId)
        ->where('month', $month)
        ->where('year', $year) // Check for the specific year
        ->where('is_available', true)
        ->exists();

    // If the month and year are not available, return false
    if (!$isMonthAvailable) {
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
public function getAllCanceledAppointments(Request $request)
{
    try {
        $validated = $request->validate([
            'doctor_id' => 'required|exists:doctors,id',
        ]);

        $doctorId = $validated['doctor_id'];

        // Get excluded dates for the specific doctor and for all doctors
        $excludedDates = ExcludedDate::where(function ($query) use ($doctorId) {
            $query->where('doctor_id', $doctorId) // Excluded dates for this doctor
                  ->orWhereNull('doctor_id'); // Excluded dates for all doctors
        })->get();

        // Fetch all canceled appointments for the doctor
        $canceledAppointments = $this->findAllCanceledAppointments($doctorId, $excludedDates);

        if ($canceledAppointments->isEmpty()) {
            return response()->json([
                'message' => 'No canceled appointments found for the specified doctor.',
                'canceled_appointments' => []
            ]);
        }

        // Build the response
        $response = [
            'current_date' => Carbon::now()->format('Y-m-d'),
            'canceled_appointments' => $canceledAppointments
        ];

        return response()->json($response);

    } catch (\Exception $e) {
        // Log the error

        // Return a user-friendly error response
        return response()->json([
            'message' => 'An error occurred while fetching canceled appointments.',
            'error' => $e->getMessage()
        ], 500);
    }
}

private function findAllCanceledAppointments($doctorId, $excludedDates)
{
    // Fetch all canceled appointments for the doctor
    $appointments = Appointment::where('doctor_id', $doctorId)
        ->where('status', 'canceled')
        ->get();

    // Filter out appointments that fall on excluded dates or violate other restrictions
    $filteredAppointments = $appointments->filter(function ($appointment) use ($excludedDates, $doctorId) {
        $appointmentDate = Carbon::parse($appointment->date);

        // Check if the date is excluded
        $isExcluded = $excludedDates->contains(function ($excludedDate) use ($appointmentDate) {
            return $appointmentDate->between($excludedDate->start_date, $excludedDate->end_date);
        });

        if ($isExcluded) {
            return false;
        }

        // Check if the month and year are available for appointments
        $month = $appointmentDate->month;
        $year = $appointmentDate->year;

        $isMonthAvailable = AppointmentAvailableMonth::where('doctor_id', $doctorId)
            ->where('month', $month)
            ->where('year', $year)
            ->where('is_available', true)
            ->exists();

        if (!$isMonthAvailable) {
            return false;
        }

        // Check if the doctor has a schedule for this date (if applicable)
        $hasSpecificDates = Schedule::where('doctor_id', $doctorId)
            ->where('is_active', true)
            ->whereNotNull('date')
            ->exists();

        if ($hasSpecificDates) {
            $isDateScheduled = Schedule::where('doctor_id', $doctorId)
                ->where('date', $appointmentDate->format('Y-m-d'))
                ->where('is_active', true)
                ->exists();

            if (!$isDateScheduled) {
                return false;
            }
        }

        return true;
    });

    return $filteredAppointments->values(); // Reset keys for JSON response
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
     // Validate the request
     $validated = $request->validate([
         'patient_id' => 'required|exists:patients,id', // Ensure the patient exists
         'doctor_id' => 'required|exists:doctors,id',   // Ensure the doctor exists
         'appointment_date' => 'required|date_format:Y-m-d',
         'appointment_time' => 'required|date_format:H:i',
         'description' => 'nullable|string|max:1000',
         'addToWaitlist' => 'nullable|boolean', // Add validation for waitlist field
     ]);
 
     // Check if the slot is already booked
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
 
     // Create the appointment
     $appointment = Appointment::create([
         'patient_id' => $validated['patient_id'], // Use the provided patient_id
         'doctor_id' => $validated['doctor_id'],
         'appointment_date' => $validated['appointment_date'],
         'appointment_time' => $validated['appointment_time'],
         'add_to_waitlist' => $validated['addToWaitlist'] ?? false, // Default to false if not provided
         'notes' => $validated['description'] ?? null,
         'status' => 0,  // Default status (e.g., pending)
         'created_by' => 1, // Assuming created_by is the user ID
     ]);
 
     // Check if the appointment should be added to the waitlist
     if ($validated['addToWaitlist'] ?? false) {
         // Fetch specialization_id from the doctor (assuming it's stored in the Doctor model)
         $doctor = Doctor::find($validated['doctor_id']);
         $specialization_id = $doctor->specialization_id;
 
         // Create a waitlist entry
         WaitList::create([
             'doctor_id' => $validated['doctor_id'],
             'patient_id' => $validated['patient_id'], // Use the provided patient_id
             'is_Daily' => false,
             'specialization_id' => $specialization_id,
             'importance' =>  null, // Use importance from the request
             'appointmentId' => $appointment->id ?? null, // Use importance from the request
             'notes' => $validated['description'] ?? null, // Use notes from the appointment
             'created_by' => 1, // Assuming created_by is the user ID
         ]);
     }
 
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
    
        // Get excluded dates for the specific doctor and for all doctors
        $excludedDates = ExcludedDate::where(function ($query) use ($doctorId) {
            $query->where('doctor_id', $doctorId) // Excluded dates for this doctor
                  ->orWhereNull('doctor_id'); // Excluded dates for all doctors
        })->get();
    
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
            // Check if the date is excluded
            $isExcluded = $excludedDates->contains(function ($excludedDate) use ($date) {
                return Carbon::parse($date)->between(
                    $excludedDate->start_date,
                    $excludedDate->end_date
                );
            });
    
            // If the date is excluded, skip it
            if ($isExcluded) {
                continue;
            }
    
            // Check if the month is available
            $month = Carbon::parse($date)->month;
            $isMonthAvailable = $this->isMonthAvailable($doctorId, $month);
    
            // If the month is not available, skip this date
            if (!$isMonthAvailable) {
                continue;
            }
    
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
    
        // Find the closest available non-canceled appointment, excluding excluded dates and unavailable months
        $closestAvailableAppointment = $this->findClosestAvailableAppointment($doctorId, $excludedDates);
    
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
    
    private function findClosestAvailableAppointment($doctorId, $excludedDates)
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
    
        // Check if today is excluded
        $isTodayExcluded = $excludedDates->contains(function ($excludedDate) use ($currentDate) {
            return $currentDate->between($excludedDate->start_date, $excludedDate->end_date);
        });
    
        // Check if the current month is available
        $currentMonth = $currentDate->month;
        $isCurrentMonthAvailable = $this->isMonthAvailable($doctorId, $currentMonth);
    
        if (!$isTodayExcluded && $isCurrentMonthAvailable) {
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
        }
    
        // If no available slots today, check future days
        for ($i = 1; $i < 30; $i++) { // Limit to 30 days
            $date = $currentDate->copy()->addDays($i)->format('Y-m-d');
    
            // Check if the date is excluded
            $isDateExcluded = $excludedDates->contains(function ($excludedDate) use ($date) {
                return Carbon::parse($date)->between($excludedDate->start_date, $excludedDate->end_date);
            });
    
            if ($isDateExcluded) {
                continue; // Skip this date
            }
    
            // Check if the month is available
            $month = Carbon::parse($date)->month;
            $isMonthAvailable = $this->isMonthAvailable($doctorId, $month);
    
            if (!$isMonthAvailable) {
                continue; // Skip this month
            }
    
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

    private function isMonthAvailable($doctorId, $month)
    {
        // Get the current year and month
        $currentYear = Carbon::now()->year;
        $currentMonth = Carbon::now()->month;
    
        // If the month has already passed this year, return false (do not look into the next year)
        if ($month < $currentMonth) {
            return false;
        }
    
        // Check if the month is available in the AppointmentAvailableMonth model for the current year
        return AppointmentAvailableMonth::where('doctor_id', $doctorId)
            ->where('month', $month)
            ->where('is_available', true)
            ->exists();
    }
    
    
    public function changeAppointmentStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|integer|in:' . implode(',', array_column(AppointmentSatatusEnum::cases(), 'value')),
            'reason' => 'nullable|string|required_if:status,2|max:255'
        ]);
    
        $appointment = Appointment::findOrFail($id);
    
        $appointment->status = $validated['status'];
        
        // Add reason only if status is 2 and reason is provided
        if ($validated['status'] == 2 && isset($validated['reason'])) {
            $appointment->reason = $validated['reason'];
        }
    
        $appointment->save();
    
        return response()->json([
            'message' => 'Appointment status updated successfully.',
            'appointment' => new AppointmentResource($appointment),
        ]);
    }
    public function destroy($appointmentid)
    {
        $appointment = Appointment::findOrFail($appointmentid);
        $appointment->delete();

        return response()->json([
            'message' => 'Appointment deleted successfully.'
        ]);
    }

    public function update(Request $request, $id)
    {
        // Validate the request data
        $validated = $request->validate([
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'phone' => 'sometimes|required|string|max:20',
            'doctor_id' => 'sometimes|required',
            'appointment_date' => 'sometimes|required|date_format:Y-m-d',
            'appointment_time' => 'sometimes|required|date_format:H:i',
            'description' => 'nullable|string|max:1000',
            'addToWaitlist' => 'nullable|boolean', // Add validation for waitlist field
            'importance' => 'nullable|integer',   // Add validation for importance field
        ]);
    
        // Find the appointment by ID
        $appointment = Appointment::findOrFail($id);
    
        // Check if the time slot is already booked (excluding the current appointment)
        $excludedStatuses = [
            AppointmentSatatusEnum::CANCELED->value, // Add CANCELED here
        ];
    
        $existingAppointment = Appointment::where('doctor_id', $request->doctor_id ?? $appointment->doctor_id)
            ->whereDate('appointment_date', $request->appointment_date ?? $appointment->appointment_date)
            ->where('appointment_time', $request->appointment_time ?? $appointment->appointment_time)
            ->whereNotIn('status', $excludedStatuses)
            ->where('id', '!=', $appointment->id) // Exclude the current appointment
            ->exists();
    
        if ($existingAppointment) {
            return response()->json([
                'message' => 'This time slot is already booked.',
                'errors' => ['appointment_time' => ['The selected time slot is no longer available.']]
            ], 422);
        }
    
        // Update the patient details
        $patient = Patient::findOrFail($appointment->patient_id);
        $patient->update([
            'Firstname' => $validated['first_name'] ?? $patient->Firstname,
            'Lastname' => $validated['last_name'] ?? $patient->Lastname,
            'phone' => $validated['phone'] ?? $patient->phone,
        ]);
    
        // Update the appointment details
        $appointment->update([
            'doctor_id' => $validated['doctor_id'] ?? $appointment->doctor_id,
            'appointment_date' => $validated['appointment_date'] ?? $appointment->appointment_date,
            'appointment_time' => $validated['appointment_time'] ?? $appointment->appointment_time,
            'add_to_waitlist' => $validated['addToWaitlist'] ?? $appointment->add_to_waitlist,
            'notes' => $validated['description'] ?? $appointment->notes,
        ]);
    
        // Handle waitlist logic
        if (isset($validated['addToWaitlist'])) {
            if ($validated['addToWaitlist']) {
                // Fetch specialization_id from the doctor (assuming it's stored in the Doctor model)
                $doctor = Doctor::find($validated['doctor_id'] ?? $appointment->doctor_id);
                $specialization_id = $doctor->specialization_id;
    
                // Create or update the waitlist entry
                WaitList::updateOrCreate(
                    [
                        'doctor_id' => $validated['doctor_id'] ?? $appointment->doctor_id,
                        'patient_id' => $patient->id,
                    ],
                    [
                        'specialization_id' => $specialization_id,
                        'importance' => $validated['importance'] ?? null, // Use importance from the request
                        'notes' => $validated['description'] ?? null, // Use notes from the appointment
                        'created_by' => 1, // Assuming created_by is the user ID
                    ]
                );
            } else {
                // Remove the appointment from the waitlist if addToWaitlist is false
                WaitList::where('doctor_id', $validated['doctor_id'] ?? $appointment->doctor_id)
                    ->where('patient_id', $patient->id)
                    ->delete();
            }
        }
    
        return new AppointmentResource($appointment);
    }

}
