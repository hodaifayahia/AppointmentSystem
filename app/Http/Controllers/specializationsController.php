<?php

namespace App\Http\Controllers;

use App\Models\Specialization;
use Illuminate\Http\Request;

class specializationsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Specialization::get();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validate the input data
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);
    
        try {
            // Check if the specialization already exists (soft deleted)
            $existingSpecialization = Specialization::withTrashed()->where('name', $validatedData['name'])->first();
    
            if ($existingSpecialization) {
                // If it exists and is soft deleted, restore it
                $existingSpecialization->restore();
                $existingSpecialization->update($validatedData);
                $specialization = $existingSpecialization;
            } else {
                // Create a new specialization if it doesn't exist
                $specialization = Specialization::create($validatedData);
            }
    
            // Return the specialization
            return response()->json([
                'message' => 'Specialization created or restored successfully',
                'data' => $specialization
            ], 201); // 201 Created HTTP status code
    
        } catch (\Exception $e) {
            // Log the error for debugging purposes
            \Log::error('Error creating or restoring specialization: ' . $e->getMessage());
    
            // Return an error response
            return response()->json([
                'message' => 'An error occurred while processing the specialization',
                'error' => $e->getMessage()
            ], 500); // 500 Internal Server Error
        }
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function update(Request $request, $id)
    {
        // First, find the specialization or fail if it doesn't exist
        $specialization = Specialization::findOrFail($id);
    
        // Validate the input data, but since 'name' must be unique, we exclude the current record
        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:specializations,name,'.$id,
            'description' => 'nullable|string|max:1000',
        ]);
    
        try {
            // Update the specialization
            $specialization->update($validatedData);
    
            // Return a success response
            return response()->json([
                'message' => 'Specialization updated successfully',
                'data' => $specialization
            ], 200); // 200 OK for updates
    
        } catch (\Exception $e) {
            // Log the error for debugging purposes
            \Log::error('Error updating specialization: ' . $e->getMessage());
    
            // Return an error response
            return response()->json([
                'message' => 'An error occurred while updating the specialization',
                'error' => $e->getMessage()
            ], 500); // 500 Internal Server Error
        }
    }

    /**
     * Update the specified resource in storage.
     */
    

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        // Find the specialization or fail if it doesn't exist
        $specialization = Specialization::findOrFail($id);
    
        try {
            //TODO
            // Before deleting, you might want to check if this specialization is linked to any doctors
            // This is just a suggestion for additional logic; adjust according to your needs
            // if ($specialization->doctors()->count() > 0) {
            //     return response()->json([
            //         'message' => 'Cannot delete this specialization as it is linked to doctors',
            //     ], 400); // Bad Request
            // }
    
            // Delete the specialization
            $specialization->delete();
    
            // Return a success response
            return response()->json([
                'message' => 'Specialization deleted successfully',
            ], 200); // 200 OK for deletion confirmation
    
        } catch (\Exception $e) {
            // Log the error for debugging purposes
            \Log::error('Error deleting specialization: ' . $e->getMessage());
    
            // Return an error response
            return response()->json([
                'message' => 'An error occurred while deleting the specialization',
                'error' => $e->getMessage()
            ], 500); // 500 Internal Server Error
        }
    }
}
