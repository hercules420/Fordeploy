<?php

namespace App\Console\Commands;

use App\Models\Notification;
use App\Models\Order;
use App\Services\PayMongoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReconcileMarketplacePayments extends Command
{
    protected $signature = 'paymongo:reconcile-marketplace-payments {--limit=100 : Max orders to scan} {--dry-run : Report mismatches without updating orders}';

    protected $description = 'Reconcile marketplace order payment statuses against PayMongo records';

    public function handle(PayMongoService $paymongo): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $dryRun = (bool) $this->option('dry-run');

        $orders = Order::query()
            ->whereNotNull('paymongo_payment_id')
            ->where('payment_status', '!=', 'paid')
            ->whereNotIn('status', ['cancelled', 'refunded'])
            ->latest('id')
            ->limit($limit)
            ->get(['id', 'order_number', 'consumer_id', 'farm_owner_id', 'status', 'payment_status', 'payment_method', 'paymongo_payment_id']);

        $scanned = 0;
        $corrected = 0;
        $alreadyConsistent = 0;
        $apiFailures = 0;

        foreach ($orders as $order) {
            $scanned++;
            $gatewayId = (string) $order->paymongo_payment_id;

            $paid = $this->isGatewayMarkedPaid($paymongo, $gatewayId);
            if ($paid === null) {
                $apiFailures++;
                $this->warn("[API_FAIL] {$order->order_number} ({$gatewayId})");
                continue;
            }

            if (!$paid) {
                $alreadyConsistent++;
                continue;
            }

            if ($dryRun) {
                $corrected++;
                $this->line("[DRY_RUN] Would mark {$order->order_number} as paid");
                continue;
            }

            $this->markOrderPaid($order->id);
            $corrected++;
            $this->info("[FIXED] Marked {$order->order_number} as paid");
        }

        $this->newLine();
        $this->info('Reconciliation summary:');
        $this->line("- scanned: {$scanned}");
        $this->line("- corrected: {$corrected}");
        $this->line("- already_consistent: {$alreadyConsistent}");
        $this->line("- api_failures: {$apiFailures}");
        $this->line("- mode: " . ($dryRun ? 'dry-run' : 'apply'));

        return self::SUCCESS;
    }

    private function isGatewayMarkedPaid(PayMongoService $paymongo, string $gatewayId): ?bool
    {
        if ($gatewayId === '') {
            return null;
        }

        if (str_starts_with($gatewayId, 'cs_')) {
            $session = $paymongo->retrieveCheckoutSession($gatewayId);
            if (!$session) {
                return null;
            }

            $attributes = $session['attributes'] ?? [];
            $intentStatus = strtolower((string) data_get($attributes, 'payment_intent.attributes.status', ''));
            if (in_array($intentStatus, ['succeeded', 'paid'], true)) {
                return true;
            }

            $payments = $attributes['payments'] ?? [];
            return !empty($payments);
        }

        if (str_starts_with($gatewayId, 'link_')) {
            $link = $paymongo->retrievePaymentLink($gatewayId);
            if (!$link) {
                return null;
            }

            $attributes = $link['attributes'] ?? [];
            $status = strtolower((string) ($attributes['status'] ?? ''));
            if (in_array($status, ['paid', 'succeeded'], true)) {
                return true;
            }

            $amountPaid = (int) ($attributes['amount_paid'] ?? 0);
            if ($amountPaid > 0) {
                return true;
            }

            $payments = $attributes['payments'] ?? [];
            return !empty($payments);
        }

        $payment = $paymongo->retrievePayment($gatewayId);
        if (!$payment) {
            return null;
        }

        $status = strtolower((string) data_get($payment, 'attributes.status', ''));
        return in_array($status, ['paid', 'succeeded'], true);
    }

    private function markOrderPaid(int $orderId): void
    {
        DB::transaction(function () use ($orderId): void {
            /** @var Order|null $order */
            $order = Order::query()
                ->where('id', $orderId)
                ->lockForUpdate()
                ->with(['farmOwner:id,user_id,farm_name'])
                ->first();

            if (!$order || $order->payment_status === 'paid') {
                return;
            }

            $order->update([
                'payment_status' => 'paid',
            ]);

            if ($order->consumer_id) {
                Notification::create([
                    'user_id' => $order->consumer_id,
                    'title' => 'Payment Confirmed',
                    'message' => "Payment for order {$order->order_number} was confirmed via PayMongo.",
                    'type' => 'system',
                    'channel' => 'in_app',
                    'data' => [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'source' => 'reconciliation',
                    ],
                    'status' => 'sent',
                    'sent_at' => now(),
                ]);
            }

            if ($order->farmOwner?->user_id) {
                Notification::create([
                    'user_id' => $order->farmOwner->user_id,
                    'title' => 'Customer Payment Received',
                    'message' => "Order {$order->order_number} has been paid online.",
                    'type' => 'alert',
                    'channel' => 'in_app',
                    'data' => [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'source' => 'reconciliation',
                    ],
                    'status' => 'sent',
                    'sent_at' => now(),
                ]);
            }
        });
    }
}
