<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWaitlistRequest;
use App\Http\Requests\UpdateWaitlistRequest;
use App\Http\Resources\AppointmentResource;
use App\Http\Resources\WaitListResource;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\WaitList;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class WaitListController extends Controller
{
    /**
     * Display a listing of the resource.
     */
  /**
 * Display a listing of the resource.
 */
public function index(Request $request)
{
    $importance = $request->query('importance');
    $specializationId = $request->query('specialization_id');
    $doctor_id = $request->query('doctor_id');
    $isDaily = $request->query('is_Daily', 0); // Default to 0 if not provided
    $today = Carbon::today();

    $query = WaitList::with(['doctor.user', 'patient', 'specialization']);

    // Filter by is_Daily if provided
    if ($isDaily !== null) {
        $query->where('is_Daily', (int)$isDaily);
        if ((int)$isDaily === 1) {
            $query->whereDate('created_at', $today);
        }
    }

    // Filter by importance if provided
    if ($importance !== null) {
        $query->where('importance', $importance);
    }
    // Filter by specialization ID if provided
    if (!empty($specializationId)) {
        $query->where('specialization_id', $specializationId);
    }
    $waitlistscount = $query->get();


    // Handle doctor_id filtering
    if ($doctor_id === "null") {
        $query->whereNull('doctor_id');
    } elseif (!empty($doctor_id)) {
        $query->where('doctor_id', $doctor_id);
    }


    // Sorting logic
    if ($importance !== null) {
        $query->orderBy('importance', 'asc');
    }

    if ((int)$isDaily === 0) {
        $query->orderBy('updated_at', 'asc'); // Ascending when isDaily = 1
    } 

    // Get filtered waitlists
    $waitlists = $query->get();

    return response()->json([
        'data' => WaitListResource::collection($waitlists),
        'count' => $waitlists->count(),
        'count_with_doctor' => $waitlists->whereNotNull('doctor_id')->count(),
        'count_without_doctor' => $waitlistscount->where('doctor_id',null)->count()
    ]);
}



    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreWaitlistRequest $request)
    {
        $validatedData = $request->validated();
    
        // Ensure doctor_id is set to null if not provided
        if (!isset($validatedData['doctor_id'])) {
            $validatedData['doctor_id'] = null;
        }
    
        // Add created_by to the validated data
        $validatedData['created_by'] = Auth::id();
    
        // Check if a waitlist entry already exists for the given patient, doctor, and specialization
        $existingWaitlist = WaitList::where('patient_id', $validatedData['patient_id'])
            ->where('doctor_id', $validatedData['doctor_id'])
            ->where('specialization_id', $validatedData['specialization_id'])
            ->first();
    
        if ($existingWaitlist) {
            // Update the existing waitlist entry
            $existingWaitlist->update([
                'is_Daily' => $validatedData['is_Daily'],
                'importance' => $validatedData['importance'] ?? null,
                'notes' => $validatedData['notes'],
            ]);
    
            return new WaitListResource($existingWaitlist);
        }
    
        // Create a new waitlist entry if it doesn't exist
        $waitlist = WaitList::create($validatedData);
    
        return new WaitListResource($waitlist);
    }
    public function updateImportance(Request $request, WaitList $waitlist)
    {
       
        $validatedData = $request->validate([
            'importance' => 'required|integer|in:0,1',
        ]); 

        $waitlist->update([
            'importance' => $validatedData['importance'] 
        ]);

        return new WaitListResource($waitlist);
    }

    public function search(Request $request)
    {
        $patients = Patient::query();

        if ($request->has('firstname')) {
            $patients->where('firstname', 'like', '%' . $request->input('firstname') . '%');
        }

        if ($request->has('lastname')) {
            $patients->where('lastname', 'like', '%' . $request->input('lastname') . '%');
        }

        if ($request->has('date_of_birth')) {
            $patients->whereDate('date_of_birth', $request->input('date_of_birth')); 
        }

        if ($request->has('doctor_name')) {
            $patients->whereHas('doctor', function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->input('doctor_name') . '%');
            });
        }

        return $patients->get(); 
    }
    /**
     * Update the specified resource in storage.
     */
   
     public function update(UpdateWaitlistRequest $request, WaitList $waitlist)
     {
         $validatedData = $request->validated();
 
         $waitlist->update($validatedData);
 
         return new WaitListResource($waitlist);
     }
     public function moveToEnd($waitlistId)
     {
         // Find the waitlist item by ID
         $waitlist = WaitList::find($waitlistId);
 
         // Check if the waitlist item exists
         if (!$waitlist) {
             return response()->json([
                 'success' => false,
                 'message' => 'Waitlist item not found.',
             ], 404);
         }
 
         // Update the created_at timestamp to the current time
         $waitlist->updated_at = now();
         $waitlist->MoveToEnd = $waitlist->MoveToEnd + 1;
         $waitlist->save();
 
         // Return a success response
         return response()->json([
             'success' => true,
             'message' => 'Waitlist item moved to the end successfully.',
         ]);
     }

     public function AddPaitentToAppointments(Request $request)
     {
         // Validate the request
         $validated = $request->validate([
             'waitlist_id' => 'required',
             'patient_id' => 'required|exists:patients,id',
             'appointment_date' => 'nullable|date_format:Y-m-d',
             'appointment_time' => 'nullable|date_format:H:i',
             'appointmentId' => 'nullable',
             'doctor_id' => 'required|exists:doctors,id',
             'notes' => 'nullable|string'
         ]);
     
         // Get current date and time
         $now = Carbon::now();
     
         // Determine the appointment date and time
         $appointmentDate = $validated['appointment_date'] ?? $now->format('Y-m-d');
         $appointmentTime = $validated['appointment_time'] ?? $now->format('H:i');
         $existingAppointment = null;
         if ($validated['appointmentId']) {
            $existingAppointment = Appointment::where('id', $validated['appointmentId'])
            ->where('patient_id', $validated['patient_id'])
           ->where('doctor_id', $validated['doctor_id'])
           ->exsits();
         }
     
         // Check if an appointment already exists for the patient and doctor
     
         if ($existingAppointment) {
             // Update the existing appointment with the provided or current date and time
             $existingAppointment->update([
                 'appointment_date' => $appointmentDate,
                 'appointment_time' => $appointmentTime,
                 'notes' => $validated['notes'] ?? $existingAppointment->notes,
                 'status' => 0, // Assuming 0 means "pending" or "scheduled"
                 'created_by' => 0 // Assuming you are using authentication
             ]);
     
             $appointment = $existingAppointment;
         } else {
             // Create a new appointment with the provided or current date and time
             $appointment = Appointment::create([
                 'patient_id' => $validated['patient_id'],
                 'doctor_id' => $validated['doctor_id'],
                 'appointment_date' => $appointmentDate,
                 'appointment_time' => $appointmentTime,
                 'notes' => $validated['notes'] ?? null,
                 'status' => 0, // Assuming 0 means "pending" or "scheduled"
                 'created_by' => 0 // Assuming you are using authentication
             ]);
         }
     
         // Delete the patient from the waitlist
         WaitList::where('id', $validated['waitlist_id'])->delete();
     
         // Return the created or updated appointment as a resource
         return new AppointmentResource($appointment);
     }
     // app/Http/Controllers/WaitlistController.php
     public function isempty()  {
        $waitlist = WaitList::where('is_Daily', 0)->exists();
        return response()->json([
            'data' => $waitlist
        ]);
       
     }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(WaitList $waitlist)
    {
        $waitlist->delete(); 

        return response()->noContent(); 
    }

    public function restore($id)
{
    $waitlist = WaitList::withTrashed()->findOrFail($id); 
    $waitlist->restore(); 

    // ... 
}

}
