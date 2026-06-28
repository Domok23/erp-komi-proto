<?php

namespace App\Services;

use App\Models\InvoicePurchase;
use App\Models\InvoiceSales;
use App\Models\PoSubcon;
use App\Models\PoSupplier;
use App\Models\SalesOrder;
use Carbon\Carbon;

class InvoiceGeneratorService
{
    public static function generateFromSO(SalesOrder $so): InvoiceSales
    {
        return InvoiceSales::create([
            'company_id' => $so->company_id,
            'sales_order_id' => $so->id,
            'invoice_number' => CodeGenerator::generateInvoiceSalesNo(),
            'invoice_date' => Carbon::now()->toDateString(),
            'due_date' => Carbon::now()->addDays(30)->toDateString(),
            'subtotal' => $so->subtotal,
            'ppn_percent' => $so->ppn_percent,
            'ppn_amount' => $so->ppn_amount,
            'shipping_cost' => $so->shipping_cost,
            'grand_total' => $so->grand_total,
            'paid_amount' => 0,
            'status' => 'unpaid',
            'is_tax_invoice' => false,
            'notes' => 'Generated automatically from SO '.$so->so_number,
        ]);
    }

    public static function generateFromPO(PoSupplier $po): InvoicePurchase
    {
        return InvoicePurchase::create([
            'company_id' => $po->company_id,
            'invoice_number' => CodeGenerator::generateInvoicePurchaseNo(),
            'purchase_type' => 'po_supplier',
            'reference_id' => $po->id,
            'invoice_date' => Carbon::now()->toDateString(),
            'due_date' => Carbon::now()->addDays(30)->toDateString(),
            'subtotal' => $po->subtotal,
            'tax_amount' => $po->ppn_amount,
            'grand_total' => $po->grand_total,
            'paid_amount' => 0,
            'status' => 'unpaid',
            'notes' => 'Generated automatically from Supplier PO '.$po->po_number,
        ]);
    }

    public static function generateFromSubconPO(PoSubcon $po): InvoicePurchase
    {
        return InvoicePurchase::create([
            'company_id' => $po->company_id,
            'invoice_number' => CodeGenerator::generateInvoicePurchaseNo(),
            'purchase_type' => 'po_subcon',
            'reference_id' => $po->id,
            'invoice_date' => Carbon::now()->toDateString(),
            'due_date' => Carbon::now()->addDays(30)->toDateString(),
            'subtotal' => $po->service_cost,
            'tax_amount' => 0, // subcon service tax can be customized if needed
            'grand_total' => $po->total_cost,
            'paid_amount' => 0,
            'status' => 'unpaid',
            'notes' => 'Generated automatically from Subcon PO '.$po->po_number,
        ]);
    }
}
