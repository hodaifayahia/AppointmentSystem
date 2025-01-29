<?php

namespace App\Http\Controllers;

use \Log;
use App\Http\Resources\AppointmentResource;
use App\Http\Resources\PatientResource;
use App\Models\Appointment;
use App\Models\Patient;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $patients = Patient::paginate(50);

        return [
            'data' => PatientResource::collection($patients),
            'meta' => [
                'total' => $patients->total(),
                'per_page' => $patients->perPage(),
                'current_page' => $patients->currentPage(),
                'last_page' => $patients->lastPage(),
                'from' => $patients->firstItem(),
                'to' => $patients->lastItem(),
            ],
        ];
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string', 
            'phone' => 'required|string',
            'dateOfBirth' => 'nullable|date|string',
            'Idnum' => 'nullable|string|max:20', // Assuming ID can be up to 20 characters long
        ]);
    
        $patient = Patient::create([
            'Firstname' => $validatedData['first_name'],
            'Lastname' => $validatedData['last_name'],
            'phone' => $validatedData['phone'],
            'dateOfBirth' => $validatedData['dateOfBirth'] ?? null, // Handle optional date
            'Idnum' => $validatedData['Idnum'] ?? null, // Handle optional ID number
            'created_by' => 2,
        ]);
       
    
        return new PatientResource($patient);
    }
    
    public function update(Request $request, Patient $patient)
    {
        $validatedData = $request->validate([
            'firstname' => 'required|string|max:255',
            'lastname' => 'required|string', 
            'phone' => 'required|string',
            'dateOfBirth' => 'nullable|date|string',
            'Idnum' => 'nullable|string|max:20',
        ]);
    
        $patient->update([
            'Firstname' => $validatedData['firstname'],
            'Lastname' => $validatedData['lastname'],
            'phone' => $validatedData['phone'],
            'dateOfBirth' => $validatedData['dateOfBirth'] ?? null,
            'Idnum' => $validatedData['Idnum'] ?? null,
        ]);
    
        return new PatientResource($patient);
    }

    /**
     * Display the specified resource.
     */
    // PatientController.php
public function search(Request $request)
{
    $searchTerm = $request->query('query');
    
    // If search term is empty, return all patients ordered by 'created_at'
    if (empty($searchTerm)) {
        return PatientResource::collection(
            Patient::orderBy('created_at', 'desc')->get()
        );
    }
    
    // Search patients by first name, last name, or phone
    $patients = Patient::where(function($query) use ($searchTerm) {
        $query->where('Firstname', 'LIKE', "%{$searchTerm}%")
              ->orWhere('Lastname', 'LIKE', "%{$searchTerm}%")
              ->orWhere('dateOfBirth', 'LIKE', "%{$searchTerm}%")
              ->orWhere('Idnum', 'LIKE', "%{$searchTerm}%")
              ->orWhere('phone', 'LIKE', "%{$searchTerm}%");
    })
    ->orderBy('created_at', 'desc')
    ->get();
    
    return PatientResource::collection($patients);
}
    

    /**
     * Show the form for editing the specified resource.
     */
    public function PatientAppointments($PatientId)
    {
        // Log the received PatientId for debugging
        Log::info('Received PatientId:', ['PatientId' => $PatientId]);
    
        // Use eager loading to reduce the number of queries, enhancing performance with large datasets
        $appointments = Appointment::with(['patient', 'doctor.user'])
            ->where('patient_id', $PatientId)
            ->orderBy('appointment_date', 'desc') // Sort by appointment date, most recent first
            ->paginate(15); // Paginate with 15 items per page, adjust as needed
    
        // Check if any appointments were found
        if ($appointments->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No appointments found for the given patient.'
            ], 404);
        }
    
        // Add custom data or perform additional operations here if required
        $totalAppointments = $appointments->total(); // Total count of appointments
    
        // If you need to pass additional metadata to the frontend:
        $metaData = [
            'total' => $totalAppointments,
            'page' => $appointments->currentPage(),
            'per_page' => $appointments->perPage(),
            'last_page' => $appointments->lastPage(),
            'from' => $appointments->firstItem(),
            'to' => $appointments->lastItem(),
        ];
    
        // Use a custom resource collection which includes the metadata
        return AppointmentResource::collection($appointments)->additional(['meta' => $metaData]);
    }
    public function SpecificPatient( $patientid)
    {
        $patient = Patient::find($patientid);
        return new PatientResource($patient);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Patient $patient)
    {
        try {
            $patient->delete();
            return response()->json([
                'message' => 'Patient deleted successfully',
            ], Response::HTTP_OK); // Return 200 OK
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete patient',
                'error' => $e->getMessage(), // Optionally include the error message for debugging
            ], Response::HTTP_INTERNAL_SERVER_ERROR); // Return 500 Internal Server Error
        }
    }
    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:patients,id', // Ensure all IDs exist in the patients table
        ]);

        try {
            Patient::whereIn('id', $request->input('ids'))->delete();

            return response()->json(['message' => 'Patients deleted successfully'], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to delete patients', 'error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
