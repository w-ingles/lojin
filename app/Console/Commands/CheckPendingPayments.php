<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Ticket;
use App\Models\TicketBatch;
use App\Scopes\TenantScope;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\MercadoPagoConfig;

class CheckPendingPayments extends Command
{
    protected $signature   = 'payments:check-pending';
    protected $description = 'Verifica pagamentos pendentes no Mercado Pago e atualiza os pedidos';

    public function handle(): void
    {
        $orders = Order::withoutGlobalScope(TenantScope::class)
            ->with('items')
            ->where('status', 'pending')
            ->whereNotNull('payment_id')
            ->where('created_at', '>=', now()->subHours(24))
            ->get();

        $this->info("Pedidos pendentes com payment_id: {$orders->count()}");

        if ($orders->isEmpty()) return;

        MercadoPagoConfig::setAccessToken(config('mercadopago.access_token'));
        $client = new PaymentClient();

        foreach ($orders as $order) {
            try {
                $payment = $client->get((int) $order->payment_id);

                if (!$payment) continue;

                $this->line("  Pedido #{$order->id} | payment_id: {$order->payment_id} | status MP: {$payment->status}");

                if ($payment->status === 'approved') {
                    DB::transaction(function () use ($order, $payment) {
                        foreach ($order->items as $item) {
                            if ($item->itemable_type === TicketBatch::class) {
                                for ($i = 0; $i < $item->quantity; $i++) {
                                    Ticket::create([
                                        'ticket_batch_id' => $item->itemable_id,
                                        'order_item_id'   => $item->id,
                                        'user_id'         => $order->user_id,
                                        'status'          => 'paid',
                                    ]);
                                }
                            }
                        }
                        $order->markAsPaid();
                    });

                    $this->info("Pedido #{$order->id} confirmado e ingressos gerados.");
                    Log::info("CheckPendingPayments: pedido #{$order->id} pago.");

                } elseif (in_array($payment->status, ['rejected', 'cancelled'])) {
                    DB::transaction(function () use ($order) {
                        foreach ($order->items as $item) {
                            if ($item->itemable_type === TicketBatch::class) {
                                TicketBatch::withoutGlobalScopes()
                                    ->where('id', $item->itemable_id)
                                    ->decrement('sold', $item->quantity);
                            }
                        }
                        $order->update(['status' => 'cancelled']);
                    });

                    $this->info("Pedido #{$order->id} cancelado.");
                }
            } catch (\Throwable $e) {
                Log::error("CheckPendingPayments: erro no pedido #{$order->id}", ['error' => $e->getMessage()]);
            }
        }
    }
}
