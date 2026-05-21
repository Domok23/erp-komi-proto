<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['proto', 'sample', 'mass'])->default('proto');
            $table->enum('status', [
                'planning',
                'development',
                'sampling',
                'approved',
                'production',
                'completed',
                'cancelled',
            ])->default('planning');
            $table->foreignId('customer_id')->nullable()->constrained('customers')->onDelete('set null');
            $table->foreignId('sales_order_id')->nullable()->constrained('sales_orders')->onDelete('set null');
            $table->foreignId('design_id')->nullable()->constrained('rd_designs')->onDelete('set null');
            $table->date('start_date')->nullable();
            $table->date('target_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('target_qty')->default(0);
            $table->integer('produced_qty')->default(0);
            $table->timestamps();

            $table->index(['company_id', 'code']);
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'type']);
            $table->index(['company_id', 'customer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};