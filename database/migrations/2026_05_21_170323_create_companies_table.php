<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->string('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('npwp', 30)->nullable();
            $table->enum('type', ['main', 'branch'])->default('main');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
