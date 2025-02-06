<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWaitlistRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true; // Adjust this based on your authorization logic
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'doctor_id' => 'nullable|exists:doctors,id', // Ensure the doctor exists in the database
            'patient_id' => 'sometimes|required|exists:patients,id', // Ensure the patient exists
            'specialization_id' => 'sometimes|required|exists:specializations,id', // Ensure the specialization exists
            'is_Daily' => 'nullable|boolean', // Must be a boolean if provided
            'importance' => 'nullable|in:0,1', // Must be one of the allowed values
            'notes' => 'sometimes|nullable|string', // Must be a string if provided
        ];
    }
}