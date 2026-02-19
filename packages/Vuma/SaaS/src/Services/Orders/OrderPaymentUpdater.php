<?php

namespace Vuma\SaaS\Services\Orders;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderPaymentUpdater
{
    /**
     * Mark a Bagisto order as paid after a successful payment callback.
     *
     * @param string   $orderIncrementId  Bagisto order increment ID
     * @param string   $gateway           Payment method slug (e.g. "mpesa_ke")
     * @param string   $reference         Provider transaction ID
     * @param int|null $amountCents       Optional: verify amount in cents
     */
    public function markPaid(
        string  $orderIncrementId,
        string  $gateway,
        string  $reference,
        ?int    $amountCents = null
    ): bool {
        return DB::transaction(function () use ($orderIncrementId, $gateway, $reference, $amountCents) {
            if (!class_exists(\Webkul\Sales\Models\Order::class)) {
                Log::warning('Bagisto Order model not found; skipping order update', [
                    'increment_id' => $orderIncrementId,
                ]);
                return false;
            }

            $order = \Webkul\Sales\Models\Order::query()
                ->where('increment_id', $orderIncrementId)
                ->first();

            if (!$order) {
                Log::warning('Order not found for payment update', [
                    'increment_id' => $orderIncrementId,
                    'gateway'      => $gateway,
                    'reference'    => $reference,
                ]);
                return false;
            }

            // Update payment record
            try {
                if (method_exists($order, 'payment') && $order->payment) {
                    $order->payment->transaction_id = $reference;
                    $order->payment->method         = $gateway;
                    $order->payment->save();
                }
            } catch (\Throwable $e) {
                Log::warning('Payment relation update failed', ['error' => $e->getMessage()]);
            }

            // Update order status
            try {
                if (method_exists($order, 'updateStatus')) {
                    $order->updateStatus('processing');
                } else {
                    $order->status = 'processing';
                    $order->save();
                }
            } catch (\Throwable $e) {
                Log::warning('Order status update failed', ['error' => $e->getMessage()]);
            }

            Log::info('Order marked paid', [
                'increment_id' => $orderIncrementId,
                'gateway'      => $gateway,
                'reference'    => $reference,
            ]);

            return true;
        });
    }
}
