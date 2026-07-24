<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hr_warning_letters', function (Blueprint $table) {
            $table->string('level', 16)->default('sp_1')->after('employee_id');
        });
    }

    public function down(): void
    {
        Schema::table('hr_warning_letters', function (Blueprint $table) {
            $table->dropColumn('level');
        });
    }
};
