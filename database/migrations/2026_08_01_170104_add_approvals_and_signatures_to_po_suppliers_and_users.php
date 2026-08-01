<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add signature field to users
        Schema::table('users', function (Blueprint $table) {
            $table->longText('signature')->nullable();
        });

        // 2. Modify po_suppliers for approval and revision tracking
        Schema::table('po_suppliers', function (Blueprint $table) {
            $table->string('approval_status')->default('draft')->after('status');
            $table->foreignId('parent_id')->nullable()->after('approval_status')
                ->constrained('po_suppliers')->onDelete('cascade');
            $table->integer('revision_number')->default(0)->after('parent_id');
        });

        // 3. Create po_supplier_approvals table
        Schema::create('po_supplier_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('po_supplier_id')->constrained('po_suppliers')->onDelete('cascade');
            $table->string('approval_level'); // 'manager', 'director'
            $table->string('status')->default('pending'); // 'pending', 'approved', 'rejected'
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->longText('signature_path')->nullable(); // stores base64 E-Sign PNG data
            $table->text('rejection_reason')->nullable();
            $table->timestamp('actioned_at')->nullable();
            $table->timestamps();

            $table->index(['po_supplier_id', 'approval_level']);
            $table->index(['po_supplier_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('po_supplier_approvals');

        Schema::table('po_suppliers', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn(['approval_status', 'parent_id', 'revision_number']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('signature');
        });
    }
};
