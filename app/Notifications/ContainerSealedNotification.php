<?php

namespace App\Notifications;

use App\Models\ShipmentContainer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContainerSealedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private ShipmentContainer $container
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Container {$this->container->container_number} Sealed")
            ->greeting("Hello {$notifiable->name},")
            ->line("Container **{$this->container->container_number}** has been sealed.")
            ->line("**Container Details:**")
            ->line("- Type: {$this->container->container_type}")
            ->line("- Seal Number: {$this->container->seal_number}")
            ->line("- Weight: {$this->container->current_weight} / {$this->container->max_weight} kg")
            ->line("- Volume: {$this->container->current_volume} / {$this->container->max_volume} m³")
            ->line("- Items: {$this->container->items()->count()}")
            ->action('View Shipment', route('filament.admin.resources.shipments.view', $this->container->shipment_id))
            ->line('Thank you for using our platform!');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'container_id' => $this->container->id,
            'container_number' => $this->container->container_number,
            'seal_number' => $this->container->seal_number,
            'shipment_id' => $this->container->shipment_id,
            'weight' => $this->container->current_weight,
            'volume' => $this->container->current_volume,
            'items_count' => $this->container->items()->count(),
            'message' => "Container {$this->container->container_number} has been sealed",
        ];
    }
}
