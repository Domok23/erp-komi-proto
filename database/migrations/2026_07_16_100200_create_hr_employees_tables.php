<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('candidate_id')->nullable()->constrained('hr_candidates')->nullOnDelete();
            $table->string('employee_number', 50);
            $table->string('name');
            $table->string('nik', 32)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email', 100)->nullable();
            $table->text('address')->nullable();
            $table->foreignId('department_id')->nullable()->constrained('hr_departments')->nullOnDelete();
            $table->foreignId('position_id')->nullable()->constrained('hr_positions')->nullOnDelete();
            $table->date('join_date')->nullable();
            $table->string('status', 16)->default('active'); // active | inactive
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'employee_number']);
            $table->unique(['company_id', 'nik']);
        });

        Schema::create('hr_employment_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();
            $table->string('type', 16); // pkwt | pkwtt
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('file_path')->nullable();
            $table->string('status', 16)->default('active'); // active | ended
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('hr_employee_placements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();
            $table->foreignId('project_id')->constrained('projects')->restrictOnDelete();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('status', 16)->default('active'); // active | ended
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_employee_placements');
        Schema::dropIfExists('hr_employment_contracts');
        Schema::dropIfExists('hr_employees');
    }
};
