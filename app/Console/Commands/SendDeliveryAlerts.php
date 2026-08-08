<?php

namespace App\Console\Commands;

use App\Models\DeliveryAlertLog;
use App\Models\PoSubcon;
use App\Models\PoSupplier;
use App\Models\User;
use App\Notifications\DeliveryEscalationNotification;
use App\Notifications\DeliveryOverdueNotification;
use App\Notifications\DeliveryWarningNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendDeliveryAlerts extends Command
{
    protected $signature = 'app:send-delivery-alerts';

    protected $description = 'Scan active POs and shipments and send delivery alert notifications';

    public function handle(): int
    {
        $this->info('Starting delivery alerts scan...');

        // 1. Process PoSupplier
        $poSuppliers = PoSupplier::whereIn('status', ['ordered', 'partial'])->get();
        foreach ($poSuppliers as $po) {
            $this->processPO($po);
        }

        // 2. Process PoSubcon
        $poSubcons = PoSubcon::whereIn('status', ['ordered', 'partial'])->get();
        foreach ($poSubcons as $po) {
            $this->processPO($po);
        }

        $this->info('Delivery alerts scan completed.');

        return 0;
    }

    private function processPO($po): void
    {
        // Calculate deadline date
        $deadlineDate = null;

        // Priority 1: Check active shipments
        $activeShipment = $po->purchaseShipments()
            ->whereNotIn('status', ['arrived', 'cancelled'])
            ->whereNotNull('eta')
            ->orderBy('eta', 'asc')
            ->first();

        if ($activeShipment) {
            $deadlineDate = $activeShipment->eta;
        } else {
            // Priority 2: Fall back to PO delivery date
            $deadlineDate = $po->delivery_date;
        }

        if (! $deadlineDate) {
            return; // No deadline reference, skip
        }

        $deadline = Carbon::parse($deadlineDate)->startOfDay();
        $today = Carbon::today();

        // Calculate days difference (positive = late/overdue, negative = early warning)
        $daysOverdue = $deadline->diffInDays($today, false);

        // Determine target alert level
        $targetLevel = null;
        if ($daysOverdue >= 7) {
            $targetLevel = 'escalation';
        } elseif ($daysOverdue >= 0) {
            $targetLevel = 'overdue';
        } elseif ($daysOverdue >= -3 && $daysOverdue <= -1) {
            $targetLevel = 'warning';
        }

        if (! $targetLevel) {
            return;
        }

        // Define levels in order
        $levels = ['warning', 'overdue', 'escalation'];
        $targetIndex = array_search($targetLevel, $levels);

        // Retrieve users in the PO's company to notify
        $users = User::where('company_id', $po->company_id)->get();

        if ($users->isEmpty()) {
            return;
        }

        // Fire all eligible alert levels up to the target level (to backfill missed ones)
        for ($i = 0; $i <= $targetIndex; $i++) {
            $level = $levels[$i];

            // Check if alert was already sent
            $alreadySent = DeliveryAlertLog::where([
                'company_id' => $po->company_id,
                'alertable_type' => get_class($po),
                'alertable_id' => $po->id,
                'alert_level' => $level,
            ])->exists();

            if (! $alreadySent) {
                // Determine notification class
                $notificationClass = match ($level) {
                    'warning' => DeliveryWarningNotification::class,
                    'overdue' => DeliveryOverdueNotification::class,
                    'escalation' => DeliveryEscalationNotification::class,
                };

                // Notify users
                foreach ($users as $user) {
                    $user->notify(new $notificationClass($po, $deadline, $daysOverdue));
                }

                // Log alert
                DeliveryAlertLog::create([
                    'company_id' => $po->company_id,
                    'alertable_type' => get_class($po),
                    'alertable_id' => $po->id,
                    'alert_level' => $level,
                    'deadline_date' => $deadlineDate,
                    'days_overdue' => $daysOverdue,
                    'sent_at' => now(),
                ]);
            }
        }
    }
}
