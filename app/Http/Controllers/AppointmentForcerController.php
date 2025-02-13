<?php

namespace App\Http\Controllers;

use App\Http\Resources\ForceStuatAppointmentResource;
use App\Models\AppointmentForcer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AppointmentForcerController extends Controller
{

    public function getPermissions(Request $request)
    {
        try {
    
            if ($request->has('doctor_id')) {
                $validator = Validator::make($request->all(), [
                    'doctor_id' => 'exists:doctors,id',
                ]);
    
                if ($validator->fails()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Validation failed',
                        'errors' => $validator->errors(),
                    ], 422);
                }
            }
    
            // Fetch permissions (filtered by doctor_id if provided)
            $query = AppointmentForcer::all();
    
            if ($request->has('doctor_id')) {
                $query->where('doctor_id', $request->doctor_id);
            }
    

    
            return response()->json([
                'success' => true,
                'data' => ForceStuatAppointmentResource::collection($query),
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error in AppointmentForcerController@getPermissions: ' . $e->getMessage());
    
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching permissions',
            ], 500);
        }
    }
    public function IsAbleToForce(Request $request)
{
    try {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'doctor_id' => 'required|exists:doctors,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $userId = $request->user_id;
        $doctorId = $request->doctor_id;

        // Fetch permissions
        $permission = AppointmentForcer::where('user_id', $userId)
                                       ->where('doctor_id', $doctorId)
                                       ->first();

        $isAbleToForce = false;
        if ($permission) {
            $isAbleToForce = ($permission->is_able_to_force === null || $permission->is_able_to_force === 0 || $permission->is_able_to_force === false) ? false : true;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'is_able_to_force' => $isAbleToForce,
            ],
        ], 200);
    } catch (\Exception $e) {
        Log::error('Error in AppointmentForcerController@getPermissions: ' . $e->getMessage());

        return response()->json([
            'success' => false,
            'message' => 'An error occurred while fetching permissions',
        ], 500);
    }
}
    public function updateOrCreatePermission(Request $request)
    {
        dump($request->all());
        try {
            // Validate request data
            $validator = Validator::make($request->all(), [
                'doctor_id' => 'required|exists:doctors,id',
                'user_id' => 'required|exists:users,id',
                'is_able_to_force' => 'required|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if the permission record exists
            $permission = AppointmentForcer::where([
                'doctor_id' => $request->doctor_id,
                'user_id' => $request->user_id
            ])->first();

            if ($permission) {
                // Update existing permission
                $permission->update([
                    'is_able_to_force' => $request->is_able_to_force
                ]);

                $message = $request->is_able_to_force ? 
                    'Permission granted successfully' : 
                    'Permission removed successfully';
            } else {
                // Create new permission
                AppointmentForcer::create([
                    'doctor_id' => $request->doctor_id,
                    'user_id' => $request->user_id,
                    'is_able_to_force' => $request->is_able_to_force
                ]);
                $message = 'Permission created successfully';
                dd( $message);

            }

            return response()->json([
                'success' => true,
                'message' => $message
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error in AppointmentForcerController@updateOrCreatePermission: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating permissions'
            ], 500);
        }
    }

    // Optional: Get current permission status
    public function getPermissionStatus(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'doctor_id' => 'required|exists:doctors,id',
                'user_id' => 'required|exists:users,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $permission = AppointmentForcer::where([
                'doctor_id' => $request->doctor_id,
                'user_id' => $request->user_id
            ])->first();

            return response()->json([
                'success' => true,
                'data' => [
                    'is_able_to_force' => $permission ? $permission->is_able_to_force : false
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error in AppointmentForcerController@getPermissionStatus: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching permission status'
            ], 500);
        }
    }
}