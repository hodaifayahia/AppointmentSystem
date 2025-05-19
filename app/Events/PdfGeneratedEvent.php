<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PdfGeneratedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $templateIds;
    public $placeholderData;
    public $patientInfo;
    public $pdfPath;
    public $doctorId;
    public $patientId;
    public $appointmentId;

    /**
     * Create a new event instance.
     */
    public function __construct(array $templateIds, array $placeholderData, string $pdfPath, int $doctorId, int $patientId, int $appointmentId)
    {
        $this->templateIds = $templateIds;
        $this->placeholderData = $placeholderData;
        $this->pdfPath = $pdfPath;
        $this->doctorId = $doctorId;
        $this->patientId = $patientId;
        $this->appointmentId = $appointmentId;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }
}