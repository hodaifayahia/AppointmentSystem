<?php

namespace App\Listeners;

use App\Events\PdfGeneratedEvent;
use App\Models\Consultation;
use Illuminate\Support\Facades\Log;

class StoreConsultationRecordListener
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
            // Store consultation record for each template
            foreach ($event->templateIds as $templateId) {
                Consultation::create([
                    'template_id' => $templateId,
                    'patient_id' => $event->patientId,
                    'doctor_id' => $event->doctorId,
                    'appointment_id' => $event->appointmentId
                ]);
            }
            
            Log::info("Consultation records created successfully for patient {$event->patientId}");
        } catch (\Exception $e) {
            Log::error("Error storing consultation records: " . $e->getMessage());
            Log::error($e->getTraceAsString());
        }
    }
}
