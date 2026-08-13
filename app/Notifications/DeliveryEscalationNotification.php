<?php

namespace App\Notifications;

use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Notifications\Notification;

class DeliveryEscalationNotification extends Notification
{
    private $po;

    private $deadline;

    private $daysOverdue;

    public function __construct($po, $deadline, $daysOverdue)
    {
        $this->po = $po;
        $this->deadline = $deadline;
        $this->daysOverdue = $daysOverdue;
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return FilamentNotification::make()
            ->title("CRITICAL: Delivery Escalation: PO {$this->po->po_number}")
            ->body("Delivery has been overdue since {$this->deadline->format('Y-m-d')} ({$this->daysOverdue} days overdue). Immediate action required!")
            ->danger()
            ->getDatabaseMessage();
    }
}
