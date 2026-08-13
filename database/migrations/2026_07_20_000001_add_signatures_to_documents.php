<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Sales Orders — customer signature
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->text('customer_signature')->nullable()->after('notes');
        });

        // Goods Receipts — receiver signature
        Schema::table('goods_receipts', function (Blueprint $table) {
            $table->text('receiver_signature')->nullable()->after('notes');
        });

        // PO Suppliers — management signature
        Schema::table('po_suppliers', function (Blueprint $table) {
            $table->text('buyer_signature')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropColumn('customer_signature');
        });

        Schema::table('goods_receipts', function (Blueprint $table) {
            $table->dropColumn('receiver_signature');
        });

        Schema::table('po_suppliers', function (Blueprint $table) {
            $table->dropColumn('buyer_signature');
        });
    }
};
