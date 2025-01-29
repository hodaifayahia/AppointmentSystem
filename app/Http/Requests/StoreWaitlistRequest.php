<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWaitlistRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true; // You can adjust this based on your authorization logic
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
{
    return [
        'doctor_id' => 'nullable',
        'patient_id' => 'required',
        'specialization_id' => 'required',
        'is_Daily' => 'nullable|boolean',
        'importance' => 'nullable',
        'notes' => 'nullable|string',
    ];
}
}