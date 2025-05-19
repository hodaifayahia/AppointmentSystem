<?php

namespace App\Listeners;

use App\Events\PdfGeneratedEvent;
use App\Models\PatientDocement;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class StoreGeneratedPdfDocumentListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(PdfGeneratedEvent $event): void
    {
        try {
            // Store the generated PDF document
            $fileName = basename($event->pdfPath);
            $fileSize = Storage::size($event->pdfPath);
            
            PatientDocement::create([
                'patient_id' => $event->patientId,
                'doctor_id' => $event->doctorId,
                'folder_id' => 1, // Default folder ID, you might want to make this configurable
                'document_type' => 'Word',
                'document_path' => $event->pdfPath,
                'document_name' => $fileName,
                'document_size' => $fileSize
            ]);

            Log::info("PDF document stored successfully for patient {$event->patientId}");
        } catch (\Exception $e) {
            Log::error("Error storing PDF document: " . $e->getMessage());
            Log::error($e->getTraceAsString());
        }
    }
}