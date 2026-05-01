<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Scopes\TenantScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
            $client  = new PaymentClient();
            $payment = $client->get((int) $paymentId);
        } catch (\Throwable) {
            return response()->json(['status' => 'error']);
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
            $order->update([
                'payment_method' => $payment->payment_type_id ?? 'mercado_pago',
                'payment_id'     => (string) $payment->id,
            ]);
            $order->markAsPaid();
        }

        return response()->json(['status' => 'ok']);
    }
}
