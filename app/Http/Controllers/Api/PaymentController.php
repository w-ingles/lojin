<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\Exceptions\MPApiException;
use MercadoPago\MercadoPagoConfig;

class PaymentController extends Controller
{
    public function criarPreferencia(Request $request, int $orderId): JsonResponse
    {
        $order = Order::with('items')->findOrFail($orderId);

        if ($order->user_id !== $request->user()->id) {
            abort(403, 'Acesso negado.');
        }

        if ($order->status !== 'pending') {
            return response()->json(['message' => 'Pedido já processado.'], 422);
        }

        $slug    = app('current_tenant')->slug;
        $baseUrl = rtrim(config('app.url'), '/');

        MercadoPagoConfig::setAccessToken(config('mercadopago.access_token'));

        $items = $order->items->map(fn ($item) => [
            'id'          => (string) $item->id,
            'title'       => $item->item_name,
            'quantity'    => (int) $item->quantity,
            'unit_price'  => (float) $item->unit_price,
            'currency_id' => 'BRL',
        ])->toArray();

        $client = new PreferenceClient();

        try {
            $preference = $client->create([
                'items' => $items,
                'payer' => [
                    'name'  => $order->customer_name,
                    'email' => $order->customer_email,
                ],
                'external_reference' => (string) $order->id,
            ]);
        } catch (MPApiException $e) {
            return response()->json([
                'message'       => 'Erro ao criar preferência de pagamento.',
                'mp_status'     => $e->getApiResponse()->getStatusCode(),
                'mp_response'   => $e->getApiResponse()->getContent(),
            ], 502);
        }

        $sandbox = config('mercadopago.sandbox', true);

        return response()->json([
            'preference_id' => $preference->id,
            'checkout_url'  => $sandbox ? $preference->sandbox_init_point : $preference->init_point,
        ]);
    }
}
