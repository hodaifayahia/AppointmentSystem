<?php

namespace App\Http\Controllers;

use App\AppointmentBookingWindow;
use App\Http\Resources\DoctorResource;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        })->with('user');
        
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
        // Validate the request
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string',
            'password' => 'required|min:8',
            'specialization' => 'required',
            'days' => 'required|array',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'frequency' => 'required|string',
            'patients_based_on_time' => 'required|boolean',
            'appointmentBookingWindow' => 'required|integer|in:1,3,5',
            'number_of_patients_per_day' => 'required_if:patients_based_on_time,false|nullable|integer',
            'time_slot' => 'required_if:patients_based_on_time,true|nullable|integer', // Assuming time_slot should be integer for minutes
        ]);
    
        try {
            // Create user
            $user = User::create([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'phone' => $validatedData['phone'],
                'created_by' => 2,//TOdo
                'password' => bcrypt($validatedData['password']),
                'role' => 'doctor',
            ]);
    
            // Calculate number_of_patients_per_day based on time_slot if patients_based_on_time is true
            $number_of_patients_per_day = $validatedData['patients_based_on_time'] 
                ? $this->calculatePatientsPerDay($validatedData['start_time'], $validatedData['end_time'], $validatedData['time_slot']) 
                : $validatedData['number_of_patients_per_day'];
    
            // Create doctor
            $doctor = Doctor::create([
                'user_id' => $user->id,
                'specialization_id' => $validatedData['specialization'],
                'created_by' => 2, //TODO
                'days' => json_encode($validatedData['days']), // Explicitly encode the days array
                'start_time' => $validatedData['start_time'],
                'end_time' => $validatedData['end_time'],
                'number_of_patients_per_day' => $number_of_patients_per_day,
                'frequency' => $validatedData['frequency'],
                'patients_based_on_time' => $validatedData['patients_based_on_time'],
                'time_slot' => $validatedData['time_slot'],
                'appointment_booking_window' => AppointmentBookingWindow::from($validatedData['appointmentBookingWindow'])->value,
                'specific_date' => null,
                'notes' => null,
            ]);
            return response()->json([
                'message' => 'Doctor created successfully!',
                'doctor' => new DoctorResource($doctor),
            ], 201);
    
        } catch (\Exception $e) {
            // If something goes wrong, delete the user if it was created
            if (isset($user)) {
                $user->delete();
            }
            
            return response()->json([
                'message' => 'Error creating doctor',
                'error' => $e->getMessage()
            ], 500);
        }
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
    public function update(Request $request, string $id)
    {
        // Find doctor by user_id and eager load user relationship
        $doctor = Doctor::with('user')->where('id', $id)->firstOrFail();

        // Validate the request
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $doctor->user_id,
            'phone' => 'required|string',
            'password' => 'nullable|min:8',
            'specialization' => 'required',
            'days' => 'required|array',
            'start_time' => 'required',
            'end_time' => 'required',
            'frequency' => 'required|string',
            'patients_based_on_time' => 'required|boolean',
            'appointmentBookingWindow' => 'required|integer|in:1,3,5',
            'number_of_patients_per_day' => 'required_if:patients_based_on_time,false|nullable|integer',
            'time_slot' => 'required_if:patients_based_on_time,true|nullable|string',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
    
        try {
            // Update user
            $doctor->user->update([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
            ]);
    
            if ($request->filled('password')) {
                $doctor->user->update([
                    'password' => bcrypt($request->password)
                ]);
            }
    
            // Update doctor
            $doctor->update([
                'specialization_id' => $request->specialization,
                'days' => $request->days, // The array will be automatically JSON encoded by the cast
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'number_of_patients_per_day' => $request->number_of_patients_per_day,
                'frequency' => $request->frequency,
                'patients_based_on_time' => $request->patients_based_on_time,
                'appointment_booking_window' => $request->appointmentBookingWindow,
                'time_slot' => $request->time_slot,
            ]);
    
            // Refresh the model to get the updated data with relationships
            $doctor = $doctor->fresh('user');
    
            return response()->json([
                'message' => 'Doctor updated successfully!',
                'doctor' => new DoctorResource($doctor),
            ]);
    
        } catch (\Exception $e) {
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
    public function specificDoctor(Request $request)
    {
        // Retrieve the doctor using the `doctorid` parameter
        $doctor = Doctor::find(8);
    
        if (!$doctor) {
            return response()->json(['error' => 'Doctor not found'], 404);
        }
    
        // Return the formatted doctor data using a resource
        return new DoctorResource($doctor);
    }
    

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
