<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('client_requests', function (Blueprint $table): void {
            $table->id();
            $table->string('owner_name');
            $table->string('farm_name');
            $table->string('email')->unique();
            $table->text('farm_location');
            $table->string('valid_id_path');
            $table->string('business_permit_path');
            $table->string('password');
            $table->string('status')->default('pending');
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_requests');
    }
};
