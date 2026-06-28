<?php

namespace App\Services;

use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptRetur;
use App\Models\InvoicePurchase;
use App\Models\InvoiceSales;
use App\Models\JobOrder;
use App\Models\Payment;
use App\Models\PoSubcon;
use App\Models\PoSupplier;
use App\Models\ProductionOrder;
use App\Models\Project;
use App\Models\PurchaseShipment;
use App\Models\QcInspection;
use App\Models\SalesOrder;
use App\Models\StockTransfer;
use Carbon\Carbon;

class CodeGenerator
{
    public static function generateProjectCode(): string
    {
        $year = Carbon::now()->year;
        $count = Project::whereYear('created_at', $year)->count() + 1;
        do {
            $code = sprintf('PRJ-%03d-%d', $count, $year);
            $count++;
        } while (Project::where('code', $code)->exists());

        return $code;
    }

    public static function generateSONumber(): string
    {
        $year = Carbon::now()->year;
        $count = SalesOrder::whereYear('created_at', $year)->count() + 1;
        do {
            $number = sprintf('SO-%d-%03d', $year, $count);
            $count++;
        } while (SalesOrder::where('so_number', $number)->exists());

        return $number;
    }

    public static function generatePOSupplierNo(): string
    {
        $year = Carbon::now()->year;
        $count = PoSupplier::whereYear('created_at', $year)->count() + 1;
        do {
            $number = sprintf('PO-SUP-%d-%03d', $year, $count);
            $count++;
        } while (PoSupplier::where('po_number', $number)->exists());

        return $number;
    }

    public static function generatePOSubconNo(): string
    {
        $year = Carbon::now()->year;
        $count = PoSubcon::whereYear('created_at', $year)->count() + 1;
        do {
            $number = sprintf('PO-SUBCON-%d-%03d', $year, $count);
            $count++;
        } while (PoSubcon::where('po_number', $number)->exists());

        return $number;
    }

    public static function generateGRNumber(): string
    {
        $year = Carbon::now()->year;
        $count = GoodsReceipt::whereYear('created_at', $year)->count() + 1;
        do {
            $number = sprintf('GR-%d-%03d', $year, $count);
            $count++;
        } while (GoodsReceipt::where('gr_number', $number)->exists());

        return $number;
    }

    public static function generateInvoicePurchaseNo(): string
    {
        $year = Carbon::now()->year;
        $count = InvoicePurchase::whereYear('created_at', $year)->count() + 1;
        do {
            $number = sprintf('INV-PUR-%d-%03d', $year, $count);
            $count++;
        } while (InvoicePurchase::where('invoice_number', $number)->exists());

        return $number;
    }

    public static function generateInvoiceSalesNo(): string
    {
        $year = Carbon::now()->year;
        $count = InvoiceSales::whereYear('created_at', $year)->count() + 1;
        do {
            $number = sprintf('INV-SALES-%d-%03d', $year, $count);
            $count++;
        } while (InvoiceSales::where('invoice_number', $number)->exists());

        return $number;
    }

    public static function generateProductionOrderNumber(): string
    {
        $year = Carbon::now()->year;
        $count = ProductionOrder::whereYear('created_at', $year)->count() + 1;
        return sprintf('PO-%d-%03d', $year, $count);
    }

    public static function generateQcInspectionNumber(): string
    {
        $year = Carbon::now()->year;
        $count = QcInspection::whereYear('created_at', $year)->count() + 1;
        return sprintf('QC-%d-%03d', $year, $count);
    }

    public static function generateJobOrderNumber(): string
    {
        $year = Carbon::now()->year;
        $count = JobOrder::whereYear('created_at', $year)->count() + 1;
        return sprintf('JO-%d-%03d', $year, $count);
    }

    public static function generateTransferNumber(): string
    {
        $year = Carbon::now()->year;
        $count = StockTransfer::whereYear('created_at', $year)->count() + 1;
        do {
            $number = sprintf('ST-%d-%03d', $year, $count);
            $count++;
        } while (StockTransfer::where('transfer_number', $number)->exists());

        return $number;
    }

    public static function generatePurchaseShipmentNo(): string
    {
        $year = Carbon::now()->year;
        $count = PurchaseShipment::whereYear('created_at', $year)->count() + 1;
        do {
            $number = sprintf('SHP-PUR-%d-%03d', $year, $count);
            $count++;
        } while (PurchaseShipment::where('shipment_number', $number)->exists());

        return $number;
    }

    public static function generateGRReturNumber(array $excludeNumbers = []): string
    {
        $year = Carbon::now()->year;
        $count = GoodsReceiptRetur::whereYear('created_at', $year)->count() + 1;
        do {
            $number = sprintf('RET-GR-%d-%03d', $year, $count);
            $count++;
        } while (
            GoodsReceiptRetur::where('retur_number', $number)->exists() ||
            in_array($number, $excludeNumbers)
        );

        return $number;
    }

    public static function generatePaymentNumber(string $invoiceType): string
    {
        $year = Carbon::now()->year;
        $prefix = $invoiceType === 'sales' ? 'PAY-SALES' : 'PAY-PUR';
        $count = Payment::where('invoice_type', $invoiceType)
            ->whereYear('payment_date', $year)
            ->count() + 1;
        do {
            $number = sprintf('%s-%d-%03d', $prefix, $year, $count);
            $count++;
        } while (Payment::where('payment_number', $number)->exists());

        return $number;
    }
}
