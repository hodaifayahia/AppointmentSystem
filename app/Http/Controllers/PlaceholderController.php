<?php

namespace App\Http\Controllers;

use App\Http\Resources\PlaceholderResource;
use App\Models\Placeholder;
use Illuminate\Http\Request;

class PlaceholderController extends Controller
{
    
    public function index()  {
        $placeholder =  Placeholder::with(['doctor', 'specializations:id,name'])
            ->get();
        
        return PlaceholderResource::collection($placeholder);

    }


    //store
    public function store(Request $request) {
        // Validate the request data
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'doctor_id' => 'required|exists:doctors,id',
            'specializations_id' => 'required|exists:specializations,id',
        ]);

        $placeholder = Placeholder::create([
            'name' => $request->name,
            'description' => $request->description,
            'doctor_id' => $request->doctor_id,
            'specializations_id' => $request->specializations_id,
        ]);

        return response()->json($placeholder, 201);
    }
//make the updaet funcaiton 
    public function update(Request $request) {
        // Validate the request data
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'doctor_id' => 'required|exists:doctors,id',
            'specializations_id' => 'required|exists:specializations,id',
        ]);

        $placeholder = Placeholder::find($request->id);
        if (!$placeholder) {
            return response()->json(['message' => 'Placeholder not found'], 404);
        }

        $placeholder->update([
            'name' => $request->name,
            'description' => $request->description,
            'doctor_id' => $request->doctor_id,
            'specializations_id' => $request->specializations_id,
        ]);

        return response()->json($placeholder, 200);
    }
    //search 
    public function search(Request $request) {
        $query = Placeholder::all();
        if ($request->has('name')) {
            $query = $query->where('name', 'like', '%' . $request->name . '%');
        }
        if ($request->has('description')) {
            $query = $query->where('description', 'like', '%' . $request->description . '%');
        }
        if ($request->has('doctor_id')) {
            $query = $query->where('doctor_id', $request->doctor_id);
        }
        if ($request->has('specializations_id')) {
            $query = $query->where('specializations_id', $request->specializations_id);
        }
        return $query->get();
    }
        
    // deleate 
    public function destroy(Request $request) {
        $placeholder = Placeholder::find($request->id);
        if (!$placeholder) {
            return response()->json(['message' => 'Placeholder not found'], 404);
        }
        
        $placeholder->delete();
        
        return response()->json(['message' => 'Placeholder deleted successfully'], 200);
    }
}
