<?php

namespace App\Http\Controllers;

use \Storage;
use App\Http\Resources\UserDoctorResource;
use App\Http\Resources\UserResource;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Fetch all users that are not soft-deleted
        $users = User::where('deleted_at',null)
        ->paginate(2);
    
        // Return the collection wrapped in a resource
        return UserResource::collection($users);  // Wrap collection with resource transformation
    }
    public function getCurrentUser()
    {
        try {
            $user = Doctor::where('user_id', Auth::id())
                ->with(['user', 'specialization'])
                ->first();
    
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Doctor not found for the current user',
                ], 404);
            }
    
            return response()->json([
                'success' => true,
                'data' => new UserDoctorResource($user),
            ]);
    
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching user information',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    /**
     * Show the form for creating a new resource.
     */

     
    public function role()
    {
        return response()->json([
            'role' => Auth::user()->role,
            'id' => Auth::user()->doctor->id ?? null,
            'specialization_id' => Auth::user()->doctor->specialization_id ?? null,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Max 2MB file size
            'role' => 'required|string|in:admin,doctor,receptionist', 
            'password' => 'required|string|min:8',
        ]);
        
        
            // Handle file upload
            $avatarPath = null;
            if ($request->hasFile('avatar')) {
                $avatar = $request->file('avatar');
                $fileName = $validatedData['name'] . '-' . time() . '.' . $avatar->getClientOriginalExtension();
                $avatarPath = $avatar->storeAs('Users', $fileName, 'public');
            }
            
            // Create the user
            $user = User::create([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'phone' => $validatedData['phone'],
                'role' => $validatedData['role'],
                'password' => Hash::make($validatedData['password']),
                'avatar' => $avatarPath,
            ]);
       
        
  
        
    
        return new UserResource($user);
    }

    
    /**
     * Show the form for editing the specified resource.
     */
    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);
    
        // Validate input data, including avatar
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'phone' => 'required|string|min:10|max:15',
            'password' => 'nullable|string|min:8',
            'role' => 'required|string|in:admin,doctor,receptionist',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Avatar validation
        ]);
    
        // Prepare data for updating
        $updateData = [
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'phone' => $validatedData['phone'],
            'role' => $validatedData['role'],
        ];
    
        // Handle avatar upload if present
        if ($request->hasFile('avatar')) {
            // Delete old avatar if exists
            if ($user->avatar) {
                Storage::delete($user->avatar);
            }
    
            // Store new avatar and save its path
            $updateData['avatar'] = $request->file('users')->store('users', 'public');
        }
    
        // Handle password update if provided
        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->input('password'));
        }
    
        // Update user data
        $user->update($updateData);
    
        // Respond with the updated user data
        return response()->json([
            'success' => true,
            'user' => new UserResource($user),
        ]);
    }
    
    public function ChangeRole($userId, Request $request)
    {
        $user = User::findOrFail($userId);
        
        $validatedData = $request->validate([
            'role' => 'required|string|in:admin,doctor,receptionist',
        ]);
        
        $user->update([
            'role' => $validatedData['role']
        ]);
        
        return response()->json([
            "success" => true,
        ]);
    }
    
    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    public function search(Request $request)
    {
        $searchTerm = $request->query('query');
        // If search term is empty, return an empty collection
        if (empty($searchTerm)) {
            return UserResource::collection(User::orderBy('created_at', 'desc')->get());
        }
    
        $users = User::where(function($query) use ($searchTerm) {
            $query->where('name', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('email', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('phone', 'LIKE', "%{$searchTerm}%");
        })
        ->orderBy('created_at', 'desc')
        ->paginate();
    
        return UserResource::collection($users);
    }
    

    

  

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        $user->delete(); // Performs a soft delete if the model uses SoftDeletes
        return response()->noContent(); // Returns a 204 No Content response
    }
    
    public function bulkDelete(Request $request)
    {
        $ids = $request->query('ids'); // Retrieve 'ids' from the query parameters
    
        if (!is_array($ids)) {
            return response()->json(['message' => 'Invalid input'], 422);
        }
    
        User::whereIn('id', $ids)->delete();
    
        return response()->json(['message' => 'Users deleted successfully!'], 200);
    }

    
    
    
}
