<?php

namespace App\Http\Controllers;

use App\AppointmentBookingWindow;
use App\DayOfWeekEnum;
use App\Http\Resources\DoctorResource;
use App\Models\Appointment;
use App\Models\Doctor;
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
        $doctorId = $request->query('doctorId'); // Get the doctorId parameter (optional)
        $month = $request->query('month'); // Get the month parameter (e.g., '2023-10')
    
        // Base query to fetch doctors
        $doctorsQuery = Doctor::whereHas('user', function ($query) {
            $query->where('role', 'doctor');
        })
        ->with(['user', 'specialization']);
    
        // Apply doctorId filter if provided
        if ($doctorId) {
            $doctorsQuery->where('id', $doctorId);
        }
    
        // Fetch the doctors
        $doctors = $doctorsQuery->get();
    
        // Transform the results
        $transformedDoctors = $doctors->map(function ($doctor) use ($month) {
            return $this->transformDoctor($doctor, $month);
        });
    
        return response()->json([
            'data' => $transformedDoctors,
        ]);
    }
    

    
    private function transformDoctor($doctor, $month)
    {
        $workingDates = [];
    
        // Fetch appointments for the doctor for the specified month
        $appointments = Appointment::where('doctor_id', $doctor->id)
            ->whereYear('appointment_date', '=', substr($month, 0, 4))
            ->whereMonth('appointment_date', '=', substr($month, 5, 2))
            ->get();
    
        // Extract working dates from appointments
        if ($appointments->isNotEmpty()) {
            $workingDates = $appointments->pluck('appointment_date')->unique()->values()->toArray();
        }
    
        return [
            'id' => $doctor->id,
            'name' => $doctor->user->name,
            'specialization' => $doctor->specialization->name,
            'working_dates' => $workingDates,
        ];
    }
    /**
     * Show the form for creating a new resource.
     */
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
        'email' => 'required|unique:users,email',
        'phone' => 'required|string',
        'password' => 'required|min:8',
        'specialization' => 'required|exists:specializations,id',
        'frequency' => 'required|string',
        'patients_based_on_time' => 'required|boolean',
        'time_slot' => 'nullable|integer',
        'schedules' => 'array|required_without:customDates',
        'customDates' => 'array|required_without:schedules',
        'schedules.*.day_of_week' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
        'schedules.*.shift_period' => 'required|in:morning,afternoon',
        'schedules.*.start_time' => 'required',
        'schedules.*.end_time' => 'required|after:schedules.*.start_time',
        'number_of_patients_per_day' => 'required|integer|min:1',
        'customDates.*.date' => 'required|date|after_or_equal:today',
        'customDates.*.morningStartTime' => 'nullable|required_with:customDates.*.morningEndTime|date_format:H:i',
        'customDates.*.morningEndTime' => 'nullable|required_with:customDates.*.morningStartTime|date_format:H:i',
        'customDates.*.afternoonStartTime' => 'nullable|required_with:customDates.*.afternoonEndTime|date_format:H:i',
        'customDates.*.afternoonEndTime' => 'nullable|required_with:customDates.*.afternoonStartTime|date_format:H:i',
        'customDates.*.morningPatients' => 'nullable|required_with:customDates.*.morningStartTime|integer|min:1',
        'customDates.*.afternoonPatients' => 'nullable|required_with:customDates.*.afternoonStartTime|integer|min:1',
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

            // Prepare appointment available months data for bulk insertion
            $appointmentMonths = [];
            $currentYear = date('Y');
            $currentMonth = date('n'); // Get current month without leading zeros

            if ($request->has('appointmentBookingWindow')) {
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

            // Handle custom dates or regular schedules
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
    $customSchedules = [];

    // Loop through each custom date in the request
    foreach ($request->customDates as $dateInfo) {
        // Parse the date
        $date = Carbon::parse($dateInfo['date']);

        // Determine the day of the week
        $dayOfWeek = DayOfWeekEnum::from(strtolower($dateInfo['day_of_week']))->value;

        // Prepare schedule data based on the shift period
        $customSchedules[] = $this->prepareScheduleData(
            $doctor,
            $date,
            $dateInfo['shift_period'], // 'morning' or 'afternoon'
            $dateInfo['start_time'],   // Start time
            $dateInfo['end_time'],     // End time
            $dayOfWeek,                // Day of the week
            $dateInfo['number_of_patients_per_day'], // Number of patients
            $request
        );
    }

    // Insert all custom schedules into the database
    if (!empty($customSchedules)) {
        Schedule::insert($customSchedules);
    }
}
    
    private function createRegularSchedules(Request $request, Doctor $doctor)
    {
        $schedules = collect($request->schedules)->map(function ($schedule) use ($doctor) {
            return [
                'doctor_id' => $doctor->id,
                'day_of_week' => $schedule['day_of_week'],
                'shift_period' => $schedule['shift_period'],
                'start_time' => $schedule['start_time'],
                'end_time' => $schedule['end_time'],
                'number_of_patients_per_day' => $schedule['number_of_patients_per_day'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        })->all();
    
        Schedule::insert($schedules);
    }
   
    
    private function prepareScheduleData(Doctor $doctor, Carbon $date, string $shift, string $startTime, string $endTime, string $dayOfWeek, ?int $patients, Request $request): array
    {
        // Determine the number of patients for this schedule:
        // 1. If a number is provided (for example, in customDates), use it.
        // 2. Else if the doctor is set to use patients based on time and a time slot is provided,
        //    calculate the number of patients based on the available minutes divided by the time slot duration.
        // 3. Otherwise, use the provided 'number_of_patients_per_day' from the request.
        if ($patients !== null) {
            $numberOfPatients = $patients;
        } elseif ($request->patients_based_on_time && !empty($request->time_slot)) {
            $numberOfPatients = $this->calculatePatientsPerShift($startTime, $endTime, $request->time_slot);
        } else {
            $numberOfPatients = $request->number_of_patients_per_day;
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
    
    
    private function calculatePatientsPerShift($startTime, $endTime, $timeSlot)
    {
        $start = Carbon::parse($startTime);
        $end = Carbon::parse($endTime);
        $totalMinutes = $end->diffInMinutes($start);
        return (int) floor($totalMinutes / $timeSlot);
    }
    /**
     * Calculate the number of patients per day based on start time, end time, and time slot.
     *
     * @param string $startTime Format: H:i
     * @param string $endTime Format: H:i
     * @param int $timeSlot Time in minutes for each slot
     * @return int
     */
    private function calculatePatientsPerDay($startTime, $end_time, $timeSlot)
    {
        $start = strtotime($startTime);
        $end = strtotime($end_time);
        
        // Convert times to minutes since midnight
        $startMinutes = intval(date('H', $start)) * 60 + intval(date('i', $start));
        $endMinutes = intval(date('H', $end)) * 60 + intval(date('i', $end));
        
        // Calculate total workable minutes
        $totalMinutes = $endMinutes - $startMinutes;
        
        // Calculate number of slots
        return floor($totalMinutes / $timeSlot);
    }

    // Make sure your DoctorResource handles JSON days properly
    public function update(Request $request, $doctorid)
    {
        // dd($request->all());
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|unique:users,email',
            'phone' => 'required|string',
            'password' => 'nullable|min:8',
            'specialization' => 'required|exists:specializations,id',
            'frequency' => 'required|string',
            'patients_based_on_time' => 'required|boolean',
            'time_slot' => 'nullable|integer',
            'schedules' => 'array|required_without:customDates',
            'customDates' => 'array|required_without:schedules',
            'schedules.*.day_of_week' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'schedules.*.shift_period' => 'required|in:morning,afternoon',
            'schedules.*.start_time' => 'required',
            'schedules.*.end_time' => 'required|after:schedules.*.start_time',
            'number_of_patients_per_day' => 'required|integer|min:1',
            'customDates.*.morningStartTime' => 'nullable|required_with:customDates.*.morningEndTime|date_format:H:i',
            'customDates.*.morningEndTime' => 'nullable|required_with:customDates.*.morningStartTime|date_format:H:i|after:customDates.*.morningStartTime',
            'customDates.*.afternoonStartTime' => 'nullable|required_with:customDates.*.afternoonEndTime|date_format:H:i',
            'customDates.*.afternoonEndTime' => 'nullable|required_with:customDates.*.afternoonStartTime|date_format:H:i|after:customDates.*.afternoonStartTime',
            'customDates.*.morningPatients' => 'nullable|required_with:customDates.*.morningStartTime|integer|min:1',
            'customDates.*.afternoonPatients' => 'nullable|required_with:customDates.*.afternoonStartTime|integer|min:1',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
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
