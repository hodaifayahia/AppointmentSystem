<?php

namespace App\Http\Controllers;

use \Storage;
use App\Http\Enum\RoleSystemEnum;
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
        ->paginate();
    
        // Return the collection wrapped in a resource
        return UserResource::collection($users);  // Wrap collection with resource transformation
    }
    public function GetReceptionists(Request $request)
    {
        // Fetch users where the role column is "receptionist"
        $users = User::where('role','receptionist')->paginate();
    
        return UserResource::collection($users);
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
            'email' => 'required|unique:users,email',
            'phone' => 'required|string',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Max 2MB file size
            'role' => 'required|string|in:admin,doctor,receptionist,SuperAdmin', 
            'password' => 'required|string|min:8',
        ]);
    
        // Handle file upload for avatar (only if a new avatar is uploaded)
        $avatarPath = null;
        if ($request->hasFile('avatar')) {
            // Check if the user has an existing avatar and delete it
            if ($request->user() && $request->user()->avatar) {
                Storage::disk('public')->delete($request->user()->avatar);
            }
    
            $avatar = $request->file('avatar');
            $fileName = $validatedData['name'] . '-' . time() . '.' . $avatar->getClientOriginalExtension();
            $avatarPath = $avatar->storeAs('Users', $fileName, 'public');
        }
    
        // Create or update the user
        $user = User::updateOrCreate(
            ['id' => $request->id], // This ensures you can update an existing user
            [
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'phone' => $validatedData['phone'],
                'role' => $validatedData['role'],
                'password' => Hash::make($validatedData['password']),
                'avatar' => $avatarPath ?? $request->user()->avatar, // Keep old avatar if none uploaded
            ]
        );
    
        return new UserResource($user);
    }
   /**
 * Show the form for editing the specified resource.
 */
public function update(Request $request, string $id)
{
    // Find the user by their ID
    $user = User::findOrFail($id);

    // Validate input data, including avatar
    $validatedData = $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|unique:users,email,' . $id,
        'phone' => 'required|string|min:10|max:15',
        'password' => 'nullable|string|min:8',
        'role' => 'required|string|in:admin,doctor,receptionist,SuperAdmin',
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
        $updateData['avatar'] = $request->file('avatar')->store('users', 'public');
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
        $role = $request->query('role');
        // If search term is empty, return an empty collection
        if (empty($searchTerm)) {
            return UserResource::collection(User::orderBy('created_at', 'desc')->get());
        }
    
        $users = User::where(function($query) use ($searchTerm) {
            $query->where('name', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('email', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('phone', 'LIKE', "%{$searchTerm}%");
        });

        if ($role) {
            $users->where('role',$role);
        }
        $users = $users->orderBy('created_at', 'desc')
        ->paginate();
    
    
        return UserResource::collection($users);
    }
    

    

  

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
{
    $authUser = Auth::user();

    // Prevent deleting a SuperAdmin if the authenticated user is not a SuperAdmin
    if ($user->role === RoleSystemEnum::SuperAdmin) {
        return response()->json([
            'message' => 'You cannot delete a Super Admin',
        ], 403);
    }

    $user->delete(); // Uses SoftDeletes if enabled

    return response()->noContent(); // Returns HTTP 204 No Content
}

public function bulkDelete(Request $request)
{
    $ids = $request->input('ids'); // Retrieves 'ids' from the request body

    if (!is_array($ids) || empty($ids)) {
        return response()->json(['message' => 'Invalid input'], 422);
    }

    // Convert all IDs to integers to avoid SQL injection risks
    $ids = array_map('intval', $ids);

    // Exclude Super Admins from being deleted
    $usersToDelete = User::whereIn('id', $ids)->where('role', '!=', RoleSystemEnum::SuperAdmin)->pluck('id')->toArray();

    if (empty($usersToDelete)) {
        return response()->json(['message' => 'No users deleted (Super Admins cannot be deleted)'], 403);
    }

    User::whereIn('id', $usersToDelete)->delete();

    return response()->json(['message' => 'Users deleted successfully!'], 200);
}

    
    
    
}
