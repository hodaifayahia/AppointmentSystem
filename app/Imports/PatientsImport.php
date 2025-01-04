<?php

namespace App\Imports;

use App\Models\Patient;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Validation\Rule;

class PatientsImport implements ToModel, WithHeadingRow
{
    protected $createdBy;

    public function __construct($createdBy)
    {
        $this->createdBy = $createdBy;
    }

    public function model(array $row)
    {
        // Handle date of birth with multiple format support
        $dateOfBirth = null;
        if (!empty($row['date_of_birth'])) {
            try {
                // Try multiple date formats
                $dateFormats = ['mm/dd/YYYY', 'd/m/Y', 'Y-m-d'];
                foreach ($dateFormats as $format) {
                    try {
                        $dateOfBirth = Carbon::createFromFormat($format, $row['date_of_birth']);
                        if ($dateOfBirth) break;
                    } catch (\Exception $e) {
                        continue;
                    }
                }
            } catch (\Exception $e) {
                // If all formats fail, leave as null
                $dateOfBirth = null;
            }
        }
        
        return new Patient([
            'Firstname' => trim($row['firstname']),
            'Lastname' => trim($row['lastname']),
            'phone' => isset($row['phone']) ? trim((string)$row['phone']) : null,
            'Idnum' => trim($row['idnum']),
            'dateOfBirth' => $dateOfBirth,
            'created_by' => $this->createdBy,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // public function rules(): array
    // {
    //     return [
    //         '*.firstname' => ['required', 'string', 'max:255'],
    //         '*.lastname' => ['required', 'string', 'max:255'],
    //         '*.phone' => ['nullable', function ($attribute, $value, $fail) {
    //             if ($value) {
    //                 $cleanedPhone = preg_replace('/[^0-9+]/', '', (string)$value);
    //                 if (strlen($cleanedPhone) < 8 || strlen($cleanedPhone) > 15) {
    //                     $fail("The $attribute must be a valid phone number.");
    //                 }
    //             }
    //         }],
    //         '*.idnum' => ['required', 'string', 'unique:patients,idnum'],
    //         '*.date_of_birth' => ['nullable', function ($attribute, $value, $fail) {
    //             if ($value) {
    //                 $dateFormats = ['m/d/Y', 'd/m/Y', 'Y-m-d'];
    //                 $isValid = false;
                    
    //                 foreach ($dateFormats as $format) {
    //                     try {
    //                         $date = Carbon::createFromFormat($format, $value);
    //                         if ($date && $date->year > 1900 && $date <= now()) {
    //                             $isValid = true;
    //                             break;
    //                         }
    //                     } catch (\Exception $e) {
    //                         continue;
    //                     }
    //                 }
                    
    //                 if (!$isValid) {
    //                     $fail("The $attribute must be a valid date in one of these formats: MM/DD/YYYY, DD/MM/YYYY, or YYYY-MM-DD");
    //                 }
    //             }
    //         }],
    //     ];
    // }

    public function customValidationMessages()
    {
        return [
            '*.firstname.required' => 'The firstname field is required.',
            '*.firstname.string' => 'The firstname must be text.',
            '*.lastname.required' => 'The lastname field is required.',
            '*.lastname.string' => 'The lastname must be text.',
            '*.idnum.required' => 'The ID number field is required.',
            '*.idnum.unique' => 'The ID number has already been taken.',
        ];
    }
}