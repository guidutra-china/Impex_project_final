<?php

namespace App\Notifications;

use App\Models\Shipment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ShipmentConfirmedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private Shipment $shipment
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Shipment {$this->shipment->shipment_number} Confirmed")
            ->greeting("Hello {$notifiable->name},")
            ->line("The shipment **{$this->shipment->shipment_number}** has been confirmed.")
            ->line("**Shipment Details:**")
            ->line("- Status: {$this->shipment->status}")
            ->line("- Containers: {$this->shipment->containers()->count()}")
            ->line("- Total Weight: {$this->shipment->containers()->sum('current_weight')} kg")
            ->line("- Total Volume: {$this->shipment->containers()->sum('current_volume')} m³")
            ->action('View Shipment', route('filament.admin.resources.shipments.view', $this->shipment))
            ->line('Thank you for using our platform!');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'shipment_id' => $this->shipment->id,
            'shipment_number' => $this->shipment->shipment_number,
            'status' => $this->shipment->status,
            'containers_count' => $this->shipment->containers()->count(),
            'total_weight' => $this->shipment->containers()->sum('current_weight'),
            'total_volume' => $this->shipment->containers()->sum('current_volume'),
            'message' => "Shipment {$this->shipment->shipment_number} has been confirmed",
        ];
    }
}
