<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subcons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('code')->unique();
            $table->string('name');
            $table->enum('service_type', ['embroidery', 'printing', 'sewing', 'cutting', 'finishing', 'other']);
            $table->string('contact_person')->nullable();
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['company_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subcons');
    }
};