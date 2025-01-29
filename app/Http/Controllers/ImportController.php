<?php

namespace App\Http\Controllers;

use App\Imports\AppointmentImport;
use App\Imports\PatientsImport;
use App\Imports\UsersImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class ImportController extends Controller
{
    
    public function ImportUsers(Request $request)
    {
        try {
            // Validate the uploaded file
            $request->validate([
                'file' => 'required|mimes:xlsx,csv',
            ]);
    
            $createdBy = Auth::id();
            Excel::import(new UsersImport($createdBy), $request->file('file'));
    
            return response()->json([
                'success' => true,
                'message' => 'Users imported successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage()
            ], 422);
        }
    }

    public function ImportPatients(Request $request)
    {
        try {
            // More lenient file validation
            $request->validate([
                'file' => [
                    'required',
                    'file',
                    'mimes:csv,xlsx,xls',
                    'max:10240', // 10MB max file size
                ]
            ], [
                'file.required' => 'Please select a file to upload.',
                'file.file' => 'The uploaded file is invalid.',
                'file.mimes' => 'The file must be a CSV or Excel file (xlsx, csv).',
                'file.max' => 'The file size must not exceed 10MB.',
            ]);

            $createdBy = Auth::id();
            Excel::import(new PatientsImport($createdBy), $request->file('file'));

            return response()->json([
                'success' => true,
                'message' => 'Patients imported successfully!'
            ]);

        } catch (ValidationException $e) {
            $failures = collect($e->failures())
                ->map(function ($failure) {
                    return "Row {$failure->row()}: {$failure->errors()[0]}";
                })
                ->join(', ');

            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $failures
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage()
            ], 422);
        }
    }
    public function ImportAppointment(Request $request , $doctorid)
    {
        try {
            // More lenient file validation
            $request->validate([
                'file' => [
                    'required',
                    'file',
                    'mimes:csv,xlsx,xls',
                    'max:10240', // 10MB max file size
                ]
            ], [
                'file.required' => 'Please select a file to upload.',
                'file.file' => 'The uploaded file is invalid.',
                'file.mimes' => 'The file must be a CSV or Excel file (xlsx, csv).',
                'file.max' => 'The file size must not exceed 10MB.',
            ]);
        
            // Get the authenticated user's ID
            $createdBy = Auth::id();
        
            // Perform the import
            $import = new AppointmentImport($createdBy, $doctorid);
            Excel::import($import, $request->file('file'));
        
            // Get the results of the import
            $successCount = $import->getSuccessCount();
            $errors = $import->getErrors();
        
            // Return a JSON response with the results
            if (count($errors) > 0) {
                return response()->json([
                    'success' => true,
                    'message' => "Successfully imported {$successCount} appointments, but some rows had errors.",
                    'errors' => $errors,
                ]);
            }
        
            return response()->json([
                'success' => true,
                'message' => "Successfully imported {$successCount} appointments.",
            ]);
        
        } catch (ValidationException $e) {
            // Handle validation errors (e.g., file type or size issues)
            $failures = collect($e->failures())
                ->map(function ($failure) {
                    return "Row {$failure->row()}: {$failure->errors()[0]}";
                })
                ->join(', ');
        
            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $failures,
            ], 422);
        
        } catch (\Exception $e) {
            // Handle any other exceptions
            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}