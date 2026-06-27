<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('costings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('project_id')->constrained('projects')->onDelete('cascade');
            $table->foreignId('design_id')->nullable()->constrained('rd_designs')->onDelete('set null');
            $table->date('costing_date')->nullable();
            $table->string('version')->default('1.0');
            $table->enum('status', ['draft', 'calculated', 'submitted', 'approved', 'rejected'])->default('draft');
            $table->decimal('material_cost', 15, 2)->default(0);
            $table->decimal('mp_cost', 15, 0)->default(0);
            $table->decimal('overhead_pct', 5, 2)->default(15);
            $table->decimal('overhead_amount', 15, 2)->default(0);
            $table->decimal('shipping_cost', 15, 0)->default(0);
            $table->decimal('profit_margin_pct', 5, 2)->default(20);
            $table->decimal('profit_margin_amount', 15, 2)->default(0);
            $table->decimal('landed_cost', 15, 2)->default(0);
            $table->decimal('selling_price', 15, 2)->default(0);
            $table->string('currency')->default('IDR');
            $table->text('notes')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('rejected_at')->nullable();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'project_id']);
            $table->index(['company_id', 'design_id']);
            $table->index(['company_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('costings');
    }
};
