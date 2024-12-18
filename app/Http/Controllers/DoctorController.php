<?php

namespace App\Http\Controllers;

use App\Http\Resources\DoctorResource;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Http\Request;

class DoctorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $doctors = Doctor::with('user')->get();

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
        // Validate incoming data
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'specialization' => 'required|string|max:255',
            'day' => 'required|string|max:10',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'number_of_patient' => 'required|integer|min:0',
            'frequency' => 'required|string|in:Daily,Weekly,Monthly,Custom',
            'specific_date' => 'nullable|date',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif',
        ]);

        // Store the user data first
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),  // Ensure password is hashed
            'role' => 'doctor', // or another role depending on your logic
            // 'avatar' => $request->avatar ? $request->file('avatar')->store('avatars', 'public') : null,  // If avatar is uploaded
        ]);

        // Store the doctor data
        $doctor = Doctor::create([
            'user_id' => $user->id,  // Link the doctor with the created user
            'specialization' => $request->specialization,
            'day' => $request->day,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'number_of_patient' => $request->number_of_patient,
            'frequency' => $request->frequency,
            'specific_date' => $request->specific_date,
            'notes' => $request->notes,
        ]);
        dd($doctor ,$user);
        // Return response
        return response()->json([
            'message' => 'Doctor created successfully!',
            'doctor' => DoctorResource::collection($doctor),
        ], 201);
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
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
