<?php

namespace Tests\Feature;

use App\Models\FarmOwner;
use App\Models\Notification;
use App\Models\Order;
use App\Models\PayMongoWebhookEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayMongoWebhookIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_paymongo_webhook_event_is_processed_once_for_marketplace_orders(): void
    {
        config(['services.paymongo.webhook_secret' => null]);

        $consumer = User::factory()->create([
            'role' => 'consumer',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $farmOwnerUser = User::factory()->create([
            'role' => 'farm_owner',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $farmOwner = FarmOwner::create([
            'user_id' => $farmOwnerUser->id,
            'farm_name' => 'Webhook Demo Farm',
            'farm_address' => 'Sitio Demo',
            'city' => 'Cebu City',
            'province' => 'Cebu',
            'postal_code' => '6000',
            'permit_status' => 'approved',
            'subscription_status' => 'active',
        ]);

        $order = Order::create([
            'order_number' => 'ORD-WEBHOOK-1001',
            'consumer_id' => $consumer->id,
            'farm_owner_id' => $farmOwner->id,
            'subtotal' => 1000,
            'shipping_cost' => 100,
            'tax' => 120,
            'discount' => 0,
            'total_amount' => 1220,
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'payment_method' => 'gcash',
            'paymongo_payment_id' => 'cs_test_checkout_123',
            'delivery_type' => 'delivery',
            'delivery_address' => 'Test Address',
            'item_count' => 1,
        ]);

        $payload = [
            'data' => [
                'id' => 'evt_marketplace_paid_001',
                'attributes' => [
                    'type' => 'checkout_session.payment.paid',
                    'data' => [
                        'id' => 'cs_test_checkout_123',
                        'attributes' => [
                            'metadata' => [
                                'purpose' => 'marketplace_order',
                                'order_id' => (string) $order->id,
                                'payment_method' => 'gcash',
                            ],
                            'payments' => [
                                ['id' => 'pay_test_payment_001'],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->postJson(route('webhooks.paymongo'), $payload)
            ->assertStatus(200)
            ->assertJson(['status' => 'success']);

        $order->refresh();
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('cs_test_checkout_123', $order->paymongo_payment_id);
        $this->assertSame(2, Notification::count());

        $this->postJson(route('webhooks.paymongo'), $payload)
            ->assertStatus(200)
            ->assertJson(['status' => 'duplicate_ignored']);

        $order->refresh();
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame(2, Notification::count());

        $this->assertSame(1, PayMongoWebhookEvent::count());

        $event = PayMongoWebhookEvent::first();
        $this->assertNotNull($event);
        $this->assertSame('evt_marketplace_paid_001', $event->event_id);
        $this->assertSame('processed', $event->status);
        $this->assertSame(200, $event->response_code);
    }
}
