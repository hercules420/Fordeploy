<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->unsignedTinyInteger('performance_rating')
                ->default(3)
                ->after('monthly_salary');

            $table->index(['farm_owner_id', 'performance_rating']);
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex(['farm_owner_id', 'performance_rating']);
            $table->dropColumn('performance_rating');
        });
    }
};
