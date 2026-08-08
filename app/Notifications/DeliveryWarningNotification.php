<?php

namespace App\Notifications;

use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Notifications\Notification;

class DeliveryWarningNotification extends Notification
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
        $days = abs($this->daysOverdue);

        return FilamentNotification::make()
            ->title("Delivery Approaching: PO {$this->po->po_number}")
            ->body("Delivery deadline is on {$this->deadline->format('Y-m-d')} ({$days} ".($days == 1 ? 'day' : 'days').' left).')
            ->warning()
            ->getDatabaseMessage();
    }
}
