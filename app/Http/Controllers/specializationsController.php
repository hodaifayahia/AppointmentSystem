<?php

namespace App\Http\Controllers;

use App\Http\Resources\SpecializationResource;
use App\Models\Specialization;
use Illuminate\Http\Request;
use Illuminate\Support\Str;


class specializationsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
         
        $specialization =  Specialization::get();
        return SpecializationResource::collection($specialization);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        

    }
    
    public function store(Request $request)
    {
        // Validate the input data including the new photo field
        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:specializations,name,NULL,id,deleted_at,NULL',
            'description' => 'nullable|string|max:1000',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Max 2MB file size
        ]);
    
        try {
            // Handle file upload
            $photoPath = null;
            if ($request->hasFile('photo')) {
                $photo = $request->file('photo');
                $fileName = Str::slug($validatedData['name']) . '-' . time() . '.' . $photo->getClientOriginalExtension();
                $photoPath = $photo->storeAs('specializations', $fileName, 'public');
                $validatedData['photo'] = $photoPath;
            }
    
            // Check if the specialization already exists (including soft deleted)
            $existingSpecialization = Specialization::withTrashed()->where('name', $validatedData['name'])->first();
    
            if ($existingSpecialization) {
                // If it exists and is soft deleted, restore it
                $existingSpecialization->restore();
                
                // Update existing specialization, include photo if new one was uploaded
                $existingSpecialization->update(array_merge($validatedData, [
                    'photo' => $photoPath ?? $existingSpecialization->photo
                ]));
                $specialization = $existingSpecialization;
            } else {
                // Create a new specialization if it doesn't exist
                $specialization = Specialization::create($validatedData);
            }
    
            // Return the specialization with appropriate message
            return response()->json([
                'message' => $existingSpecialization ? 'Specialization restored and updated' : 'Specialization created successfully',
                'data' => $specialization
            ], 201); // 201 Created HTTP status code
    
        } catch (\Exception $e) {
            // Log the error for debugging purposes
            \Log::error('Error creating or restoring specialization: ' . $e->getMessage());
    
            // If there was a file upload, delete it to clean up
            if ($photoPath) {
                Storage::disk('public')->delete($photoPath);
            }
    
            // Return an error response with a more specific message
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

 
   
    public function update(Request $request, $id)
    {
        // Find the specialization or fail if it doesn't exist
        $specialization = Specialization::findOrFail($id);
    
        // Validate the input data, with special handling for the photo update
        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:specializations,name,' . $id . ',id,deleted_at,NULL',
            'description' => 'nullable|string|max:1000',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Max 2MB file size
        ]);
    
        try {
            // Handle file upload if a new photo is provided
            if ($request->hasFile('photo')) {
                // First, delete the old photo if it exists
                if ($specialization->photo) {
                    Storage::disk('public')->delete($specialization->photo);
                }
    
                // Upload the new photo
                $photo = $request->file('photo');
                $fileName = Str::slug($validatedData['name']) . '-' . time() . '.' . $photo->getClientOriginalExtension();
                $photoPath = $photo->storeAs('specializations', $fileName, 'public');
                $validatedData['photo'] = $photoPath;
            } else {
                // If no photo is uploaded, keep the existing photo
                $validatedData['photo'] = $specialization->photo;
            }
    
            // Update the specialization with the new data
            $specialization->update($validatedData);
    
            // Return a success response
            return response()->json([
                'message' => 'Specialization updated successfully',
                'data' => $specialization
            ], 200); // 200 OK for updates
    
        } catch (\Exception $e) {
            // Log the error for debugging purposes
            \Log::error('Error updating specialization: ' . $e->getMessage());
    
            // If a photo was uploaded but we encountered an error, delete the new photo to clean up
            if ($request->hasFile('photo')) {
                Storage::disk('public')->delete($photoPath ?? '');
            }
    
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
