<?php

namespace Tests\Feature;

use App\Models\FarmOwner;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\PayMongoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileMarketplaceAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_profile_requires_bearer_token(): void
    {
        $response = $this->getJson('/api/mobile/profile');

        $response
            ->assertStatus(401)
            ->assertJson([
                'message' => 'Authentication token is required.',
            ]);
    }

    public function test_consumer_can_log_in_and_access_profile_with_bearer_token(): void
    {
        $user = User::factory()->create([
            'role' => 'consumer',
            'status' => 'active',
            'location' => 'Test City',
        ]);

        $loginResponse = $this->postJson('/api/mobile/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $loginResponse
            ->assertOk()
            ->assertJsonPath('data.email', $user->email);

        $token = $loginResponse->json('data.token');

        $this->assertIsString($token);
        $this->assertNotSame('', $token);

        $profileResponse = $this
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/mobile/profile');

        $profileResponse
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.email', $user->email)
            ->assertJsonPath('data.location', 'Test City');
    }

    public function test_consumer_can_create_an_online_payment_order(): void
    {
        $consumer = User::factory()->create([
            'role' => 'consumer',
            'status' => 'active',
        ]);

        $farmOwnerUser = User::factory()->create([
            'role' => 'farm_owner',
            'status' => 'active',
        ]);

        $farmOwner = FarmOwner::create([
            'user_id' => $farmOwnerUser->id,
            'farm_name' => 'Sunrise Poultry',
            'farm_address' => 'Farm Road',
            'city' => 'Tarlac City',
            'province' => 'Tarlac',
            'postal_code' => '2300',
            'permit_status' => 'approved',
            'subscription_status' => 'active',
        ]);

        $product = Product::create([
            'farm_owner_id' => $farmOwner->id,
            'sku' => 'EGG-TRAY-001',
            'name' => 'Fresh Eggs',
            'category' => 'eggs',
            'status' => 'active',
            'quantity_available' => 25,
            'price' => 180,
            'unit' => 'tray',
            'published_at' => now(),
        ]);

        $this->mock(PayMongoService::class, function ($mock) {
            $mock->shouldReceive('createCheckoutSession')
                ->once()
                ->andReturn([
                    'id' => 'cs_test_order_123',
                    'attributes' => [
                        'checkout_url' => 'https://paymongo.test/checkout/order-123',
                    ],
                ]);
        });

        $token = $this->postJson('/api/mobile/auth/login', [
            'email' => $consumer->email,
            'password' => 'password',
        ])->json('data.token');

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/mobile/orders', [
                'delivery_address' => '123 Example Street',
                'delivery_city' => 'Tarlac City',
                'delivery_province' => 'Tarlac',
                'delivery_postal_code' => '2300',
                'payment_method' => 'gcash',
                'items' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 2,
                    ],
                ],
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.payment.provider', 'paymongo')
            ->assertJsonPath('data.payment.method', 'gcash')
            ->assertJsonPath('data.payment.checkout_url', 'https://paymongo.test/checkout/order-123');

        $this->assertDatabaseHas('orders', [
            'consumer_id' => $consumer->id,
            'farm_owner_id' => $farmOwner->id,
            'payment_method' => 'gcash',
            'paymongo_payment_id' => 'cs_test_order_123',
        ]);

        $this->assertDatabaseHas('order_items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
    }

    public function test_consumer_can_cancel_unpaid_order_and_stock_is_restored(): void
    {
        $consumer = User::factory()->create([
            'role' => 'consumer',
            'status' => 'active',
        ]);

        $farmOwnerUser = User::factory()->create([
            'role' => 'farm_owner',
            'status' => 'active',
        ]);

        $farmOwner = FarmOwner::create([
            'user_id' => $farmOwnerUser->id,
            'farm_name' => 'Golden Hen Farm',
            'farm_address' => 'Barangay 1',
            'city' => 'Capas',
            'province' => 'Tarlac',
            'postal_code' => '2315',
            'permit_status' => 'approved',
            'subscription_status' => 'active',
        ]);

        $product = Product::create([
            'farm_owner_id' => $farmOwner->id,
            'sku' => 'CHK-LIVE-001',
            'name' => 'Native Chicken',
            'category' => 'live_stock',
            'status' => 'active',
            'quantity_available' => 8,
            'quantity_sold' => 2,
            'price' => 350,
            'unit' => 'piece',
            'published_at' => now(),
        ]);

        $order = Order::create([
            'order_number' => 'ORD-TEST-CANCEL-001',
            'consumer_id' => $consumer->id,
            'farm_owner_id' => $farmOwner->id,
            'subtotal' => 700,
            'shipping_cost' => 100,
            'tax' => 84,
            'discount' => 0,
            'total_amount' => 884,
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'payment_method' => 'cod',
            'delivery_type' => 'delivery',
            'delivery_address' => '123 Example Street',
            'item_count' => 2,
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 350,
            'total_price' => 700,
            'product_attributes' => ['name' => 'Native Chicken'],
        ]);

        $product->update([
            'quantity_available' => 6,
            'quantity_sold' => 4,
        ]);

        $token = $this->postJson('/api/mobile/auth/login', [
            'email' => $consumer->email,
            'password' => 'password',
        ])->json('data.token');

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/mobile/orders/{$order->id}/cancel");

        $response
            ->assertOk()
            ->assertJson([
                'message' => 'Order cancelled successfully.',
            ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'cancelled',
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'quantity_available' => 8,
            'quantity_sold' => 2,
        ]);
    }

    public function test_consumer_can_retry_unpaid_online_order_payment(): void
    {
        $consumer = User::factory()->create([
            'role' => 'consumer',
            'status' => 'active',
        ]);

        $farmOwnerUser = User::factory()->create([
            'role' => 'farm_owner',
            'status' => 'active',
        ]);

        $farmOwner = FarmOwner::create([
            'user_id' => $farmOwnerUser->id,
            'farm_name' => 'Retry Farm',
            'farm_address' => 'North Road',
            'city' => 'Concepcion',
            'province' => 'Tarlac',
            'postal_code' => '2316',
            'permit_status' => 'approved',
            'subscription_status' => 'active',
        ]);

        $product = Product::create([
            'farm_owner_id' => $farmOwner->id,
            'sku' => 'RETRY-PROD-001',
            'name' => 'Retry Eggs',
            'category' => 'eggs',
            'status' => 'active',
            'quantity_available' => 10,
            'price' => 200,
            'unit' => 'tray',
            'published_at' => now(),
        ]);

        $order = Order::create([
            'order_number' => 'ORD-TEST-RETRY-001',
            'consumer_id' => $consumer->id,
            'farm_owner_id' => $farmOwner->id,
            'subtotal' => 400,
            'shipping_cost' => 100,
            'tax' => 48,
            'discount' => 0,
            'total_amount' => 548,
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'payment_method' => 'gcash',
            'delivery_type' => 'delivery',
            'delivery_address' => 'Retry Street',
            'item_count' => 2,
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 200,
            'total_price' => 400,
            'product_attributes' => ['name' => 'Retry Eggs'],
        ]);

        $this->mock(PayMongoService::class, function ($mock) {
            $mock->shouldReceive('createCheckoutSession')
                ->once()
                ->andReturn([
                    'id' => 'cs_test_retry_123',
                    'attributes' => [
                        'checkout_url' => 'https://paymongo.test/checkout/retry-123',
                    ],
                ]);
        });

        $token = $this->postJson('/api/mobile/auth/login', [
            'email' => $consumer->email,
            'password' => 'password',
        ])->json('data.token');

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/mobile/orders/{$order->id}/retry-payment");

        $response
            ->assertOk()
            ->assertJsonPath('data.order_id', $order->id)
            ->assertJsonPath('data.payment.method', 'gcash')
            ->assertJsonPath('data.payment.checkout_url', 'https://paymongo.test/checkout/retry-123');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'paymongo_payment_id' => 'cs_test_retry_123',
        ]);
    }
}