<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll', function (Blueprint $table) {
            $table->string('workflow_status', 40)->default('draft')->after('status');
            $table->decimal('regular_hours', 8, 2)->default(0)->after('hours_worked');
            $table->decimal('hourly_rate', 12, 4)->default(0)->after('regular_hours');
            $table->decimal('late_deduction', 10, 2)->default(0)->after('tax_deduction');
            $table->decimal('insurance_deduction', 10, 2)->default(0)->after('loan_deduction');
            $table->decimal('reimbursement_deduction', 10, 2)->default(0)->after('insurance_deduction');
            $table->foreignId('finance_approved_by')->nullable()->after('processed_by')->constrained('users')->nullOnDelete();
            $table->timestamp('finance_approved_at')->nullable()->after('workflow_status');
            $table->foreignId('owner_approved_by')->nullable()->after('finance_approved_by')->constrained('users')->nullOnDelete();
            $table->timestamp('owner_approved_at')->nullable()->after('finance_approved_at');
            $table->foreignId('payslip_released_by')->nullable()->after('owner_approved_by')->constrained('users')->nullOnDelete();
            $table->timestamp('payslip_released_at')->nullable()->after('owner_approved_at');

            $table->index(['farm_owner_id', 'workflow_status']);
            $table->index(['finance_approved_at']);
            $table->index(['payslip_released_at']);
        });
    }

    public function down(): void
    {
        Schema::table('payroll', function (Blueprint $table) {
            $table->dropIndex(['farm_owner_id', 'workflow_status']);
            $table->dropIndex(['finance_approved_at']);
            $table->dropIndex(['payslip_released_at']);

            $table->dropConstrainedForeignId('payslip_released_by');
            $table->dropConstrainedForeignId('owner_approved_by');
            $table->dropConstrainedForeignId('finance_approved_by');

            $table->dropColumn([
                'workflow_status',
                'regular_hours',
                'hourly_rate',
                'late_deduction',
                'insurance_deduction',
                'reimbursement_deduction',
                'finance_approved_at',
                'owner_approved_at',
                'payslip_released_at',
            ]);
        });
    }
};
