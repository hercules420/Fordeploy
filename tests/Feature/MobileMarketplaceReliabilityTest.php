<?php

namespace Tests\Feature;

use App\Models\FarmOwner;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileMarketplaceReliabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_burst_checkout_keeps_stock_consistent(): void
    {
        [$consumer, $product] = $this->createConsumerAndProduct(stock: 120, price: 100);

        $token = $this->loginAndGetToken($consumer);
        $attempts = 20;
        $success = 0;

        for ($i = 1; $i <= $attempts; $i++) {
            $response = $this
                ->withHeader('Authorization', 'Bearer ' . $token)
                ->postJson('/api/mobile/orders', [
                    'delivery_address' => "Burst Address {$i}",
                    'delivery_city' => 'Tarlac City',
                    'delivery_province' => 'Tarlac',
                    'delivery_postal_code' => '2300',
                    'payment_method' => 'cod',
                    'items' => [
                        [
                            'product_id' => $product->id,
                            'quantity' => 1,
                        ],
                    ],
                ]);

            if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                $success++;
            }
        }

        $product->refresh();

        $this->assertSame($attempts, $success, 'All burst checkouts should succeed with sufficient stock.');
        $this->assertSame(120 - $success, (int) $product->quantity_available);
        $this->assertSame($success, (int) $product->quantity_sold);
    }

    public function test_orders_and_notifications_api_latency_under_basic_load(): void
    {
        [$consumer] = $this->createConsumerAndProduct(stock: 80, price: 95);
        $token = $this->loginAndGetToken($consumer);

        $orderLatencies = [];
        $notificationLatencies = [];

        for ($i = 0; $i < 20; $i++) {
            $start = microtime(true);
            $ordersResponse = $this
                ->withHeader('Authorization', 'Bearer ' . $token)
                ->getJson('/api/mobile/orders');
            $orderLatencies[] = (microtime(true) - $start) * 1000;
            $ordersResponse->assertOk();

            $start = microtime(true);
            $notificationsResponse = $this
                ->withHeader('Authorization', 'Bearer ' . $token)
                ->getJson('/api/mobile/notifications');
            $notificationLatencies[] = (microtime(true) - $start) * 1000;
            $notificationsResponse->assertOk();
        }

        $ordersAvgMs = array_sum($orderLatencies) / count($orderLatencies);
        $notificationsAvgMs = array_sum($notificationLatencies) / count($notificationLatencies);

        // Generous threshold for local dev reliability checks.
        $this->assertLessThan(2000, $ordersAvgMs, 'Orders endpoint average latency should stay below 2s.');
        $this->assertLessThan(2000, $notificationsAvgMs, 'Notifications endpoint average latency should stay below 2s.');
    }

    public function test_cancel_burst_restores_stock_consistency(): void
    {
        [$consumer, $product] = $this->createConsumerAndProduct(stock: 60, price: 120);
        $token = $this->loginAndGetToken($consumer);

        $createdOrderIds = [];
        for ($i = 1; $i <= 10; $i++) {
            $response = $this
                ->withHeader('Authorization', 'Bearer ' . $token)
                ->postJson('/api/mobile/orders', [
                    'delivery_address' => "Cancel Address {$i}",
                    'delivery_city' => 'Tarlac City',
                    'delivery_province' => 'Tarlac',
                    'delivery_postal_code' => '2300',
                    'payment_method' => 'cod',
                    'items' => [
                        [
                            'product_id' => $product->id,
                            'quantity' => 1,
                        ],
                    ],
                ]);

            $response->assertCreated();
            $createdOrderIds[] = (int) $response->json('data.order_id');
        }

        $cancelIds = array_slice($createdOrderIds, 0, 6);
        foreach ($cancelIds as $orderId) {
            $cancelResponse = $this
                ->withHeader('Authorization', 'Bearer ' . $token)
                ->postJson("/api/mobile/orders/{$orderId}/cancel");

            $cancelResponse->assertOk();
        }

        $product->refresh();

        $this->assertSame(60 - 10 + 6, (int) $product->quantity_available);
        $this->assertSame(10 - 6, (int) $product->quantity_sold);

        $cancelledCount = Order::whereIn('id', $cancelIds)
            ->where('status', 'cancelled')
            ->count();

        $this->assertSame(count($cancelIds), $cancelledCount);
    }

    private function createConsumerAndProduct(int $stock, int $price): array
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
            'farm_name' => 'Reliability Farm',
            'farm_address' => 'Reliability Address',
            'city' => 'Tarlac City',
            'province' => 'Tarlac',
            'postal_code' => '2300',
            'permit_status' => 'approved',
            'subscription_status' => 'active',
        ]);

        $product = Product::create([
            'farm_owner_id' => $farmOwner->id,
            'sku' => 'REL-' . strtoupper((string) fake()->bothify('###???')),
            'name' => 'Reliability Product',
            'category' => 'eggs',
            'status' => 'active',
            'quantity_available' => $stock,
            'quantity_sold' => 0,
            'price' => $price,
            'unit' => 'tray',
            'published_at' => now(),
        ]);

        return [$consumer, $product];
    }

    private function loginAndGetToken(User $consumer): string
    {
        $response = $this->postJson('/api/mobile/auth/login', [
            'email' => $consumer->email,
            'password' => 'password',
        ]);

        $response->assertOk();

        $token = $response->json('data.token');
        $this->assertIsString($token);
        $this->assertNotSame('', $token);

        return $token;
    }
}
