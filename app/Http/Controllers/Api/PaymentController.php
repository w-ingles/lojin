<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Ticket;
use App\Models\TicketBatch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\Exceptions\MPApiException;
use MercadoPago\MercadoPagoConfig;

class PaymentController extends Controller
{
    public function processar(Request $request, int $orderId): JsonResponse
    {
        $order = Order::with('items')->findOrFail($orderId);

        if ($order->user_id !== $request->user()->id) {
            abort(403, 'Acesso negado.');
        }

        if ($order->status !== 'pending') {
            return response()->json(['message' => 'Pedido já processado.'], 422);
        }

        MercadoPagoConfig::setAccessToken(config('mercadopago.access_token'));

        $paymentData = [
            'transaction_amount' => (float) $order->total,
            'external_reference' => (string) $order->id,
            'notification_url'   => url('/api/webhooks/mercadopago'),
            'payment_method_id'  => $request->input('payment_method_id'),
            'payer'              => [
                'email'          => $order->customer_email,
                'identification' => $request->input('payer.identification', []),
            ],
        ];

        if ($request->filled('token')) {
            $paymentData['token']        = $request->input('token');
            $paymentData['installments'] = (int) $request->input('installments', 1);
            if ($request->filled('issuer_id')) {
                $paymentData['issuer_id'] = $request->input('issuer_id');
            }
        }

        try {
            $payment = (new PaymentClient())->create($paymentData);
        } catch (MPApiException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Erro ao processar pagamento. Tente novamente.',
            ], 502);
        }

        if ($payment->status === 'approved') {
            $this->gerarIngressos($order, $payment);
            return response()->json(['status' => 'approved']);
        }

        if (in_array($payment->status, ['pending', 'in_process'])) {
            $details = [];
            if ($payment->payment_method_id === 'pix') {
                $txData = $payment->point_of_interaction->transaction_data ?? null;
                $details['pix_code']   = $txData?->qr_code;
                $details['pix_base64'] = $txData?->qr_code_base64;
            } elseif ($payment->payment_type_id === 'ticket') {
                $details['boleto_url'] = $payment->transaction_details?->external_resource_url;
            }
            return response()->json(['status' => 'pending', 'details' => $details]);
        }

        return response()->json([
            'status'  => 'rejected',
            'message' => $this->motivoRejeicao($payment->status_detail ?? ''),
        ], 422);
    }

    private function gerarIngressos(Order $order, object $payment): void
    {
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
    }

    private function motivoRejeicao(string $detail): string
    {
        return match ($detail) {
            'cc_rejected_insufficient_amount'      => 'Saldo insuficiente.',
            'cc_rejected_bad_filled_card_number'   => 'Número do cartão incorreto.',
            'cc_rejected_bad_filled_date'          => 'Data de validade incorreta.',
            'cc_rejected_bad_filled_security_code' => 'Código de segurança incorreto.',
            'cc_rejected_blacklist'                => 'Cartão não aceito.',
            'cc_rejected_call_for_authorize'       => 'Contate seu banco para autorizar.',
            'cc_rejected_card_disabled'            => 'Cartão desativado.',
            'cc_rejected_duplicated_payment'       => 'Pagamento duplicado.',
            'cc_rejected_high_risk'                => 'Pagamento recusado por segurança.',
            default                                => 'Pagamento recusado. Tente outro cartão.',
        };
    }
}
