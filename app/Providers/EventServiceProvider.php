<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     */
    protected $listen = [
        \App\Events\PdfGeneratedEvent::class => [
            \App\Listeners\StoreConsultationRecordListener::class,
            \App\Listeners\StorePlaceholderAttributesListener::class,
            \App\Listeners\StoreGeneratedPdfDocumentListener::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }
}
