<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_bulk_order_enabled')->default(false)->after('minimum_order');
            $table->unsignedInteger('order_quantity_step')->default(1)->after('is_bulk_order_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['is_bulk_order_enabled', 'order_quantity_step']);
        });
    }
};
