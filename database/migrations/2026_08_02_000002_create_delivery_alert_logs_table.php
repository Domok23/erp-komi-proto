<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_alert_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('alertable_type');
            $table->unsignedBigInteger('alertable_id');
            $table->string('alert_level');
            $table->date('deadline_date');
            $table->integer('days_overdue')->nullable();
            $table->timestamp('sent_at')->useCurrent();
            $table->timestamps();

            // Polymorphic relation index
            $table->index(['alertable_type', 'alertable_id']);

            // Unique composite constraint to prevent duplicate warnings of same level for same PO
            $table->unique(
                ['company_id', 'alertable_type', 'alertable_id', 'alert_level'],
                'delivery_alerts_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_alert_logs');
    }
};
