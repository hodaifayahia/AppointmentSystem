<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Fetch the authenticated user
        $user = User::find(Auth::id());
    
        // Optionally, return the user as a resource
        return new UserResource($user);
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
        //
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

    public function update(Request $request)
    {
        $validated = $request->validate([
            'name' => 'string|max:255',
            'email' => 'email|max:255',
            'avatar' => 'nullable|string', // Avatar as base64 string
        ]);

        try {
            $user = User::findOrFail(Auth::id());

            // Update user details
            $user->name = $validated['name'] ?? $user->name;
            $user->email = $validated['email'] ?? $user->email;

            // Handle avatar if passed as base64 string
            if ($request->has('avatar')) {
                $avatarData = $validated['avatar'];
                $image = str_replace('data:image/png;base64,', '', $avatarData);
                $image = str_replace(' ', '+', $image);
                $imageName = 'avatar_' . time() . '.png';

                // Save in storage/app/public/avatars/
                Storage::disk('public')->put('avatars/' . $imageName, base64_decode($image));

                // Delete old avatar if exists
                if ($user->avatar) {
                    Storage::disk('public')->delete($user->avatar);
                }

                // Save new avatar path
                $user->avatar = 'avatars/' . $imageName;
            }

            $user->save();

            return response()->json(['message' => 'User updated successfully', 'user' => new UserResource($user)], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update user: ' . $e->getMessage()], 500);
        }
    }
    
    public function updatePassword(Request $request)
{
    // Validate the incoming request data
    $validated = $request->validate([
        'current_password' => 'required|string',
        'new_password' => 'required|string|min:8|confirmed', // Confirmed means it expects a matching 'new_password_confirmation' field in the request
    ]);

    try {
        // Check if the current password matches
        if (!Hash::check($validated['current_password'], Auth::user()->password)) {
            return response()->json(['error' => 'Current password is incorrect.'], 400);
        }

        // Update the user's password
        $user = Auth::user();
        $user->password = Hash::make($validated['new_password']);
        $user->save();

        // Return success response
        return response()->json(['message' => 'Password updated successfully'], 200);

    } catch (\Exception $e) {
        // Handle any other errors
        return response()->json(['error' => 'Failed to update password: ' . $e->getMessage()], 500);
    }
}
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
