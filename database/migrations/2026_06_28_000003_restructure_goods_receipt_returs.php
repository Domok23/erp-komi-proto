<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goods_receipt_retur_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goods_receipt_retur_id')->constrained('goods_receipt_returs')->onDelete('cascade');
            $table->foreignId('material_id')->constrained('materials')->onDelete('cascade');
            $table->decimal('qty_returned', 15, 2);
            $table->string('reason');
            $table->timestamps();
        });

        // Migrate existing flat rows into header + line items
        $groups = DB::table('goods_receipt_returs')
            ->select('goods_receipt_id', 'retur_number')
            ->distinct()
            ->get();

        foreach ($groups as $group) {
            $rows = DB::table('goods_receipt_returs')
                ->where('goods_receipt_id', $group->goods_receipt_id)
                ->where('retur_number', $group->retur_number)
                ->orderBy('id')
                ->get();

            if ($rows->isEmpty()) {
                continue;
            }

            $headerId = $rows->first()->id;

            foreach ($rows as $row) {
                DB::table('goods_receipt_retur_items')->insert([
                    'goods_receipt_retur_id' => $headerId,
                    'material_id' => $row->material_id,
                    'qty_returned' => $row->qty_returned,
                    'reason' => $row->reason,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ]);

                if ($row->id !== $headerId) {
                    DB::table('goods_receipt_returs')->where('id', $row->id)->delete();
                }
            }
        }

        Schema::table('goods_receipt_returs', function (Blueprint $table) {
            $table->dropForeign(['material_id']);
            $table->dropColumn(['material_id', 'qty_returned', 'reason']);
            $table->text('notes')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('goods_receipt_returs', function (Blueprint $table) {
            $table->foreignId('material_id')->nullable()->after('retur_number')->constrained('materials')->onDelete('cascade');
            $table->decimal('qty_returned', 15, 2)->nullable()->after('material_id');
            $table->string('reason')->nullable()->after('qty_returned');
            $table->dropColumn('notes');
        });

        $items = DB::table('goods_receipt_retur_items')->get();
        foreach ($items as $item) {
            $header = DB::table('goods_receipt_returs')->where('id', $item->goods_receipt_retur_id)->first();
            if (! $header) {
                continue;
            }

            $existing = DB::table('goods_receipt_returs')
                ->where('goods_receipt_id', $header->goods_receipt_id)
                ->where('retur_number', $header->retur_number)
                ->whereNotNull('material_id')
                ->exists();

            if (! $existing) {
                DB::table('goods_receipt_returs')
                    ->where('id', $header->id)
                    ->update([
                        'material_id' => $item->material_id,
                        'qty_returned' => $item->qty_returned,
                        'reason' => $item->reason,
                    ]);
            } else {
                DB::table('goods_receipt_returs')->insert([
                    'goods_receipt_id' => $header->goods_receipt_id,
                    'retur_number' => $header->retur_number,
                    'material_id' => $item->material_id,
                    'qty_returned' => $item->qty_returned,
                    'reason' => $item->reason,
                    'status' => $header->status,
                    'created_at' => $item->created_at,
                    'updated_at' => $item->updated_at,
                ]);
            }
        }

        Schema::dropIfExists('goods_receipt_retur_items');
    }
};
