<?php

namespace App\Http\Controllers;

use App\Http\Resources\PatientResource;
use App\Models\Patient;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Patient::all();
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
        ]);

        $Patient = Patient::create([
            'Firstname' => $validatedData['first_name'],
            'Lastname' => $validatedData['last_name'],
            'phone' => $validatedData['phone'],
            'created_by' => 2,
        ]);

        return new PatientResource($Patient);
    }

    public function update(Request $request, Patient $patient)
    {
        
        $validatedData = $request->validate([
            'firstname' => 'required|string|max:255',
            'lastname' => 'required|string', 
            'phone' => 'required|string',
        ]);

        $patient->update([
            'Firstname' => $validatedData['firstname'],
            'Lastname' => $validatedData['lastname'],
            'phone' => $validatedData['phone'],
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
              ->orWhere('phone', 'LIKE', "%{$searchTerm}%");
    })
    ->orderBy('created_at', 'desc')
    ->get();
    
    return PatientResource::collection($patients);
}
    

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
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
