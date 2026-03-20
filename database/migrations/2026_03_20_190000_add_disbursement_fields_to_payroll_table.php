<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll', function (Blueprint $table) {
            $table->foreignId('disbursement_prepared_by')
                ->nullable()
                ->after('payslip_released_by')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('disbursement_prepared_at')->nullable()->after('payslip_released_at');
            $table->foreignId('disbursed_by')
                ->nullable()
                ->after('disbursement_prepared_by')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('disbursed_at')->nullable()->after('disbursement_prepared_at');
            $table->string('disbursement_reference', 120)->nullable()->after('payment_method');

            $table->index(['disbursement_prepared_at']);
            $table->index(['disbursed_at']);
        });
    }

    public function down(): void
    {
        Schema::table('payroll', function (Blueprint $table) {
            $table->dropIndex(['disbursement_prepared_at']);
            $table->dropIndex(['disbursed_at']);

            $table->dropConstrainedForeignId('disbursed_by');
            $table->dropConstrainedForeignId('disbursement_prepared_by');

            $table->dropColumn([
                'disbursement_prepared_at',
                'disbursed_at',
                'disbursement_reference',
            ]);
        });
    }
};
