<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_warning_letters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();
            $table->string('letter_number', 50);
            $table->date('issued_date');
            $table->text('reason');
            $table->string('file_path')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'letter_number']);
        });

        Schema::create('hr_position_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();
            $table->foreignId('from_department_id')->nullable()->constrained('hr_departments')->nullOnDelete();
            $table->foreignId('from_position_id')->nullable()->constrained('hr_positions')->nullOnDelete();
            $table->foreignId('to_department_id')->nullable()->constrained('hr_departments')->nullOnDelete();
            $table->foreignId('to_position_id')->nullable()->constrained('hr_positions')->nullOnDelete();
            $table->string('type', 16); // promotion | demotion | transfer
            $table->date('effective_date');
            $table->text('reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_position_histories');
        Schema::dropIfExists('hr_warning_letters');
    }
};
