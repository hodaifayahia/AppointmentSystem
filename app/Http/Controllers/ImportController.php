<?php

namespace App\Http\Controllers;

use App\Imports\AppointmentImport;
use App\Imports\AttributeImport;
use App\Imports\PatientsImport;
use App\Imports\PlaceholderImport;
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
    public function ImportAppointment(Request $request, $doctorid)
    {
        try {
            // Validate the uploaded file
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
            
            // Create a new import instance
            $import = new AppointmentImport($createdBy, $doctorid);
            
            // Process the file
            Excel::import($import, $request->file('file'));
            
            // Get import results
            $successCount = $import->getSuccessCount();
            $errors = $import->getErrors();
            
            // Return appropriate response
            if (count($errors) > 0) {
                return response()->json([
                    'success' => true,
                    'message' => "Imported {$successCount} appointments with some errors",
                    'details' => [
                        'successCount' => $successCount,
                        'errorCount' => count($errors),
                        'errors' => $errors
                    ]
                ], 207); // 207 Multi-Status
            }
            
            return response()->json([
                'success' => true,
                'message' => "Successfully imported {$successCount} appointments",
                'details' => [
                    'successCount' => $successCount,
                    'errorCount' => 0
                ]
            ], 200);
            
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Import failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function Importplaceholders()  {
        $request->validate([
            'files.*' => 'required|file|mimes:xlsx,xls,csv|max:2048',
        ]);
    
        foreach ($request->file('files') as $file) {
            Excel::import(new PlaceholderImport, $file);
        }
    
        return response()->json(['message' => 'Files imported successfully'], 200);
        
    }
    public function ImportAttributes()  {
        $request->validate([
            'files.*' => 'required|file|mimes:xlsx,xls,csv|max:2048',
        ]);
    
        foreach ($request->file('files') as $file) {
            Excel::import(new AttributeImport, $file);
        }
    
        return response()->json(['message' => 'Files imported successfully'], 200);
        
    }
}