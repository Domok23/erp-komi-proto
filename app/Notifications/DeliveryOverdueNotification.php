<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Filament\Notifications\Notification as FilamentNotification;

class DeliveryOverdueNotification extends Notification
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
            ->title("Delivery Overdue: PO {$this->po->po_number}")
            ->body("Delivery was due on {$this->deadline->format('Y-m-d')} ({$this->daysOverdue} " . ($this->daysOverdue == 1 ? 'day' : 'days') . " overdue).")
            ->danger()
            ->getDatabaseMessage();
    }
}
