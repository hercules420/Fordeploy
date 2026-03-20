<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            $this->rebuildOrdersForSqlite([
                'pending', 'confirmed', 'processing', 'ready_for_pickup', 'shipped', 'delivered', 'completed', 'cancelled', 'refunded',
            ]);

            $this->rebuildDeliveriesForSqlite([
                'preparing', 'packed', 'assigned', 'out_for_delivery', 'delivered', 'completed', 'failed', 'returned', 'cancelled',
            ], 'preparing');

            return;
        }

        DB::statement("ALTER TABLE orders MODIFY status ENUM('pending','confirmed','processing','ready_for_pickup','shipped','delivered','completed','cancelled','refunded') NOT NULL DEFAULT 'pending'");
        DB::statement("ALTER TABLE deliveries MODIFY status ENUM('preparing','packed','assigned','out_for_delivery','delivered','completed','failed','returned','cancelled') NOT NULL DEFAULT 'preparing'");
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            $this->rebuildDeliveriesForSqlite([
                'pending', 'assigned', 'dispatched', 'in_transit', 'delivered', 'failed', 'returned', 'cancelled',
            ], 'pending');

            $this->rebuildOrdersForSqlite([
                'pending', 'confirmed', 'processing', 'ready_for_pickup', 'shipped', 'delivered', 'cancelled', 'refunded',
            ]);

            return;
        }

        DB::statement("ALTER TABLE deliveries MODIFY status ENUM('pending','assigned','dispatched','in_transit','delivered','failed','returned','cancelled') NOT NULL DEFAULT 'pending'");
        DB::statement("ALTER TABLE orders MODIFY status ENUM('pending','confirmed','processing','ready_for_pickup','shipped','delivered','cancelled','refunded') NOT NULL DEFAULT 'pending'");
    }

    private function rebuildOrdersForSqlite(array $statuses): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::create('orders_tmp', function (Blueprint $table) use ($statuses) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('consumer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('farm_owner_id')->constrained('farm_owners')->onDelete('cascade');
            $table->decimal('subtotal', 12, 2);
            $table->decimal('shipping_cost', 10, 2)->default(0);
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('total_amount', 12, 2);
            $table->enum('status', $statuses)->default('pending');
            $table->enum('payment_status', ['unpaid', 'partial', 'paid', 'refunded'])->default('unpaid');
            $table->string('payment_method')->nullable();
            $table->string('paymongo_payment_id')->nullable()->unique();
            $table->enum('delivery_type', ['delivery', 'pickup'])->default('delivery');
            $table->text('delivery_address')->nullable();
            $table->string('delivery_city')->nullable();
            $table->string('delivery_province')->nullable();
            $table->string('delivery_postal_code')->nullable();
            $table->timestamp('scheduled_delivery_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->text('notes')->nullable();
            $table->integer('item_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('consumer_id');
            $table->index('farm_owner_id');
            $table->index('order_number');
            $table->index('status');
            $table->index('payment_status');
            $table->index('created_at');
            $table->index('delivered_at');
        });

        DB::table('orders_tmp')->insertUsing(
            [
                'id', 'order_number', 'consumer_id', 'farm_owner_id', 'subtotal', 'shipping_cost', 'tax', 'discount', 'total_amount',
                'status', 'payment_status', 'payment_method', 'paymongo_payment_id', 'delivery_type', 'delivery_address', 'delivery_city',
                'delivery_province', 'delivery_postal_code', 'scheduled_delivery_at', 'delivered_at', 'notes', 'item_count',
                'created_at', 'updated_at', 'deleted_at',
            ],
            DB::table('orders')->select(
                'id', 'order_number', 'consumer_id', 'farm_owner_id', 'subtotal', 'shipping_cost', 'tax', 'discount', 'total_amount',
                'status', 'payment_status', 'payment_method', 'paymongo_payment_id', 'delivery_type', 'delivery_address', 'delivery_city',
                'delivery_province', 'delivery_postal_code', 'scheduled_delivery_at', 'delivered_at', 'notes', 'item_count',
                'created_at', 'updated_at', 'deleted_at'
            )
        );

        Schema::drop('orders');
        Schema::rename('orders_tmp', 'orders');

        Schema::enableForeignKeyConstraints();
    }

    private function rebuildDeliveriesForSqlite(array $statuses, string $defaultStatus): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::create('deliveries_tmp', function (Blueprint $table) use ($statuses, $defaultStatus) {
            $table->id();
            $table->foreignId('farm_owner_id')->constrained('farm_owners')->cascadeOnDelete();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('tracking_number', 50);
            $table->string('recipient_name', 255);
            $table->string('recipient_phone', 20);
            $table->text('delivery_address');
            $table->string('city', 100)->nullable();
            $table->string('province', 100)->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->date('scheduled_date')->nullable();
            $table->time('scheduled_time_from')->nullable();
            $table->time('scheduled_time_to')->nullable();
            $table->dateTime('dispatched_at')->nullable();
            $table->dateTime('delivered_at')->nullable();
            $table->enum('status', $statuses)->default($defaultStatus);
            $table->string('failure_reason', 255)->nullable();
            $table->decimal('delivery_fee', 10, 2)->default(0);
            $table->decimal('cod_amount', 12, 2)->default(0);
            $table->boolean('cod_collected')->default(false);
            $table->string('proof_of_delivery_url', 500)->nullable();
            $table->text('delivery_notes')->nullable();
            $table->text('special_instructions')->nullable();
            $table->integer('delivery_attempts')->default(0);
            $table->decimal('rating', 3, 2)->nullable();
            $table->text('feedback')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['farm_owner_id', 'status']);
            $table->index(['driver_id', 'status']);
            $table->index(['order_id']);
            $table->index(['scheduled_date']);
        });

        DB::table('deliveries_tmp')->insertUsing(
            [
                'id', 'farm_owner_id', 'order_id', 'driver_id', 'assigned_by', 'tracking_number',
                'recipient_name', 'recipient_phone', 'delivery_address', 'city', 'province', 'postal_code',
                'latitude', 'longitude', 'scheduled_date', 'scheduled_time_from', 'scheduled_time_to',
                'dispatched_at', 'delivered_at', 'status', 'failure_reason', 'delivery_fee', 'cod_amount',
                'cod_collected', 'proof_of_delivery_url', 'delivery_notes', 'special_instructions',
                'delivery_attempts', 'rating', 'feedback', 'created_at', 'updated_at', 'deleted_at',
            ],
            DB::table('deliveries')->select(
                'id', 'farm_owner_id', 'order_id', 'driver_id', 'assigned_by', 'tracking_number',
                'recipient_name', 'recipient_phone', 'delivery_address', 'city', 'province', 'postal_code',
                'latitude', 'longitude', 'scheduled_date', 'scheduled_time_from', 'scheduled_time_to',
                'dispatched_at', 'delivered_at', 'status', 'failure_reason', 'delivery_fee', 'cod_amount',
                'cod_collected', 'proof_of_delivery_url', 'delivery_notes', 'special_instructions',
                'delivery_attempts', 'rating', 'feedback', 'created_at', 'updated_at', 'deleted_at'
            )
        );

        Schema::drop('deliveries');
        Schema::rename('deliveries_tmp', 'deliveries');

        Schema::enableForeignKeyConstraints();
    }
};
