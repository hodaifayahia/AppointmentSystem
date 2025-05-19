<?php

namespace App\Http\Controllers;

use App\Models\Attribute;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AttributeController extends Controller
{
    public function index($placeholder_id)
    {
        try {
            $attributes = Attribute::where('placeholder_id', $placeholder_id)
                ->orderBy('created_at', 'desc')
                ->get();
    
            return response()->json([
                'success' => true,
                'data' => $attributes,
                'message' => 'Attributes retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve attributes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created attribute
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'value' => 'nullable|string|max:255',
            'input_type' => ['required', function ($attribute, $value, $fail) {
                if (!in_array(strtolower($value), ['true', 'false', '1', '0', true, false], true)) {
                    $fail('The input type field must be true or false.');
                }
            }],
            'placeholder_id' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
                'message' => 'Validation failed'
            ], 422);
        }

        $attribute = Attribute::create([
            'name' => $request->name,
            'value' => $request->value ?? null,
            'input_type' => filter_var($request->input_type, FILTER_VALIDATE_BOOLEAN), // Convert to boolean
            'placeholder_id' => $request->placeholder_id
        ]);

        return response()->json([
            'data' => $attribute,
            'message' => 'Attribute created successfully'
        ], 201);
    }

    /**
     * Update the specified attribute
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'value' => 'nullable|string|max:255',
            'input_type' => ['required', function ($attribute, $value, $fail) {
                if (!in_array(strtolower($value), ['true', 'false', '1', '0', true, false], true)) {
                    $fail('The input type field must be true or false.');
                }
            }],
            'placeholder_id' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
                'message' => 'Validation failed'
            ], 422);
        }

        try {
            $attribute = Attribute::findOrFail($id);
            $attribute->update([
                'name' => $request->get('name', $attribute->name),
                'value' => $request->get('value', $attribute->value),
                'input_type' => filter_var($request->get('input_type', $attribute->input_type), FILTER_VALIDATE_BOOLEAN), // Convert to boolean
                'placeholder_id' => $request->get('placeholder_id', $attribute->placeholder_id)
            ]);
    
            return response()->json([
                'data' => $attribute,
                'message' => 'Attribute updated successfully'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Attribute not found'
            ], 404);
        }
    }
    //search
    /**
     * Search for attributes by name
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        $query = $request->input('query');
        $attributes = Attribute::where('name', 'LIKE', "%{$query}%")->get();

        return response()->json([
            'data' => $attributes,
            'message' => 'Attributes retrieved successfully'
        ]);
    }

    /**
     * Remove the specified attribute
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $attribute = Attribute::findOrFail($id);
            $attribute->delete();
    
            return response()->json([
                'message' => 'Attribute deleted successfully'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Attribute not found'
            ], 404);
        }
    }
}