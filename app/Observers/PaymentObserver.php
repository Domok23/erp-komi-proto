<?php

namespace App\Observers;

use App\Models\Payment;

class PaymentObserver
{
    /**
     * Handle the Payment "saved" event.
     */
    public function saved(Payment $payment): void
    {
        $this->updateInvoice($payment);
    }

    /**
     * Handle the Payment "deleted" event.
     */
    public function deleted(Payment $payment): void
    {
        $this->updateInvoice($payment);
    }

    /**
     * Update the associated invoice paid amount and status.
     */
    protected function updateInvoice(Payment $payment): void
    {
        $invoice = $payment->invoice;
        if ($invoice) {
            $totalPaid = $invoice->payments()->sum('amount');
            $grandTotal = (float) $invoice->grand_total;

            $status = 'unpaid';
            if ($totalPaid >= $grandTotal) {
                $status = 'paid';
            } elseif ($totalPaid > 0) {
                $status = 'partial';
            } else {
                if ($invoice->due_date && $invoice->due_date->isPast()) {
                    $status = 'overdue';
                }
            }

            $invoice->update([
                'paid_amount' => $totalPaid,
                'status' => $status,
            ]);
        }
    }
}
