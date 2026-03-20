<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->string('source_type', 50)->nullable()->after('expense_number');
            $table->unsignedBigInteger('source_id')->nullable()->after('source_type');
            $table->boolean('is_auto_generated')->default(false)->after('source_id');

            $table->index(['source_type', 'source_id'], 'expenses_source_lookup_idx');
            $table->unique(['source_type', 'source_id'], 'expenses_source_unique_idx');
            $table->index(['farm_owner_id', 'is_auto_generated'], 'expenses_farm_auto_idx');
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropUnique('expenses_source_unique_idx');
            $table->dropIndex('expenses_source_lookup_idx');
            $table->dropIndex('expenses_farm_auto_idx');

            $table->dropColumn(['source_type', 'source_id', 'is_auto_generated']);
        });
    }
};
