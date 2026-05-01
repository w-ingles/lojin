<?php
namespace App\Http\Controllers\Api\Store;
use App\Http\Controllers\Controller;
use App\Http\Requests\Store\StoreCommissionerOrderRequest;
use App\Models\Commissioner;
use App\Models\Order;
use App\Models\Product;
use App\Models\Ticket;
use App\Models\TicketBatch;
use App\Models\User;
use App\Scopes\TenantScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CommissionerSalesController extends Controller
{
    private function resolveCommissioner(int $userId): ?Commissioner
    {
        return Commissioner::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', app('current_tenant')->id)
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->first();
    }

    /** Busca cliente pelo CPF — disponível apenas para comissários ativos */
    public function lookup(Request $request): JsonResponse
    {
        $request->validate([
            'cpf' => ['required', 'string'],
        ], [
            'cpf.required' => 'Informe o CPF do cliente.',
        ]);

        $commissioner = $this->resolveCommissioner($request->user()->id);

        if (!$commissioner) {
            return response()->json([
                'message' => 'Você não é um comissário ativo desta atlética.',
            ], 403);
        }

        $cpf = preg_replace('/\D/', '', $request->cpf);

        $customer = User::where('cpf', $cpf)->first();

        if (!$customer) {
            return response()->json([
                'found'   => false,
                'message' => 'Este CPF não está cadastrado. Solicite que a pessoa realize o cadastro na plataforma para poder ser atendida.',
            ], 404);
        }

        if ($customer->id === $request->user()->id) {
            return response()->json([
                'found'   => false,
                'message' => 'Você não pode realizar uma venda para si mesmo.',
            ], 422);
        }

        return response()->json([
            'found'    => true,
            'customer' => [
                'id'    => $customer->id,
                'name'  => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
            ],
        ]);
    }

    /** Cria pedido em nome do cliente identificado pelo CPF */
    public function store(StoreCommissionerOrderRequest $request): JsonResponse
    {
        $commissioner = $this->resolveCommissioner($request->user()->id);

        if (!$commissioner) {
            return response()->json([
                'message' => 'Você não é um comissário ativo desta atlética.',
            ], 403);
        }

        $data     = $request->validated();
        $customer = User::findOrFail($data['customer_user_id']);

        $order = DB::transaction(function () use ($data, $customer, $commissioner) {
            $order = Order::create([
                'user_id'         => $customer->id,
                'commissioner_id' => $commissioner->id,
                'customer_name'   => $customer->name,
                'customer_email'  => $customer->email,
                'customer_phone'  => $customer->phone,
                'customer_cpf'    => $customer->cpf
                    ? preg_replace('/\D/', '', $customer->cpf)
                    : null,
                'notes'          => $data['notes'] ?? null,
                'status'         => 'paid',
                'payment_method' => 'commissioner',
                'paid_at'        => now(),
            ]);

            $subtotal = 0;

            foreach ($data['items'] as $item) {
                if ($item['type'] === 'ticket_batch') {
                    $batch = TicketBatch::findOrFail($item['id']);
                    abort_if(!$batch->isAvailable(), 422, "Lote \"{$batch->name}\" indisponível.");
                    abort_if($batch->available < $item['qty'], 422, "Estoque insuficiente para \"{$batch->name}\".");

                    $lineTotal = $batch->price * $item['qty'];
                    $orderItem = $order->items()->create([
                        'itemable_type' => TicketBatch::class,
                        'itemable_id'   => $batch->id,
                        'item_name'     => $batch->event->name . ' — ' . $batch->name,
                        'quantity'      => $item['qty'],
                        'unit_price'    => $batch->price,
                        'total_price'   => $lineTotal,
                    ]);

                    for ($i = 0; $i < $item['qty']; $i++) {
                        Ticket::create([
                            'ticket_batch_id' => $batch->id,
                            'order_item_id'   => $orderItem->id,
                            'user_id'         => $customer->id,
                            'status'          => 'paid',
                        ]);
                    }

                    $batch->increment('sold', $item['qty']);
                    $subtotal += $lineTotal;
                } else {
                    $product = Product::findOrFail($item['id']);
                    abort_if(!$product->active, 422, "Produto \"{$product->name}\" indisponível.");
                    abort_if($product->stock < $item['qty'], 422, "Estoque insuficiente para \"{$product->name}\".");

                    $lineTotal = $product->price * $item['qty'];
                    $order->items()->create([
                        'itemable_type' => Product::class,
                        'itemable_id'   => $product->id,
                        'item_name'     => $product->name,
                        'quantity'      => $item['qty'],
                        'unit_price'    => $product->price,
                        'total_price'   => $lineTotal,
                    ]);

                    $product->decrement('stock', $item['qty']);
                    $subtotal += $lineTotal;
                }
            }

            $order->update(['subtotal' => $subtotal, 'total' => $subtotal]);
            return $order;
        });

        return response()->json($order->load('items'), 201);
    }
}