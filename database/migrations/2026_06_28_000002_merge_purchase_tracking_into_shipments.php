<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add actual_arrival to purchase_shipments
        Schema::table('purchase_shipments', function (Blueprint $table) {
            $table->date('actual_arrival')->nullable()->after('eta');
        });

        // 2. Migrate each PurchaseTracking into a PurchaseShipment (raw DB — model may not exist)
        $trackings = DB::table('purchase_trackings')->get();
        foreach ($trackings as $tracking) {
            // Check if a shipment already exists for this PO
            $existing = DB::table('purchase_shipments')
                ->where('company_id', $tracking->company_id)
                ->where('po_type', $tracking->po_type)
                ->where('po_id', $tracking->po_id)
                ->first();

            if ($existing) {
                DB::table('purchase_shipments')
                    ->where('id', $existing->id)
                    ->update([
                        'status' => $tracking->tracking_status === 'delivered' ? 'arrived' : $tracking->tracking_status,
                        'actual_arrival' => $tracking->actual_arrival,
                        'notes' => trim(($existing->notes ?? '') . "\n" . ($tracking->notes ?? '')),
                    ]);
            } else {
                $count = DB::table('purchase_shipments')->count() + 1;
                $year = now()->year;
                DB::table('purchase_shipments')->insert([
                    'company_id' => $tracking->company_id,
                    'shipment_number' => sprintf('SHP-PUR-%d-%03d', $year, $count),
                    'po_type' => $tracking->po_type,
                    'po_id' => $tracking->po_id,
                    'shipment_date' => $tracking->created_at ?? now(),
                    'status' => $tracking->tracking_status === 'delivered' ? 'arrived' : $tracking->tracking_status,
                    'eta' => $tracking->estimated_arrival,
                    'actual_arrival' => $tracking->actual_arrival,
                    'notes' => $tracking->notes,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // 3. Drop old purchase_trackings table
        Schema::dropIfExists('purchase_trackings');
    }

    public function down(): void
    {
        // Recreate purchase_trackings table
        Schema::create('purchase_trackings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('po_type');
            $table->unsignedBigInteger('po_id');
            $table->string('tracking_status')->default('pending');
            $table->date('estimated_arrival')->nullable();
            $table->date('actual_arrival')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'po_type', 'po_id']);
        });

        // Restore data from purchase_shipments
        $shipments = DB::table('purchase_shipments')->get();
        foreach ($shipments as $shipment) {
            DB::table('purchase_trackings')->insert([
                'company_id' => $shipment->company_id,
                'po_type' => $shipment->po_type,
                'po_id' => $shipment->po_id,
                'tracking_status' => match ($shipment->status) {
                    'arrived' => 'delivered',
                    default => $shipment->status,
                },
                'estimated_arrival' => $shipment->eta,
                'actual_arrival' => $shipment->actual_arrival,
                'notes' => $shipment->notes,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::table('purchase_shipments', function (Blueprint $table) {
            $table->dropColumn('actual_arrival');
        });
    }
};
