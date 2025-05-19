<?php

namespace App\Http\Controllers;

use App\AppointmentSatatusEnum;
use App\DayOfWeekEnum;
use App\Http\Resources\ExcludedDateResource;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\ExcludedDate;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ExcludedDates extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index($doctorId)
    {

        $query = ExcludedDate::with('doctor.user')
        ->where('doctor_id', $doctorId);

    // If the user is an admin, include general excluded dates
    if (Auth::user()->role === 'admin' || Auth::user()->role === 'SuperAdmin') {
        $query->orWhereNull('doctor_id');
    }

    $excludedDates = $query->get();
            return new ExcludedDateResource($excludedDates);
        }
    
    

    
    
    
    public function GetExcludedDates($doctorId)
    {

        // Fetch all excluded date ranges and eager load the 'doctor' relationship
        $excludedDates = ExcludedDate::with('doctor')  // Eager load the 'doctor' relationship
            ->get();
    
        return ExcludedDateResource::collection($excludedDates);
    }
    
    public function store(Request $request)
    {
        // Validate the request
        $request->validate([
            'doctor_id' => 'nullable|exists:doctors,id',
            'start_date' => 'required',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'reason' => 'nullable|string',
            'apply_for_all_years' => 'nullable|boolean',
            'exclusionType' => 'required|in:complete,limited',
            'morning_start_time' => 'nullable',
            'morning_end_time' => 'nullable|date_format:H:i|after:morning_start_time',
            'morning_patients' => 'nullable|integer|min:0',
            'is_morning_active' => 'nullable|boolean',
            'afternoon_start_time' => 'nullable|date_format:H:i',
            'afternoon_end_time' => 'nullable|date_format:H:i|after:afternoon_start_time',
            'afternoon_patients' => 'nullable|integer|min:0',
            'is_afternoon_active' => 'nullable|boolean',
            'shift_period' => 'nullable|string',
        ]);
    
        // Prepare data based on exclusion type
        $data = [
            'doctor_id' => $request->doctor_id ?? null,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'reason' => $request->reason,
            'exclusionType' => $request->exclusionType,
            'apply_for_all_years' => $request->apply_for_all_years ?? false,
            'is_active' => true,
            'created_by' => auth()->id() ?? null,
        ];
    
        if ($request->exclusionType == 'complete') {
            $data['start_time'] = null;
            $data['end_time'] = null;
            $data['number_of_patients_per_day'] = 0;
            $data['shift_period'] = null;
            
            $excludedDate = ExcludedDate::create($data);
            
            return response()->json([
                'message' => 'Excluded date range created successfully.',
                'data' => $excludedDate
            ], 201);
        } else {
            $createdEntries = [];
            
            // Handle morning shift
          // Modified portion for handling morning shift in the store method
if ($request->is_morning_active) {
    $morningStartTime = $request->morning_start_time;
    $morningEndTime = $request->morning_end_time;
    $morningPatients = $request->morning_patients;
    // If times are empty but patients count exists, get times from schedule
    if (($morningStartTime === null || $morningEndTime === null) && $morningPatients !== null) {
        $dateObj = Carbon::parse($request->start_date);
        $dayOfWeek = DayOfWeekEnum::cases()[$dateObj->dayOfWeek]->value;

        $morningSchedule = Schedule::select('start_time', 'end_time')
            ->where('doctor_id', $request->doctor_id)
            ->where('is_active', true)
            ->where('day_of_week', $dayOfWeek)
            ->where('shift_period', 'morning')
            ->first();
            
        if ($morningSchedule) {
            $morningStartTime = $morningStartTime ?? $morningSchedule->start_time;
            $morningEndTime = $morningEndTime ?? $morningSchedule->end_time;
        } else {
            return response()->json([
                'message' => "No schedule found for this doctor on " . $dateObj->format('l') . " morning.",
                'data' => null
            ], 400);
        }
    }
    
    $morningData = array_merge($data, [
        'start_time' => $morningStartTime,
        'end_time' => $morningEndTime,
        'number_of_patients_per_day' => $morningPatients,
        'shift_period' => 'morning',
    ]);
    
    $morningExcludedDate = ExcludedDate::create($morningData);
    $createdEntries['morning'] = $morningExcludedDate;
    
    // Reschedule morning appointments
    if ($request->end_date) {
        $currentDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);
        
        while ($currentDate->lte($endDate)) {
            $this->updateFutureAppointments(
                $request->doctor_id,
                $morningStartTime,
                $morningEndTime,
                $morningPatients,
                $currentDate->format('Y-m-d'),
                null,
                'morning'
            );
            $currentDate->addDay();
        }
    } else {
        $this->updateFutureAppointments(
            $request->doctor_id,
            $morningStartTime,
            $morningEndTime,
            $morningPatients,
            $request->start_date,
            null,
            'morning'
        );
    }
}

// Handle afternoon shift
if ($request->is_afternoon_active) {
    $afternoonStartTime = $request->afternoon_start_time;
    $afternoonEndTime = $request->afternoon_end_time;
    $afternoonPatients = $request->afternoon_patients;
    
    // If times are empty but patients count exists, get times from schedule
    if (($afternoonStartTime === null || $afternoonEndTime === null) && $afternoonPatients !== null) {
        $dateObj = Carbon::parse($request->start_date);
        $dayOfWeek = DayOfWeekEnum::cases()[$dateObj->dayOfWeek]->value;
        
        $afternoonSchedule = Schedule::select('start_time', 'end_time')
            ->where('doctor_id', $request->doctor_id)
            ->where('is_active', true)
            ->where('day_of_week', $dayOfWeek)
            ->where('shift_period', 'afternoon')
            ->first();
            
        if ($afternoonSchedule) {
            $afternoonStartTime = $afternoonStartTime ?? $afternoonSchedule->start_time;
            $afternoonEndTime = $afternoonEndTime ?? $afternoonSchedule->end_time;
        } else {
            return response()->json([
                'message' => "No schedule found for this doctor on " . $dateObj->format('l') . " afternoon.",
                'data' => null
            ], 400);
        }
    }
    
    $afternoonData = array_merge($data, [
        'start_time' => $afternoonStartTime,
        'end_time' => $afternoonEndTime,
        'number_of_patients_per_day' => $afternoonPatients,
        'shift_period' => 'afternoon',
    ]);
    
    $afternoonExcludedDate = ExcludedDate::create($afternoonData);
    $createdEntries['afternoon'] = $afternoonExcludedDate;
    
    // Reschedule afternoon appointments
    if ($request->end_date) {
        $currentDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);
        
        while ($currentDate->lte($endDate)) {
            $this->updateFutureAppointments(
                $request->doctor_id,
                $afternoonStartTime,
                $afternoonEndTime,
                $afternoonPatients,
                $currentDate->format('Y-m-d'),
                null,
                'afternoon'
            );
            $currentDate->addDay();
        }
    } else {
        $this->updateFutureAppointments(
            $request->doctor_id,
            $afternoonStartTime,
            $afternoonEndTime,
            $afternoonPatients,
            $request->start_date,
            null,
            'afternoon'
        );
    }
}
        }
    }
    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // Validate the request
        $request->validate([
            'doctor_id' => 'nullable|exists:doctors,id',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'reason' => 'nullable|string',
            'apply_for_all_years' => 'nullable|boolean',
            'exclusionType' => 'required|in:complete,limited',
            'morning_start_time' => 'nullable|date_format:H:i',
            'morning_end_time' => 'nullable|date_format:H:i|after:morning_start_time',
            'morning_patients' => 'nullable|integer|min:0',
            'is_morning_active' => 'nullable|boolean',
            'afternoon_start_time' => 'nullable|date_format:H:i',
            'afternoon_end_time' => 'nullable|date_format:H:i|after:afternoon_start_time',
            'afternoon_patients' => 'nullable|integer|min:0',
            'is_afternoon_active' => 'nullable|boolean',
        ]);
        
        // Delete previous entries for this doctor and date
        $delete = ExcludedDate::where('start_date', $request->start_date)
            ->where('doctor_id', $request->doctor_id)
            ->delete();
        
        // Common data
        $data = [
            'doctor_id' => $request->doctor_id,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'reason' => $request->reason,
            'exclusionType' => $request->exclusionType,
            'apply_for_all_years' => $request->apply_for_all_years ?? false,
            'is_active' => true,
            'created_by' => auth()->id(),
        ];
        
        if ($request->exclusionType === 'complete') {
            // Create a new complete exclusion record
            ExcludedDate::create(array_merge($data, [
                'start_time' => null,
                'end_time' => null,
                'number_of_patients_per_day' => 0,
                'shift_period' => null,
            ]));
            
            return response()->json([
                'message' => 'Excluded date marked as completely unavailable.',
            ], 200);
        }
        
        // For limited exclusion type, create new morning and/or afternoon records
        $updatedExcludedDates = [];
        
        if ($request->is_morning_active) {
            $morningStartTime = $request->morning_start_time;
            $morningEndTime = $request->morning_end_time;
            $morningPatients = $request->morning_patients;
            
            // If times are empty but patients count exists, get times from schedule
            if (($morningStartTime === null || $morningEndTime === null) && $morningPatients !== null) {
                $dateObj = Carbon::parse($request->start_date);
                $dayOfWeek = DayOfWeekEnum::cases()[$dateObj->dayOfWeek]->value;
                
                $morningSchedule = Schedule::select('start_time', 'end_time')
                    ->where('doctor_id', $request->doctor_id)
                    ->where('is_active', true)
                    ->where('day_of_week', $dayOfWeek)
                    ->where('shift_period', 'morning')
                    ->first();
                    
                if ($morningSchedule) {
                    $morningStartTime = $morningStartTime ?? $morningSchedule->start_time;
                    $morningEndTime = $morningEndTime ?? $morningSchedule->end_time;
                } else {
                    return response()->json([
                        'message' => "No schedule found for this doctor on " . $dateObj->format('l') . " morning.",
                        'data' => null
                    ], 400);
                }
            }
            
            $updatedExcludedDates['morning'] = ExcludedDate::create(array_merge($data, [
                'start_time' => $morningStartTime,
                'end_time' => $morningEndTime,
                'number_of_patients_per_day' => $morningPatients,
                'shift_period' => 'morning',
            ]));
            
            // If end_date exists, also update future appointments for the date range
            if ($request->end_date) {
                $currentDate = Carbon::parse($request->start_date);
                $endDate = Carbon::parse($request->end_date);
                
                while ($currentDate->lte($endDate)) {
                    $this->updateFutureAppointments(
                        $request->doctor_id,
                        $morningStartTime,
                        $morningEndTime,
                        $morningPatients,
                        $currentDate->format('Y-m-d'),
                        null,
                        'morning'
                    );
                    $currentDate->addDay();
                }
            } else {
                $this->updateFutureAppointments(
                    $request->doctor_id,
                    $morningStartTime,
                    $morningEndTime,
                    $morningPatients,
                    $request->start_date,
                    null,
                    'morning'
                );
            }
        }
        
        if ($request->is_afternoon_active) {
            $afternoonStartTime = $request->afternoon_start_time;
            $afternoonEndTime = $request->afternoon_end_time;
            $afternoonPatients = $request->afternoon_patients;
            
            // If times are empty but patients count exists, get times from schedule
            if (($afternoonStartTime === null || $afternoonEndTime === null) && $afternoonPatients !== null) {
                $dateObj = Carbon::parse($request->start_date);
                $dayOfWeek = DayOfWeekEnum::cases()[$dateObj->dayOfWeek]->value;
                
                $afternoonSchedule = Schedule::select('start_time', 'end_time')
                    ->where('doctor_id', $request->doctor_id)
                    ->where('is_active', true)
                    ->where('day_of_week', $dayOfWeek)
                    ->where('shift_period', 'afternoon')
                    ->first();
                    
                if ($afternoonSchedule) {
                    $afternoonStartTime = $afternoonStartTime ?? $afternoonSchedule->start_time;
                    $afternoonEndTime = $afternoonEndTime ?? $afternoonSchedule->end_time;
                } else {
                    return response()->json([
                        'message' => "No schedule found for this doctor on " . $dateObj->format('l') . " afternoon.",
                        'data' => null
                    ], 400);
                }
            }
            
            $updatedExcludedDates['afternoon'] = ExcludedDate::create(array_merge($data, [
                'start_time' => $afternoonStartTime,
                'end_time' => $afternoonEndTime,
                'number_of_patients_per_day' => $afternoonPatients,
                'shift_period' => 'afternoon',
            ]));
            
            // If end_date exists, also update future appointments for the date range
            if ($request->end_date) {
                $currentDate = Carbon::parse($request->start_date);
                $endDate = Carbon::parse($request->end_date);
                
                while ($currentDate->lte($endDate)) {
                    $this->updateFutureAppointments(
                        $request->doctor_id,
                        $afternoonStartTime,
                        $afternoonEndTime,
                        $afternoonPatients,
                        $currentDate->format('Y-m-d'),
                        null,
                        'afternoon'
                    );
                    $currentDate->addDay();
                }
            } else {
                $this->updateFutureAppointments(
                    $request->doctor_id,
                    $afternoonStartTime,
                    $afternoonEndTime,
                    $afternoonPatients,
                    $request->start_date,
                    null,
                    'afternoon'
                );
            }
        }
        
        // If no records were created, return appropriate message
        if (empty($updatedExcludedDates)) {
            return response()->json([
                'message' => 'No valid session data provided.',
                'data' => null
            ], 400);
        }
        
        return response()->json([
            'message' => 'Excluded date updated successfully.',
            'data' => $updatedExcludedDates,
        ], 200);
    }
    
    

    private function updateFutureAppointments(
        int $doctorId,
        string $startTime,
        string $endTime,
        int $newCapacity,
        ?string $specificDate = null,
        ?string $dayOfWeek = null,
        ?string $shiftPeriod = null
    ): void {
        try {
            DB::beginTransaction();
            
            // Build the query for future appointments
            $query = Appointment::where('doctor_id', $doctorId)
                ->where('appointment_date', '>=', Carbon::today())
                ->where('status', '!=', AppointmentSatatusEnum::CANCELED->value);
                
            // Filter by specific date or day of week
            if ($specificDate) {
                $query->whereDate('appointment_date', $specificDate);
            } elseif ($dayOfWeek) {
                $query->whereRaw('LOWER(DAYNAME(appointment_date)) = ?', [strtolower($dayOfWeek)]);
            }
            
            // Get all affected dates
            $affectedDates = $query->clone()
                ->select('appointment_date')
                ->distinct()
                ->orderBy('appointment_date')
                ->pluck('appointment_date');
            
            // Process each affected date
            foreach ($affectedDates as $date) {
                // Get appointments for this date, ordered by their creation date (oldest first)
                $dateAppointments = $query->clone()
                    ->whereDate('appointment_date', $date)
                    ->orderBy('created_at', 'asc')
                    ->get();
                
                if ($dateAppointments->isEmpty()) {
                    continue;
                }
                
                // Calculate new time slots based on the new parameters
                $slots = $this->getDoctorWorkingHours($doctorId, $date, $startTime, $endTime, $newCapacity, $shiftPeriod);
                if (empty($slots)) {
                    continue; // No slots available for this date
                }
                // Assign appointments to slots in order (first booked appointment gets first slot)
                foreach ($dateAppointments as $index => $appointment) {
                   // Handle excess appointments (more than available slots)
    if ($index < count($slots)) {
        // Assign the current slot to this appointment
        $appointment->update([
            'appointment_time' => $slots[$index]
        ]);
    } else {
        // Add a gap slot after the last appointment
        $lastSlot = end($slots);
        $lastSlotCarbon = Carbon::parse($date . ' ' . $lastSlot);
        
        // Calculate the new time (adding your preferred gap duration)
        // Assuming your appointments have a fixed duration, adjust this as needed
        $appointmentDuration = 15; // in minutes, adjust based on your system
        $newSlotTime = $lastSlotCarbon->addMinutes($appointmentDuration)->format('H:i:s');
        
        // Update the appointment with the new gap slot time
        $appointment->update([
            'appointment_time' => $newSlotTime
        ]);
        
        // Add this new slot to the slots array so it becomes the "last slot" for the next overflow appointment
        $slots[] = $newSlotTime;
    }
                }
            }
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update future appointments: ' . $e->getMessage());
            throw $e;
        }
    }
    public function getDoctorWorkingHours(
        int $doctorId, 
        string $date,
        string $startTime = null,
        string $endTime = null,
        int $numberOfPatients = null,
        ?string $shiftPeriod = null
    ) {
        // Parse the date parameter into a Carbon instance
        $dateObj = Carbon::parse($date);
        $dayOfWeek = DayOfWeekEnum::cases()[$dateObj->dayOfWeek]->value;
    
        // Get excluded dates schedules
        $excludedSchedules = ExcludedDate::select('start_time', 'end_time', 'number_of_patients_per_day', 'shift_period')
            ->where('doctor_id', $doctorId)
            ->where('exclusionType', 'limited')
            ->where(function ($query) use ($date) {
                $query->where('start_date', '<=', $date)
                      ->where(function ($q) use ($date) {
                          $q->whereNull('end_date')
                            ->orWhere('end_date', '>=', $date);
                      });
            })
            ->where('is_active', true)
            ->get();
    
        // If no excluded dates exist for this date, get the regular schedule
        $schedules = null;
        if ($excludedSchedules->isEmpty()) {
            $schedules = Schedule::select('start_time', 'end_time', 'number_of_patients_per_day', 'shift_period')
                ->where('doctor_id', $doctorId)
                ->where('is_active', true)
                ->where('day_of_week', $dayOfWeek)
                ->get();
        } else {
            // Use the excluded schedules
            $schedules = $excludedSchedules;
        }
    
        $doctor = Doctor::find($doctorId, ['patients_based_on_time', 'time_slot']);
    
        if (!$doctor || $schedules->isEmpty()) {
            return [];
        }
        
        $workingHours = [];
        
        // Get current date and time
        $now = Carbon::now();
        
        // Check if the requested date is today
        $isToday = $dateObj->isSameDay($now);
        
        // Create buffer time (current time + 5 minutes)
        $bufferTime = $now->copy()->addMinutes(5);
        
        // If no specific shift period is provided, get schedules for the day
        if (!$shiftPeriod || !$startTime || !$endTime || $numberOfPatients === null) {
            // If doctor has a fixed time slot, calculate based on that
            if ($doctor->time_slot !== null && (int)$doctor->time_slot > 0) {
                $timeSlotMinutes = (int)$doctor->time_slot;
                
                foreach (['morning', 'afternoon'] as $shift) {
                    $schedule = $schedules->firstWhere('shift_period', $shift);
                    if (!$schedule) continue;
                    
                    // Create full datetime objects for start and end times
                    $startTime = Carbon::parse($date . ' ' . $schedule->start_time);
                    $endTime = Carbon::parse($date . ' ' . $schedule->end_time);
                    $slotTime = clone $startTime;
                    
                    while ($slotTime < $endTime) {
                        // Only add if not today OR (is today AND time is after buffer)
                        if (!$isToday || $slotTime->greaterThan($bufferTime)) {
                            $workingHours[] = $slotTime->format('H:i');
                        }
                        $slotTime = $slotTime->copy()->addMinutes($timeSlotMinutes);
                    }
                }
            } else {
                // Calculate based on number of patients per shift
                foreach (['morning', 'afternoon'] as $shift) {
                    $schedule = $schedules->firstWhere('shift_period', $shift);
                    if (!$schedule) continue;
                    
                    // Create full datetime objects
                    $startTime = Carbon::parse($date . ' ' . $schedule->start_time);
                    $endTime = Carbon::parse($date . ' ' . $schedule->end_time);
                    
                    // Get number of patients for this specific shift
                    $patientsForShift = (int)$schedule->number_of_patients_per_day;
                    
                    if ($patientsForShift <= 0) continue;
                    
                    if ($patientsForShift == 1) {
                        // Special case: just one slot at start time
                        if (!$isToday || $startTime->greaterThan($bufferTime)) {
                            $workingHours[] = $startTime->format('H:i');
                        }
                        continue;
                    }
                    
                    // Calculate total duration in minutes
                    $totalDuration = abs($endTime->diffInMinutes($startTime));
                    
                    // Calculate the gap between each appointment - matching first function's logic
                    $gap = $totalDuration / ($patientsForShift - 1);
                    
                    // Generate slots
                    for ($i = 0; $i < $patientsForShift; $i++) {
                        $minutesToAdd = round($i * $gap);
                        $slotTime = $startTime->copy()->addMinutes($minutesToAdd);
                        
                        // Only add if not today OR (is today AND time is after buffer)
                        if (!$isToday || $slotTime->greaterThan($bufferTime)) {
                            $workingHours[] = $slotTime->format('H:i');
                        }
                    }
                }
            }
        } else {
            // We're processing a specific shift with the provided parameters
            if ($doctor->time_slot !== null && (int)$doctor->time_slot > 0 && $doctor->patients_based_on_time) {
                $timeSlotMinutes = (int)$doctor->time_slot;
                
                $startTimeCopy = Carbon::parse($date . ' ' . $startTime);
                $endTimeCopy = Carbon::parse($date . ' ' . $endTime);
                $currentTime = clone $startTimeCopy;
                
                while ($currentTime < $endTimeCopy) {
                    // Only add if not today OR (is today AND time is after buffer)
                    if (!$isToday || $currentTime->greaterThan($bufferTime)) {
                        $workingHours[] = $currentTime->format('H:i');
                    }
                    $currentTime->addMinutes($timeSlotMinutes);
                }
            } else {
                // Calculate based on number of patients
                $startTimeCopy = Carbon::parse($date . ' ' . $startTime);
                $endTimeCopy = Carbon::parse($date . ' ' . $endTime);
                
                if ($numberOfPatients <= 0) {
                    return [];
                }
                
                // Calculate total duration in minutes
                $totalDuration = abs($endTimeCopy->diffInMinutes($startTimeCopy));
                
                if ($numberOfPatients > 1) {
                    // Calculate the gap between each appointment - matching first function's logic
                    // We subtract 1 from patients because we need one less gap than appointments
                    $gap = $totalDuration / ($numberOfPatients - 1);
                    $totalDuration = $totalDuration - $gap;
                    $gap = $totalDuration / ($numberOfPatients - 1);
                    
                    // Generate slots
                    for ($i = 0; $i < $numberOfPatients; $i++) {
                        $minutesToAdd = round($i * $gap);
                        $slotTime = (clone $startTimeCopy)->addMinutes($minutesToAdd);
                        
                        // Only add if not today OR (is today AND time is after buffer)
                        if (!$isToday || $slotTime->greaterThan($bufferTime)) {
                            $workingHours[] = $slotTime->format('H:i');
                        }
                    }
                } else {
                    // Handle case with just one patient
                    if (!$isToday || $startTimeCopy->greaterThan($bufferTime)) {
                        $workingHours[] = $startTimeCopy->format('H:i');
                    }
                }
            }
        }
        
        return array_unique($workingHours);
    }
    /**
     * Remove the specified resource from storage.
     */
   // In your controller
   public function destroyByDate(Request $request)
{
     // Check in both request body and query parameters
     $date = $request->input('date', $request->query('date'));
     $doctorId = $request->input('doctor_id', $request->query('doctor_id'));
     
    //  dd($date, $doctorId);
     if (!$date) {
         return response()->json(['message' => 'Date is required'], 400);
     }
    
   
    
    // Create a query builder
    $query = ExcludedDate::whereDate('start_date', $date);
    // If doctor_id is provided, add it to the query
    if ($doctorId) {
        $query->where('doctor_id', $doctorId);
    }
    
    
    // Delete all records matching the query
    $deleted = $query->delete();
    
    if ($deleted > 0) {
        return response()->json(['message' => 'Excluded date records deleted successfully.']);
    } else {
        return response()->json(['message' => 'No records found to delete'], 404);
    }
}
}