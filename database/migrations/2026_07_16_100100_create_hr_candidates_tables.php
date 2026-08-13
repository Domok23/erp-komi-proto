<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('name');
            $table->string('nik', 32)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email', 100)->nullable();
            $table->text('address')->nullable();
            $table->string('source')->nullable();
            $table->string('status', 32)->default('screening');
            $table->text('notes')->nullable();
            $table->timestamp('hired_at')->nullable();
            $table->text('hire_override_reason')->nullable();
            $table->foreignId('hire_override_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('hire_override_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'nik']);
        });

        Schema::create('hr_hiring_checklist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained('hr_candidates')->cascadeOnDelete();
            $table->string('type', 32); // mcu | bank_account | other
            $table->string('label');
            $table->boolean('is_required')->default(true);
            $table->string('status', 16)->default('pending'); // pending | done | waived
            $table->string('file_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_hiring_checklist_items');
        Schema::dropIfExists('hr_candidates');
    }
};
