<?php

namespace App\Listeners;

use App\Events\PdfGeneratedEvent;
use App\Models\Consultation;
use App\Models\ConsultationPlaceholderAttributes;
use App\Models\Placeholder;
use App\Models\Attribute;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class StorePlaceholderAttributesListener
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
            // First, get all consultations for this patient and appointment
            $consultations = Consultation::where('patient_id', $event->patientId)
                ->where('appointment_id', $event->appointmentId)
                ->whereIn('template_id', $event->templateIds)
                ->get();
                
            if ($consultations->isEmpty()) {
                Log::warning("No consultations found for patient {$event->patientId} and appointment {$event->appointmentId}");
                return;
            }

            // Store placeholder attributes data
            foreach ($event->placeholderData as $placeholderKey => $attributeValues) {
                // Parse the key to get placeholder name (format: Placeholder.attribute)
                $parts = explode('.', $placeholderKey);
                if (count($parts) < 2) {
                    continue; // Skip if not in expected format
                }

                $placeholderName = $parts[0];
                $attributeName = $parts[1];

                // Find the placeholder and attribute
                $placeholder = Placeholder::where('name', $placeholderName)->first();
                if (!$placeholder) {
                    Log::warning("Placeholder not found: {$placeholderName}");
                    continue;
                }

                $attribute = Attribute::where('name', $attributeName)
                    ->where('placeholder_id', $placeholder->id)
                    ->first();
                if (!$attribute) {
                    Log::warning("Attribute not found: {$attributeName} for placeholder {$placeholderName}");
                    continue;
                }

                // Store the attribute value for each consultation
                foreach ($consultations as $consultation) {
                    ConsultationPlaceholderAttributes::create([
                        'consultation_id' => $consultation->id,
                        'placeholder_id' => $placeholder->id,
                        'attribute_id' => $attribute->id,
                        'attribute_value' => is_array($attributeValues) ? json_encode($attributeValues) : $attributeValues
                    ]);
                }
            }
            
            Log::info("Placeholder attributes stored successfully for patient {$event->patientId}");
        } catch (\Exception $e) {
            Log::error("Error storing placeholder attributes: " . $e->getMessage());
            Log::error($e->getTraceAsString());
        }
    }
}
