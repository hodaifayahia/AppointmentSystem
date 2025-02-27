<?php

namespace App\Http\Controllers;

use App\AppointmentBookingWindow;
use App\AppointmentSatatusEnum;
use App\DayOfWeekEnum;
use App\Http\Resources\DoctorResource;
use App\Models\Appointment;
use App\Models\AppointmentAvailableMonth;
use App\Models\Doctor;
use App\Models\ExcludedDate;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class DoctorController extends Controller
{
    public function index(Request $request)
    {
        // Get the filter query parameter
        $filter = $request->query('query');
        $doctorId = $request->query('doctor_id'); // Get doctorId from query parameter

        // Base query for doctors
        $doctorsQuery = Doctor::whereHas('user', function ($query) {
            $query->where('role', 'doctor');
        })
        ->with(['user', 'specialization', 'schedules', 'appointmentAvailableMonths']); // Eager load relationships

        // Apply filter if provided
        if ($filter) {
            $doctorsQuery->where('specialization_id', $filter);
        }

        // Apply filter by doctorId if provided
        if ($doctorId) {
            $doctorsQuery->where('id', $doctorId); 
        }

        // Paginate the results
        $doctors = $doctorsQuery->paginate();

        // Return the paginated results as a collection of DoctorResource
        return DoctorResource::collection($doctors);
    }
    
    public function WorkingDates(Request $request)
    {
        try {
            $validated = $request->validate([
                'doctorId' => 'nullable|exists:doctors,id',
                'month' => 'required|date_format:Y-m',
            ]);

            $startDate = Carbon::createFromFormat('Y-m', $validated['month'])->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();

            // Modified query to ensure we get doctor name and specialization
            $doctorsQuery = Doctor::query()
                ->select([
                    'doctors.id',
                    'doctors.specialization_id',
                    'users.name as doctor_name',  // Changed from users.name to doctor_name alias
                    'specializations.name as specialization_name'  // Changed from specializations.name to specialization_name alias
                ])
                ->join('users', 'doctors.user_id', '=', 'users.id')
                ->join('specializations', 'doctors.specialization_id', '=', 'specializations.id')
                ->where('users.role', 'doctor');

            if (isset($validated['doctorId'])) {
                $doctorsQuery->where('doctors.id', $validated['doctorId']);
            }

            $doctors = $doctorsQuery->get();

            if ($doctors->isEmpty()) {
                return response()->json([
                    'data' => [],
                    'month' => $validated['month'],
                    'total_doctors' => 0
                ]);
            }

            $doctorIds = $doctors->pluck('id')->toArray();
            
            // Get active schedules
            $schedules = DB::table('schedules')
                ->select(['doctor_id', 'date', 'day_of_week', 'is_active'])
                ->where('is_active', true)
                ->where(function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('date', [$startDate, $endDate])
                      ->orWhereNull('date');
                })
                ->whereIn('doctor_id', $doctorIds)
                ->get();

            // Get appointments
            $appointments = DB::table('appointments')
                ->select(['doctor_id', 'appointment_date'])
                ->whereIn('doctor_id', $doctorIds)
                ->whereBetween('appointment_date', [$startDate, $endDate])
                ->where('status', '!=', AppointmentSatatusEnum::CANCELED->value)
                ->distinct()
                ->get();

            // Get excluded dates
            $excludedDates = ExcludedDate::where(function ($query) use ($doctorIds) {
                $query->whereIn('doctor_id', $doctorIds)
                      ->orWhereNull('doctor_id');
            })
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate, $endDate])
                      ->orWhereBetween('end_date', [$startDate, $endDate])
                      ->orWhere(function ($q) use ($startDate, $endDate) {
                          $q->where('start_date', '<=', $startDate)
                            ->where('end_date', '>=', $endDate);
                      });
            })
            ->get();

            // Get month availability
            $monthAvailability = AppointmentAvailableMonth::whereIn('doctor_id', $doctorIds)
                ->where('month', $startDate->month)
                ->where('year', $startDate->year)
                ->where('is_available', true)
                ->pluck('doctor_id')
                ->toArray();

            $result = $doctors->map(function ($doctor) use ($schedules, $appointments, $excludedDates, $monthAvailability, $startDate, $endDate) {
                // Ensure we have all required properties
                if (!$doctor->doctor_name || !$doctor->specialization_name) {
                    \Log::error('Missing doctor data', ['doctor' => $doctor]);
                    return null;
                }

                if (!in_array($doctor->id, $monthAvailability)) {
                    return [
                        'id' => $doctor->id,
                        'name' => $doctor->doctor_name,
                        'specialization' => $doctor->specialization_name,
                        'working_dates' => [],
                    ];
                }

                $doctorSchedules = $schedules->where('doctor_id', $doctor->id);
                $doctorAppointments = $appointments->where('doctor_id', $doctor->id);
                
                $workingDates = $this->calculateWorkingDatesOptimized(
                    $doctor->id,
                    $doctorSchedules,
                    $doctorAppointments,
                    $excludedDates,
                    $startDate,
                    $endDate
                );

                return [
                    'id' => $doctor->id,
                    'name' => $doctor->doctor_name,
                    'specialization' => $doctor->specialization_name,
                    'working_dates' => array_values(array_unique($workingDates)),
                ];
            })->filter(); // Remove any null values

            return response()->json([
                'data' => $result,
                'month' => $validated['month'],
                'total_doctors' => $result->count()
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in WorkingDates', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Error fetching working dates',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function calculateWorkingDatesOptimized($doctorId, $schedules, $appointments, $excludedDates, $startDate, $endDate)
    {
        $workingDates = [];
        $currentDate = $startDate->copy();

        $doctorExcludedDates = $excludedDates->filter(function ($date) use ($doctorId) {
            return $date->doctor_id === $doctorId || $date->doctor_id === null;
        });

        while ($currentDate <= $endDate) {
            $dateStr = $currentDate->format('Y-m-d');
            
            $isExcluded = $doctorExcludedDates->contains(function ($excludedDate) use ($currentDate) {
                $endDate = $excludedDate->end_date ?? $excludedDate->start_date;
                return $currentDate->between($excludedDate->start_date, $endDate);
            });

            if (!$isExcluded) {
                $hasSpecificSchedule = $schedules->contains(function ($schedule) use ($dateStr) {
                    return $schedule->date === $dateStr && $schedule->is_active;
                });

                $hasRecurringSchedule = $schedules->contains(function ($schedule) use ($currentDate) {
                    return $schedule->day_of_week === $currentDate->dayOfWeek 
                           && $schedule->date === null 
                           && $schedule->is_active;
                });

                if ($hasSpecificSchedule || $hasRecurringSchedule) {
                    $workingDates[] = $dateStr;
                }
            }

            $currentDate->addDay();
        }

        // Add appointment dates
        $appointmentDates = $appointments->map(function ($appointment) {
            return Carbon::parse($appointment->appointment_date)->format('Y-m-d');
        })->toArray();

        return array_merge($workingDates, $appointmentDates);
    }


     public function getspecific()
     {

         try {
             // Ensure the authenticated user has a doctor profile
             if (!Auth::user() || !Auth::user()->doctor) {
                 return response()->json(['message' => 'Unauthorized'], 401);
             }
     
             $doctor = Doctor::with(['user', 'specialization', 'schedules'])
                 ->where('id', Auth::user()->doctor->id)
                 ->firstOrFail(); // Automatically returns 404 if doctor is not found
     
             return new DoctorResource($doctor);
         } catch (\Exception $e) {
             return response()->json(['message' => 'An error occurred'], 500);
         }
     }
     
    public function getDoctor(Request $request, $id = null)
{
    // If an ID is provided, return the specific doctor
    if ($id) {
        $doctor = Doctor::whereHas('user', function ($query) {
            $query->where('role', 'doctor');
        })
        ->with(['user', 'specialization', 'schedules']) // Load related data
        ->find($id); // Find the doctor by ID

        if (!$doctor) {
            return response()->json(['message' => 'Doctor not found'], 404);
        }

        return new DoctorResource($doctor); // Return a single doctor resource
    }

    // If no ID is provided, return a paginated list of doctors
    $filter = $request->query('query');
    $doctorsQuery = Doctor::whereHas('user', function ($query) {
        $query->where('role', 'doctor');
    })
    ->with(['user', 'specialization', 'schedules']); // Add 'schedules'

    if ($filter) {
        $doctorsQuery->where('specialization_id', $filter);
    }

    $doctors = $doctorsQuery->paginate();
    return DoctorResource::collection($doctors); // Return a collection of doctors
}
    // public function getDoctor($doctorid)
    // {
    //     $doctor = Doctor::find($doctorid);

    //     return DoctorResource::collection($doctor);
    // }

    /**
     * Store a newly created resource in storage.
     */
   
public function store(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required',
        'phone' => 'nullable|string',
        'password' => 'required|min:8',
        'specialization' => 'required|exists:specializations,id',
        'frequency' => 'required|string',
        'patients_based_on_time' => 'required|boolean',
        'time_slot' => 'required_if:patients_based_on_time,true|nullable|integer',
        'schedules' => 'array|required_without:customDates',
        'customDates' => 'array|required_without:schedules',
        'schedules.*.day_of_week' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
        'schedules.*.shift_period' => 'required|in:morning,afternoon',
        'schedules.*.start_time' => 'required',
        'schedules.*.end_time' => 'required|after:schedules.*.start_time',
        'schedules.*.number_of_patients_per_day' => 'required_if:patients_based_on_time,false|integer',
        'customDates.*.date' => 'required|date|after_or_equal:today',
        'customDates.*.shift_period' => 'required|in:morning,afternoon',
        'customDates.*.start_time' => 'required|date_format:H:i',
        'customDates.*.end_time' => 'required|date_format:H:i|after:customDates.*.start_time',
        'customDates.*.number_of_patients_per_day' => 'required_if:patients_based_on_time,false|integer',
        'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        'appointmentBookingWindow' => 'required|array',
        'appointmentBookingWindow.*.month' => 'required|integer|between:1,12',
        'appointmentBookingWindow.*.is_available' => 'required|boolean',
    ]);

    try {
        return DB::transaction(function () use ($request) {
            $avatarPath = null;
            if ($request->hasFile('avatar')) {
                $avatar = $request->file('avatar');
                $fileName = $request->name . '-' . time() . '.' . $avatar->getClientOriginalExtension();
                $avatarPath = $avatar->storeAs('avatar', $fileName, 'public');
            }

            // Create the user
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'created_by' => 2,
                'password' => Hash::make($request->password),
                'avatar' => $avatarPath,
                'role' => 'doctor',
            ]);

            // Create the doctor
            $doctor = Doctor::create([
                'user_id' => $user->id,
                'specialization_id' => $request->specialization,
                'created_by' => Auth::id(),
                'frequency' => $request->frequency,
                'patients_based_on_time' => $request->patients_based_on_time,
                'time_slots' => $request->time_slot,
            ]);

            // Handle appointment booking window
            $appointmentMonths = [];
            $currentYear = date('Y');
            $currentMonth = date('n');

            if ($request->has('appointmentBookingWindow')) {
                foreach ($request->appointmentBookingWindow as $month) {
                    $year = $currentYear;
                    if ($month['month'] < $currentMonth) {
                        $year = $currentYear + 1;
                    }

                    $appointmentMonths[] = [
                        'month' => $month['month'],
                        'year' => $year,
                        'doctor_id' => $doctor->id,
                        'is_available' => $month['is_available'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                DB::table('appointment_available_month')->insert($appointmentMonths);
            }

            // Handle schedules
            if ($request->has('customDates')) {
                $this->createCustomSchedules($request, $doctor);
            } elseif ($request->has('schedules')) {
                $this->createRegularSchedules($request, $doctor);
            }

            return response()->json([
                'message' => 'Doctor, schedules, and appointment months created successfully!',
                'doctor' => new DoctorResource($doctor),
            ], 201);
        });
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Error creating doctor',
            'error' => $e->getMessage()
        ], 500);
    }
}
private function createCustomSchedules(Request $request, Doctor $doctor)
{
    $customSchedules = collect($request->customDates)->map(function ($dateInfo) use ($doctor, $request) {
        $date = Carbon::parse($dateInfo['date']);
        $dayOfWeek = strtolower($date->format('l'));
        
        // Make sure time formats are correct (H:i)
        $startTime = $this->formatTimeString($dateInfo['start_time']);
        $endTime = $this->formatTimeString($dateInfo['end_time']);
        
        $numberOfPatients = $request->patients_based_on_time 
            ? $this->calculatePatientsPerShift($startTime, $endTime, $request->time_slot)
            : $dateInfo['number_of_patients_per_day'];
            
        // Update future appointments for this specific date and shift period
        $this->updateFutureAppointments(
            $doctor->id,
            $startTime,
            $endTime,
            $numberOfPatients,
            $date->format('Y-m-d'), // Specific date
            null, // No day of week needed since we have a specific date
            $dateInfo['shift_period'] // Added shift period parameter
        );

        return [
            'doctor_id' => $doctor->id,
            'date' => $date->format('Y-m-d'),
            'day_of_week' => $dayOfWeek,
            'shift_period' => $dateInfo['shift_period'],
            'start_time' => $startTime,
            'end_time' => $endTime,
            'number_of_patients_per_day' => $numberOfPatients,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    })->all();

    if (!empty($customSchedules)) {
        Schedule::insert($customSchedules);
    }
}
private function createRegularSchedules(Request $request, Doctor $doctor)
{
    $schedules = collect($request->schedules)->map(function ($schedule) use ($doctor, $request) {
        $numberOfPatients = $request->patients_based_on_time 
            ? $this->calculatePatientsPerShift($schedule['start_time'], $schedule['end_time'], $request->time_slot)
            : $schedule['number_of_patients_per_day'];

        // Update all future appointments for this weekday and shift period
        $this->updateFutureAppointments(
            $doctor->id,
            $schedule['start_time'],
            $schedule['end_time'],
            $numberOfPatients,
            null, // No specific date - all future dates
            $schedule['day_of_week'],
            $schedule['shift_period'] // Added shift_period parameter
        );

        return [
            'doctor_id' => $doctor->id,
            'day_of_week' => $schedule['day_of_week'],
            'shift_period' => $schedule['shift_period'],
            'start_time' => $schedule['start_time'],
            'end_time' => $schedule['end_time'],
            'number_of_patients_per_day' => $numberOfPatients,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    })->all();

    Schedule::insert($schedules);
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
    $dayOfWeek = DayOfWeekEnum::cases()[Carbon::parse($date)->dayOfWeek]->value;
    $doctor = Doctor::find($doctorId, ['patients_based_on_time', 'time_slot']);
    
    if (!$doctor) {
        return [];
    }
    
    $workingHours = [];
    
    // If no specific shift period is provided, get schedules for the day
    if (!$shiftPeriod || !$startTime || !$endTime || $numberOfPatients === null) {
        $schedules = Schedule::select('start_time', 'end_time', 'number_of_patients_per_day', 'shift_period')
            ->where('doctor_id', $doctorId)
            ->where('is_active', true)
            ->where('day_of_week', $dayOfWeek)
            ->get();
        
        if ($schedules->isEmpty()) {
            return [];
        }
        
        // If doctor has a fixed time slot, calculate based on that
        if ($doctor->time_slot !== null && (int)$doctor->time_slot > 0) {
            $timeSlotMinutes = (int)$doctor->time_slot;
            
            foreach (['morning', 'afternoon'] as $shift) {
                $schedule = $schedules->firstWhere('shift_period', $shift);
                if (!$schedule) continue;
                
                $startTime = Carbon::parse($date . ' ' . $schedule->start_time);
                $endTime = Carbon::parse($date . ' ' . $schedule->end_time);
                $currentTime = clone $startTime;
                
                while ($currentTime < $endTime) {
                    $workingHours[] = $currentTime->format('H:i');
                    $currentTime->addMinutes($timeSlotMinutes);
                }
            }
        } else {
            // Calculate based on number of patients per shift
            foreach (['morning', 'afternoon'] as $shift) {
                $schedule = $schedules->firstWhere('shift_period', $shift);
                if (!$schedule) continue;
                
                // Get the exact start and end times
                $startTime = Carbon::parse($schedule->start_time);
                $endTime = Carbon::parse($schedule->end_time);
                // Get number of patients for this specific shift
                $patientsForShift = (int)$schedule->number_of_patients_per_day;
                
                if ($patientsForShift <= 0) continue;
                
                // Calculate total duration in minutes
                $totalDuration = abs($endTime->diffInMinutes($startTime));
                
                // Calculate the gap between each appointment
                // We subtract 1 from patients because we need one less gap than appointments
                $gap = $totalDuration / ($patientsForShift - 1);
                $totalDuration = $totalDuration - $gap;
                $gap = $totalDuration / ($patientsForShift - 1);
                
                // Generate slots
                for ($i = 0; $i < $patientsForShift; $i++) {
                    $minutesToAdd = round($i * $gap);
                    $slotTime = (clone $startTime)->addMinutes($minutesToAdd);
                    $workingHours[] = $slotTime->format('H:i');
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
                $workingHours[] = $currentTime->format('H:i');
                $currentTime->addMinutes($timeSlotMinutes);
            }
        } else {
            // Calculate based on number of patients
            $startTimeCopy = Carbon::parse($startTime);
            $endTimeCopy = Carbon::parse($endTime);
            
            if ($numberOfPatients <= 0) {
                return [];
            }
            
            // Calculate total duration in minutes
            $totalDuration = abs($endTimeCopy->diffInMinutes($startTimeCopy));
            
            if ($numberOfPatients > 1) {
                // Calculate the gap between each appointment
                // We subtract 1 from patients because we need one less gap than appointments
                $gap = $totalDuration / ($numberOfPatients - 1);
                $totalDuration = $totalDuration - $gap;
                $gap = $totalDuration / ($numberOfPatients - 1);
                
                // Generate slots
                for ($i = 0; $i < $numberOfPatients; $i++) {
                    $minutesToAdd = round($i * $gap);
                    $slotTime = (clone $startTimeCopy)->addMinutes($minutesToAdd);
                    $workingHours[] = $slotTime->format('H:i');
                }
            } else {
                // Handle case with just one patient
                $workingHours[] = $startTimeCopy->format('H:i');
            }
        }
    }
    
    return array_unique($workingHours);
}
private function getBookedSlots($doctorId, $date) {
    // Fetch all booked appointments for the given doctor and date
    $bookedAppointments = Appointment::where('doctor_id', $doctorId)
        ->whereDate('appointment_date', $date)
        ->where('status', '!=', AppointmentSatatusEnum::CANCELED->value)
        ->get();
    
    // Map appointment times to a simple format (H:i)
    return $bookedAppointments->map(function ($appointment) {
        return Carbon::parse($appointment->appointment_time)->format('H:i');
    })->toArray();
}
/**
 * Calculate time slots based on start time, end time, and capacity
 */
private function calculateTimeSlots(
    string $startTime, 
    string $endTime, 
    int $capacity, 
    int $doctorId, 
    string $date,
    ?string $shiftPeriod = null,
): array {
    if ($capacity <= 0) {
        return [];
    }
    
    // If no specific shift period is provided, check the schedules table
    if ($shiftPeriod === null) {
        $dayOfWeek = DayOfWeekEnum::cases()[Carbon::parse($date)->dayOfWeek]->value;
        $schedules = Schedule::select('doctor_id', 'day_of_week','start_time', 'end_time', 'number_of_patients_per_day', 'shift_period')
        ->where('doctor_id', 10)
        ->where('is_active', 1)
        ->get();
        
        $slots = [];
        
        // Calculate slots for each shift (morning and afternoon)
        foreach (['morning', 'afternoon'] as $shift) {
            $schedule = $schedules->firstWhere('shift_period', $shift);
            if (!$schedule) continue;
            
            // Get shift-specific data
            $shiftStart = $schedule->start_time;
            $shiftEnd = $schedule->end_time;
            $patientsForShift = (int)$schedule->number_of_patients_per_day;
            
            // Calculate slots for this shift
            $shiftSlots = $this->calculateSlotsForShift($date, $shiftStart, $shiftEnd, $patientsForShift);
            $slots = array_merge($slots, $shiftSlots);
        }
        
        return array_unique($slots);
    }

    // If a specific shift period is provided, calculate for that shift only
    return $this->calculateSlotsForShift($date, $startTime, $endTime, $capacity);
}

private function calculateSlotsForShift(string $date, string $startTime, string $endTime, int $capacity): array
{
    if ($capacity <= 0) {
        return [];
    }
    
    $startDateTime = Carbon::parse("$date $startTime");
    $endDateTime = Carbon::parse("$date $endTime");
    
    // Make sure end time is after start time
    if ($endDateTime->lt($startDateTime)) {
        $endDateTime->addDay(); // Assume it's crossing midnight
    }
    
    // Calculate the total duration in minutes
    $totalDuration = $endDateTime->diffInMinutes($startDateTime);
    
    // If only one patient, return just the start time
    if ($capacity == 1) {
        return [$startTime];
    }
    
    // Calculate time slot gap (time between appointments)
    $gap = abs($totalDuration) / ($capacity - 1);
    
    $slots = [];
    for ($i = 0; $i < $capacity; $i++) {
        $minutesToAdd = round($i * $gap);
        $slotTime = (clone $startDateTime)->addMinutes($minutesToAdd);
        $slots[] = $slotTime->format('H:i');
    }
    
    return $slots;
}


// Helper function to ensure time format is H:i
private function formatTimeString($timeString)
{
    // Parse the input time string 
    $time = Carbon::parse($timeString);
    // Return in the correct format
    return $time->format('H:i');
}
// This method needs to be fixed too to support proper time formatting
private function calculatePatientsPerShift($startTime, $endTime, $timeSlot)
{
    // Format the times to ensure correct format
    $startTime = $this->formatTimeString($startTime);
    $endTime = $this->formatTimeString($endTime);

    // Calculate total minutes in shift
    $start = Carbon::parse($startTime);
    $end = Carbon::parse($endTime);
    $totalMinutes = $end->diffInMinutes($start);
    
    // Calculate number of slots that can fit in this time period
    $slots = floor($totalMinutes / $timeSlot);
    
    // Return at least 1 patient
    return max(1, $slots);
}

private function prepareScheduleData(Doctor $doctor, Carbon $date, string $shift, string $startTime, string $endTime, string $dayOfWeek, ?int $patients, Request $request): array
{
    $numberOfPatients = $patients;
    
    if ($request->patients_based_on_time && !empty($request->time_slot)) {
        $numberOfPatients = $this->calculatePatientsPerShift($startTime, $endTime, $request->time_slot);
    }
    
    return [
        'doctor_id' => $doctor->id,
        'date' => $date->format('Y-m-d'),
        'shift_period' => $shift,
        'start_time' => $startTime,
        'end_time' => $endTime,
        'day_of_week' => $dayOfWeek,
        'is_active' => true,
        'number_of_patients_per_day' => $numberOfPatients,
        'created_at' => now(),
        'updated_at' => now(),
    ];
}
    


    // Make sure your DoctorResource handles JSON days properly
    public function update(Request $request, $doctorid)
    {
        // dd($request->all());
        $validated = $request->validate([
           'name' => 'required|string|max:255',
            'email' => 'required',
            'phone' => 'nullable|string',
            'password' => 'nullable|min:8',
            'specialization' => 'required|exists:specializations,id',
            'frequency' => 'required|string',
            'patients_based_on_time' => 'required|boolean',
            'time_slot' => 'required_if:patients_based_on_time,true|nullable|integer',
            'schedules' => 'array|required_without:customDates',
            'customDates' => 'array|required_without:schedules',
            'schedules.*.day_of_week' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'schedules.*.shift_period' => 'required|in:morning,afternoon',
            'schedules.*.start_time' => 'required',
            'schedules.*.end_time' => 'required|after:schedules.*.start_time',
            'schedules.*.number_of_patients_per_day' => 'required_if:patients_based_on_time,false|integer',
            'customDates.*.date' => 'required|date|after_or_equal:today',
            'customDates.*.shift_period' => 'required|in:morning,afternoon',
            'customDates.*.start_time' => 'required|date_format:H:i',
            'customDates.*.end_time' => 'required|date_format:H:i|after:customDates.*.start_time',
            'customDates.*.number_of_patients_per_day' => 'required_if:patients_based_on_time,false|integer',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'appointmentBookingWindow' => 'required|array',
            'appointmentBookingWindow.*.month' => 'required|integer|between:1,12',
            'appointmentBookingWindow.*.is_available' => 'required|boolean',
        ]);

        try {
            return DB::transaction(function () use ($request, $doctorid) {
                // Find the doctor
                $doctor = Doctor::findOrFail($doctorid);
                // Check if the doctor has an associated user
                if (!$doctor->user) {
                    throw new \Exception('Doctor user not found');
                }
    
                // Handle avatar update
                $avatarPath = $doctor->user->avatar ?? null;
                if ($request->hasFile('avatar')) {
                    // Store new avatar
                    $avatar = $request->file('avatar');
                    $fileName = $request->name . '-' . time() . '.' . $avatar->getClientOriginalExtension();
                    $newAvatarPath = $avatar->storeAs('avatar', $fileName, 'public');
    
                    // Delete old avatar if exists
                    if ($avatarPath && Storage::disk('public')->exists($avatarPath)) {
                        Storage::disk('public')->delete($avatarPath);
                    }
    
                    $avatarPath = $newAvatarPath;
                }
    
                // Update user information
                $doctor->user->update([
                    'name' => $request->name,
                    'email' => $request->email,
                    'phone' => $request->phone,
                    'avatar' => $avatarPath,
                ]);
    
                // Update password if provided
                if ($request->filled('password')) {
                    $doctor->user->update([
                        'password' => Hash::make($request->password)
                    ]);
                }
    
                // Update doctor information
                $doctor->update([
                    'specialization_id' => $request->specialization ,
                    'frequency' => $request->frequency,
                    'patients_based_on_time' => $request->patients_based_on_time ,
                    'time_slot' => $request->time_slot ,
                ]);
                
                
    
                // Handle appointment booking window
                if ($request->has('appointmentBookingWindow')) {
                    // Delete existing appointment months
                    DB::table('appointment_available_month')->where('doctor_id', $doctor->id)->delete();
    
                    // Prepare new appointment months data for bulk insertion
                    $appointmentMonths = [];
                    $currentYear = date('Y'); // Get current year
                    $currentMonth = date('n'); // Get current month without leading zeros
    
                    foreach ($request->appointmentBookingWindow as $month) {
                        $year = $currentYear;
                        if ($month['month'] < $currentMonth) {
                            $year = $currentYear + 1; // If the month has passed, set the year to next year
                        }
    
                        $appointmentMonths[] = [
                            'month' => $month['month'],
                            'year' => $year, // Add the year
                            'doctor_id' => $doctor->id,
                            'is_available' => $month['is_available'],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
    
                    // Insert all appointment months in one query
                    DB::table('appointment_available_month')->insert($appointmentMonths);
                }
    
                // Delete existing schedules
                $doctor->schedules()->delete();
    
                // Create new schedules
                if ($request->has('customDates')) {
                    $this->createCustomSchedules($request, $doctor);
                } elseif ($request->has('schedules')) {
                    $this->createRegularSchedules($request, $doctor);
                }
    
                // Load fresh data with relationships
                $doctor->load(['user', 'schedules', 'specialization']);
    
                return response()->json([
                    'message' => 'Doctor and schedules updated successfully!',
                    'doctor' => new DoctorResource($doctor)
                ]);
            });
        } catch (\Exception $e) {
            \Log::error('Error updating doctor: ' . $e->getMessage());
    
            return response()->json([
                'message' => 'Error updating doctor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function GetDoctorsBySpecilaztion($specializationId)  {
        $doctors = Doctor::where('specialization_id', $specializationId)->get();
        return DoctorResource::collection($doctors);
    }

    public function search(Request $request)
    {
        $searchTerm = $request->query('query');
        
        // If search term is empty, return all doctors paginated
        if (empty($searchTerm)) {
            return DoctorResource::collection(
                Doctor::with(['user', 'specialization']) // Load specialization too
                    ->orderBy('created_at', 'desc')
                    ->paginate()
            );
        }
    
        // Search across related tables (Doctor, User, Specialization)
        $doctors = Doctor::whereHas('specialization', function ($query) use ($searchTerm) {
            // Search in Specialization table
            $query->where('name', 'LIKE', "%{$searchTerm}%");
            $query->where('id', 'LIKE', $searchTerm);
        })
        ->orWhereHas('user', function ($query) use ($searchTerm) {
            // Search in User table
            $query->where('email', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('phone', 'LIKE', "%{$searchTerm}%");
        })
        ->with(['user', 'specialization']) // Eager load User and Specialization
        ->orderBy('created_at', 'desc')
        ->paginate();
    
        return DoctorResource::collection($doctors);
    }
    
    /**
     * Display the specified resource.
     */
    public function specificDoctor(Request $request,$doctorId)
    {
        // Retrieve the doctor using the `doctorid` parameter
        $doctor = Doctor::find($doctorId);
    
        if (!$doctor) {
            return response()->json(['error' => 'Doctor not found'], 404);
        }
    
        // Return the formatted doctor data using a resource
        return new DoctorResource($doctor);
    }
    

    public function storeSchedules($id, Request $request)
    {

        // Validate the request
        $validatedData = $request->validate([
            'schedules' => 'required|array',
            'schedules.*.day_of_week' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'schedules.*.shift_period' => 'required|in:morning,afternoon,evening',
            'schedules.*.start_time' => 'required|date_format:H:i',
            'schedules.*.end_time' => 'required|date_format:H:i',
        ]);
    
        try {
            // Fetch the doctor by ID
            $doctor = Doctor::find($validatedData['doctor_id']);
    
            if (!$doctor) {
                return response()->json(['error' => 'Doctor not found'], 404);
            }
    
            // Create schedules
            foreach ($validatedData['schedules'] as $scheduleData) {
                $doctor->schedules()->create([
                    'doctor_id' => $scheduleData['doctor_id'],
                    'day_of_week' => $scheduleData['day_of_week'],
                    'shift_period' => $scheduleData['shift_period'],
                    'start_time' => $scheduleData['start_time'],
                    'end_time' => $scheduleData['end_time'],
                    
                ]);
            }
    
            return response()->json(['message' => 'Schedules created successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
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
   

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        try {
            // Fetch the doctor by ID from the request
            $doctor = Doctor::find($request->id);
    
            if (!$doctor) {
                return response()->json(['error' => 'Doctor not found'], 404);
            }
    
            // Get the user_id associated with the doctor
            $userId = $doctor->user_id;
    
            // Delete the doctor
            $doctor->delete();
            // Fetch the user by user_id
            $user = User::find($userId);
    
            if (!$user) {
                return response()->json(['error' => 'User not found'], 404);
            }
    
            // Delete the user
            $user->delete();
    
            // Return no content response to signify successful operation
            return response()->noContent();
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function bulkDelete(Request $request)
    {
        try {
            // Fetch the user_ids associated with the doctor IDs
            $userIds = Doctor::whereIn('id', $request->ids)->pluck('user_id');
    
            // Delete doctors
            $doctorsDeleted = Doctor::whereIn('id', $request->ids)->delete();
    
            // Delete users associated with the doctors
            $usersDeleted = User::whereIn('id', $userIds)->delete();
    
            // Return response with the count of deleted doctors and users
            return response()->json([
                'message' => "$doctorsDeleted doctors and $usersDeleted users deleted successfully"
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error deleting doctors and users'], 500);
        }
    }

    
}
