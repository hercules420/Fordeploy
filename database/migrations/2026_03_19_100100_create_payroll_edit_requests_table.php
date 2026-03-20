<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_edit_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_owner_id')->constrained('farm_owners')->cascadeOnDelete();
            $table->foreignId('payroll_id')->constrained('payroll')->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('pending');
            $table->text('reason')->nullable();
            $table->json('requested_changes');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['farm_owner_id', 'status']);
            $table->index(['payroll_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_edit_requests');
    }
};
