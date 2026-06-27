<?php

namespace App\Services;

use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptRetur;
use App\Models\InvoicePurchase;
use App\Models\InvoiceSales;
use App\Models\PoSubcon;
use App\Models\PoSupplier;
use App\Models\Project;
use App\Models\PurchaseShipment;
use App\Models\SalesOrder;
use App\Models\StockTransfer;
use Carbon\Carbon;

class CodeGenerator
{
    public static function generateProjectCode(): string
    {
        $year = Carbon::now()->year;
        $count = Project::whereYear('created_at', $year)->count() + 1;

        return sprintf('PRJ-%03d-%d', $count, $year);
    }

    public static function generateSONumber(): string
    {
        $year = Carbon::now()->year;
        $count = SalesOrder::whereYear('created_at', $year)->count() + 1;

        return sprintf('SO-%d-%03d', $year, $count);
    }

    public static function generatePOSupplierNo(): string
    {
        $year = Carbon::now()->year;
        $count = PoSupplier::whereYear('created_at', $year)->count() + 1;

        return sprintf('PO-SUP-%d-%03d', $year, $count);
    }

    public static function generatePOSubconNo(): string
    {
        $year = Carbon::now()->year;
        $count = PoSubcon::whereYear('created_at', $year)->count() + 1;

        return sprintf('PO-SUBCON-%d-%03d', $year, $count);
    }

    public static function generateGRNumber(): string
    {
        $year = Carbon::now()->year;
        $count = GoodsReceipt::whereYear('created_at', $year)->count() + 1;

        return sprintf('GR-%d-%03d', $year, $count);
    }

    public static function generateInvoicePurchaseNo(): string
    {
        $year = Carbon::now()->year;
        $count = InvoicePurchase::whereYear('created_at', $year)->count() + 1;

        return sprintf('INV-PUR-%d-%03d', $year, $count);
    }

    public static function generateInvoiceSalesNo(): string
    {
        $year = Carbon::now()->year;
        $count = InvoiceSales::whereYear('created_at', $year)->count() + 1;

        return sprintf('INV-SALES-%d-%03d', $year, $count);
    }

    public static function generateTransferNumber(): string
    {
        $year = Carbon::now()->year;
        $count = StockTransfer::whereYear('created_at', $year)->count() + 1;

        return sprintf('ST-%d-%03d', $year, $count);
    }

    public static function generatePurchaseShipmentNo(): string
    {
        $year = Carbon::now()->year;
        $count = PurchaseShipment::whereYear('created_at', $year)->count() + 1;

        return sprintf('SHP-PUR-%d-%03d', $year, $count);
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
}
