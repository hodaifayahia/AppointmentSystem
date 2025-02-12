<?php

namespace App\Http\Controllers;

use App\Http\Resources\ExcludedDateResource;
use App\Models\ExcludedDate;
use Illuminate\Http\Request;

class ExcludedDates extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $excludedDates = ExcludedDate::with('doctor')->get();
        return ExcludedDateResource::collection($excludedDates);
    }
    public function GetExcludedDates($doctorId)
    {

        // Fetch all excluded date ranges and eager load the 'doctor' relationship
        $excludedDates = ExcludedDate::where('doctor_id', $doctorId)
            ->with('doctor')  // Eager load the 'doctor' relationship
            ->get();
    
        return ExcludedDateResource::collection($excludedDates);
    }
    

    public function store(Request $request)
    {
        // Validate the request
        $request->validate([
            'doctor_id' => 'nullable|exists:doctors,id', // Ensure the doctor exists
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'reason' => 'nullable|string',
            'applyForAllYears' => 'nullable|boolean',

        ]);

        // Create a new excluded date range
        $excludedDate = ExcludedDate::create([
            'doctor_id' => $request->doctor_id,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'reason' => $request->reason,
            'apply_for_all_years' => $request->applyForAllYears ,

        ]);

        return response()->json(['message' => 'Excluded date range created successfully.', 'data' => $excludedDate], 201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // Validate the request
        $request->validate([
            'doctor_id' => 'nullable|exists:doctors,id', // Ensure the doctor exists
            'start_date' => 'nullable|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string',
            'applyForAllYears' => 'nullable|boolean',
        ]);
        $excludedDate = ExcludedDate::findOrFail($id);

        // Update the excluded date range
        $excludedDate->update([
            'doctor_id' => $request->doctor_id ,
            'start_date' => $request->start_date ,
            'end_date' => $request->end_date ,
            'reason' => $request->reason ,
            'apply_for_all_years' => $request->applyForAllYears ,
        ]);

        return response()->json(['message' => 'Excluded date range updated successfully.', 'data' => $excludedDate]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // Find the excluded date range by ID
        $excludedDate = ExcludedDate::findOrFail($id);

        // Delete the excluded date range
        $excludedDate->delete();

        return response()->json(['message' => 'Excluded date range deleted successfully.']);
    }
}