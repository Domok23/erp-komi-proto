<?php

namespace App\Services;

use App\Models\Project;
use App\Models\SalesOrder;
use App\Models\PoSupplier;
use App\Models\PoSubcon;
use App\Models\GoodsReceipt;
use App\Models\InvoicePurchase;
use App\Models\InvoiceSales;
use App\Models\ProductionOrder;
use App\Models\QcInspection;
use App\Models\JobOrder;
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
}
