<?php

namespace App\Http\Controllers;

use App\AppointmentBookingWindow;
use App\DayOfWeekEnum;
use App\Http\Resources\DoctorResource;
use App\Models\Doctor;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class DoctorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $filter = $request->query('query');
        $doctorsQuery = Doctor::whereHas('user', function ($query) {
            $query->where('role', 'doctor');
        })
        ->with(['user', 'specialization', 'schedules']); // Add 'schedules'
        
        
        if ($filter) {
            $doctorsQuery->where('specialization_id', $filter);
        }
        
        $doctors = $doctorsQuery->paginate();
        return DoctorResource::collection($doctors);
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
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string',
            'password' => 'required|min:8',
            'specialization' => 'required|exists:specializations,id',
            'frequency' => 'required|string',
            'patients_based_on_time' => 'required|boolean',
            'appointmentBookingWindow' => 'required|integer|in:1,3,5',
            'time_slot' => 'nullable|integer|gt:0|required_if:patients_based_on_time,true',
            'schedules' => 'array|required_without:customDates',
            'customDates' => 'array|required_without:schedules',
            'schedules.*.day_of_week' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'schedules.*.shift_period' => 'required|in:morning,afternoon',
            'schedules.*.start_time' => 'required|date_format:H:i',
            'schedules.*.end_time' => 'required|date_format:H:i|after:schedules.*.start_time',
            'schedules.*.number_of_patients_per_day' => 'required|integer|min:1',
            // 'customDates.*.date' => 'required|date|after_or_equal:today',
            'customDates.*.morningStartTime' => 'nullable|required_with:customDates.*.morningEndTime|date_format:H:i',
            'customDates.*.morningEndTime' => 'nullable|required_with:customDates.*.morningStartTime|date_format:H:i',
            'customDates.*.afternoonStartTime' => 'nullable|required_with:customDates.*.afternoonEndTime|date_format:H:i',
            'customDates.*.afternoonEndTime' => 'nullable|required_with:customDates.*.afternoonStartTime|date_format:H:i',
            'customDates.*.morningPatients' => 'nullable|required_with:customDates.*.morningStartTime|integer|min:1',
            'customDates.*.afternoonPatients' => 'nullable|required_with:customDates.*.afternoonStartTime|integer|min:1',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);
    
        try {
            return DB::transaction(function () use ($request) {
                $avatarPath = null;
                if ($request->hasFile('avatar')) {
                    $avatar = $request->file('avatar');
                    $fileName = $request->name . '-' . time() . '.' . $avatar->getClientOriginalExtension();
                    $avatarPath = $avatar->storeAs('avatar', $fileName, 'public');
                }
                $user = User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'phone' => $request->phone,
                    'created_by' =>2,
                    'password' => Hash::make($request->password),
                    'avatar' => $avatarPath,
                    'role' => 'doctor',
                ]);
    
                $doctor = Doctor::create([
                    'user_id' => $user->id,
                    'specialization_id' => $request->specialization,
                    'created_by' =>2,
                    'frequency' => $request->frequency,
                    'patients_based_on_time' => $request->patients_based_on_time,
                    'time_slot' => $request->time_slot,
                    'appointment_booking_window' => AppointmentBookingWindow::from($request->appointmentBookingWindow)->value,
                ]);
    
                if ($request->has('customDates')) {
                    $this->createCustomSchedules($request, $doctor);
                } elseif ($request->has('schedules')) {
                    $this->createRegularSchedules($request, $doctor);
                }
    
                return response()->json([
                    'message' => 'Doctor and schedules created successfully!',
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
        
        foreach ($request->customDates as $dateInfo) {
            $date = Carbon::parse($dateInfo['date']);
            $dayOfWeek = DayOfWeekEnum::from(strtolower($date->englishDayOfWeek))->value;
    
            if (!empty($dateInfo['morningStartTime'])) {
                $customSchedules[] = $this->prepareScheduleData(
                    $doctor,
                    $date,
                    'morning',
                    $dateInfo['morningStartTime'],
                    $dateInfo['morningEndTime'],
                    $dayOfWeek,
                    $dateInfo['morningPatients'] ?? null,
                    $request
                );
            }
    
            if (!empty($dateInfo['afternoonStartTime'])) {
                $customSchedules[] = $this->prepareScheduleData(
                    $doctor,
                    $date,
                    'afternoon',
                    $dateInfo['afternoonStartTime'],
                    $dateInfo['afternoonEndTime'],
                    $dayOfWeek,
                    $dateInfo['afternoonPatients'] ?? null,
                    $request
                );
            }
        }
    
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
        return [
            'doctor_id' => $doctor->id,
            'date' => $date->format('Y-m-d'),
            'shift_period' => $shift,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'day_of_week' => $dayOfWeek,
            'is_active' => true,
            'number_of_patients_per_day' => $patients ?? 
                ($request->patients_based_on_time ? 
                    $this->calculatePatientsPerShift($startTime, $endTime, $request->time_slot) : 
                    $request->number_of_patients_per_day),
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
    public function update(Request $request,  $doctor)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'required|string',
            'password' => 'nullable|min:8',
            'specialization' => 'required|exists:specializations,id',
            'frequency' => 'required|string',
            'patients_based_on_time' => 'required|boolean',
            'appointmentBookingWindow' => 'required|integer|in:1,3,5',
            'time_slot' => 'nullable|integer|gt:0|required_if:patients_based_on_time,true',
            'schedules' => 'array|required_without:customDates',
            'customDates' => 'array|required_without:schedules',
            'schedules.*.day_of_week' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'schedules.*.shift_period' => 'required|in:morning,afternoon',
            'schedules.*.start_time' => 'required|date_format:H:i',
            'schedules.*.end_time' => 'required|date_format:H:i|after:schedules.*.start_time',
            'schedules.*.number_of_patients_per_day' => 'required|integer|min:1',
            'customDates.*.date' => 'required|date|after_or_equal:today',
            'customDates.*.morningStartTime' => 'nullable|required_with:customDates.*.morningEndTime|date_format:H:i',
            'customDates.*.morningEndTime' => 'nullable|required_with:customDates.*.morningStartTime|date_format:H:i|after:customDates.*.morningStartTime',
            'customDates.*.afternoonStartTime' => 'nullable|required_with:customDates.*.afternoonEndTime|date_format:H:i',
            'customDates.*.afternoonEndTime' => 'nullable|required_with:customDates.*.afternoonStartTime|date_format:H:i|after:customDates.*.afternoonStartTime',
            'customDates.*.morningPatients' => 'nullable|required_with:customDates.*.morningStartTime|integer|min:1',
            'customDates.*.afternoonPatients' => 'nullable|required_with:customDates.*.afternoonStartTime|integer|min:1',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);
    
        try {
            return DB::transaction(function () use ($request, $doctor) {
                // Check if the doctor has an associated user
                $doctor = Doctor::find($doctor);
                if (!$doctor->user) {
                    throw new \Exception('Doctor user not found');
                }
    
                // Handle avatar update
                $avatarPath = $doctor->user->avatar ?? null;
                if ($request->hasFile('avatar')) {
                    // Delete old avatar if exists
                    if ($avatarPath && Storage::disk('public')->exists($avatarPath)) {
                        Storage::disk('public')->delete($avatarPath);
                    }
                    
                    $avatar = $request->file('avatar');
                    $fileName = $request->name . '-' . time() . '.' . $avatar->getClientOriginalExtension();
                    $avatarPath = $avatar->storeAs('avatar', $fileName, 'public');
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
                    'specialization_id' => $request->specialization,
                    'frequency' => $request->frequency,
                    'patients_based_on_time' => $request->patients_based_on_time,
                    'time_slot' => $request->time_slot,
                    'appointment_booking_window' => AppointmentBookingWindow::from($request->appointmentBookingWindow)->value,
                ]);
    
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

        dd($request->all());
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
            // Fetch the user by ID from the request
            $user = User::find($request->id);
            
            if (!$user) {
                return response()->json(['error' => 'User not found'], 404);
            }
    
            // Delete the user
            $user->delete();
    
            // Fetch the doctor associated with the user
            $doctor = Doctor::where('user_id', $request->id)->first();
    
            // Delete the doctor if it exists
            if ($doctor) {
                $doctor->delete();
            }
    
            // Return no content response to signify successful operation
            return response()->noContent();
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    
    public function bulkDelete(Request $request)
    {
        try {
            // Delete users
            User::whereIn('id', $request->ids)->delete();
    
            // Delete doctors associated with the users in one query
            $doctorsDeleted = Doctor::whereIn('user_id', $request->ids)->delete();
    
            // Return response with the count of deleted doctors
            return response()->json(['message' => "$doctorsDeleted doctors deleted successfully"]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error deleting doctors'], 500);
        }
    }

    
}
