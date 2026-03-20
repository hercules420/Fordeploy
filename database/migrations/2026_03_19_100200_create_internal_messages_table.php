<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('internal_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_owner_id')->constrained('farm_owners')->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->string('sender_role', 30);
            $table->string('recipient_role', 30);
            $table->string('message_type', 30)->default('general');
            $table->string('subject', 255);
            $table->text('message');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['farm_owner_id', 'recipient_role', 'created_at']);
            $table->index(['farm_owner_id', 'message_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('internal_messages');
    }
};
