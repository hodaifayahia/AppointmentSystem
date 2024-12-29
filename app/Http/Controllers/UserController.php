<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Fetch all users from the database
        $users = User::paginate(2);
    
        // Return the collection wrapped in a resource
        return UserResource::collection($users);  // Wrap collection with resource transformation
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
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string',
            'password' => 'required|string|min:8',
        ]);
    
        // Create the user with role
        $user = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'phone' => $validatedData['phone'],
            'password' => bcrypt($validatedData['password']),
            'created_by' => 2,
        ]);
    
        return new UserResource($user);
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
    public function edit(Request $request, string $id)
    {
        $user = User::findOrFail($id);
    
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'phone' => 'required|string',
            'password' => 'nullable|string|min:8',
        ]);
    
        $updateData = [
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'phone' => $validatedData['phone'],
            'role' => $validatedData['role'], // Add role to update data
        ];
    
        if ($request->filled('password')) {
            $updateData['password'] = bcrypt($request->input('password'));
        }
    
        $user->update($updateData);
    
        return response()->json([
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
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
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
