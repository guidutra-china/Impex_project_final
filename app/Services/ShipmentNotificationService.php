<?php

namespace App\Services;

use App\Models\Shipment;
use App\Models\ShipmentContainer;
use App\Models\User;
use App\Notifications\ShipmentConfirmedNotification;
use App\Notifications\ContainerSealedNotification;
use Illuminate\Support\Facades\Notification;

class ShipmentNotificationService
{
    /**
     * Notificar sobre shipment confirmado
     */
    public function notifyShipmentConfirmed(Shipment $shipment, ?User $user = null): void
    {
        $recipients = $this->getRecipients($shipment, $user);

        Notification::send($recipients, new ShipmentConfirmedNotification($shipment));
    }

    /**
     * Notificar sobre container selado
     */
    public function notifyContainerSealed(ShipmentContainer $container, ?User $user = null): void
    {
        $recipients = $this->getRecipients($container->shipment, $user);

        Notification::send($recipients, new ContainerSealedNotification($container));
    }

    /**
     * Notificar sobre shipment parcial
     */
    public function notifyPartialShipment(Shipment $shipment, ?User $user = null): void
    {
        $recipients = $this->getRecipients($shipment, $user);

        $message = "Shipment #{$shipment->shipment_number} foi parcialmente enviado";
        
        foreach ($recipients as $recipient) {
            $recipient->notify(new \Illuminate\Notifications\Messages\MailMessage());
        }
    }

    /**
     * Notificar sobre container com alta utilização
     */
    public function notifyHighUtilizationContainer(ShipmentContainer $container, ?User $user = null): void
    {
        $weightUtilization = ($container->current_weight / $container->max_weight) * 100;
        $volumeUtilization = ($container->current_volume / $container->max_volume) * 100;

        if ($weightUtilization >= 90 || $volumeUtilization >= 90) {
            $recipients = $this->getRecipients($container->shipment, $user);

            foreach ($recipients as $recipient) {
                $recipient->notify(new \Illuminate\Notifications\Messages\MailMessage());
            }
        }
    }

    /**
     * Notificar sobre container com baixa utilização
     */
    public function notifyLowUtilizationContainer(ShipmentContainer $container, ?User $user = null): void
    {
        $weightUtilization = ($container->current_weight / $container->max_weight) * 100;
        $volumeUtilization = ($container->current_volume / $container->max_volume) * 100;

        if ($weightUtilization < 50 && $volumeUtilization < 50) {
            $recipients = $this->getRecipients($container->shipment, $user);

            foreach ($recipients as $recipient) {
                $recipient->notify(new \Illuminate\Notifications\Messages\MailMessage());
            }
        }
    }

    /**
     * Notificar sobre shipment com problemas
     */
    public function notifyShipmentIssues(Shipment $shipment, array $issues, ?User $user = null): void
    {
        if (empty($issues)) {
            return;
        }

        $recipients = $this->getRecipients($shipment, $user);

        foreach ($recipients as $recipient) {
            $recipient->notify(new \Illuminate\Notifications\Messages\MailMessage());
        }
    }

    /**
     * Obter destinatários da notificação
     */
    private function getRecipients(Shipment $shipment, ?User $user = null): array
    {
        $recipients = [];

        // Adicionar usuário específico se fornecido
        if ($user) {
            $recipients[] = $user;
        }

        // Adicionar criador do shipment
        if ($shipment->createdBy) {
            $recipients[] = $shipment->createdBy;
        }

        // Adicionar usuários com role 'logistics_manager'
        $logisticsManagers = User::whereHas('roles', function ($q) {
            $q->where('name', 'logistics_manager');
        })->get();

        $recipients = array_merge($recipients, $logisticsManagers->toArray());

        // Remover duplicatas
        $recipients = array_unique($recipients, SORT_REGULAR);

        return $recipients;
    }

    /**
     * Enviar notificação customizada
     */
    public function sendCustomNotification(
        Shipment $shipment,
        string $subject,
        string $message,
        ?User $user = null
    ): void {
        $recipients = $this->getRecipients($shipment, $user);

        foreach ($recipients as $recipient) {
            $recipient->notify(new \Illuminate\Notifications\Messages\MailMessage());
        }
    }
}
