<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Ticket;
use App\Models\TicketBatch;
use App\Scopes\TenantScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\MercadoPagoConfig;

class WebhookController extends Controller
{
    public function mercadoPago(Request $request): JsonResponse
    {
        $type = $request->input('type') ?? $request->input('topic');

        if ($type !== 'payment') {
            return response()->json(['status' => 'ignored']);
        }

        $paymentId = $request->input('data.id') ?? $request->input('id');

        if (!$paymentId) {
            return response()->json(['status' => 'ignored']);
        }

        MercadoPagoConfig::setAccessToken(config('mercadopago.access_token'));

        try {
            $payment = (new PaymentClient())->get((int) $paymentId);
        } catch (\Throwable) {
            return response()->json(['status' => 'error'], 500);
        }

        if (!$payment || !$payment->external_reference) {
            return response()->json(['status' => 'ignored']);
        }

        $order = Order::withoutGlobalScope(TenantScope::class)
            ->with('items')
            ->find((int) $payment->external_reference);

        if (!$order || $order->status !== 'pending') {
            return response()->json(['status' => 'ignored']);
        }

        if ($payment->status === 'approved') {
            DB::transaction(function () use ($order, $payment) {
                $order->update([
                    'payment_method' => $payment->payment_type_id ?? 'mercado_pago',
                    'payment_id'     => (string) $payment->id,
                ]);

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

            return response()->json(['status' => 'ok']);
        }

        if (in_array($payment->status, ['rejected', 'cancelled'])) {
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
        }

        return response()->json(['status' => 'ok']);
    }
}
