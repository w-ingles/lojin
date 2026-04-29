<?php
namespace App\Http\Controllers\Api\Store;
use App\Http\Controllers\Controller;
use App\Http\Requests\Store\StoreOrderRequest;
use App\Models\Order;
use App\Models\Product;
use App\Models\Ticket;
use App\Models\TicketBatch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function store(StoreOrderRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = $request->user();

        $order = DB::transaction(function () use ($data, $user) {
            $order = Order::create([
                'user_id'        => $user->id,
                'customer_name'  => $user->name,
                'customer_email' => $user->email,
                'customer_phone' => $user->phone,
                'customer_cpf'   => $user->cpf
                    ? preg_replace('/\D/', '', $user->cpf)
                    : null,
                'notes'  => $data['notes'] ?? null,
                'status' => 'pending',
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
                            'user_id'         => $user->id,
                            'status'          => 'reserved',
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

    public function show(int $id): JsonResponse
    {
        return response()->json(Order::with(['items', 'items.tickets'])->findOrFail($id));
    }

    public function myOrders(Request $request): JsonResponse
    {
        return response()->json(
            Order::with('items')->where('user_id', $request->user()->id)->latest()->paginate(20)
        );
    }

    public function commissionerStatus(Request $request): JsonResponse
    {
        $commissioner = \App\Models\Commissioner::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->where('tenant_id', app('current_tenant')->id)
            ->where('user_id', $request->user()->id)
            ->where('is_active', true)
            ->first();
        return response()->json([
            'is_commissioner' => (bool) $commissioner,
            'commissioner_id' => $commissioner?->id,
        ]);
    }

    public function meusIngressos(Request $request): JsonResponse
    {
        $tickets = \App\Models\Ticket::with([
                'batch:id,event_id,name,price',
                'batch.event:id,tenant_id,name,starts_at,location,status,banner',
            ])
            ->where('user_id', $request->user()->id)->latest()->get()
            ->map(fn ($t) => [
                'id'         => $t->id,
                'code'       => $t->code,
                'status'     => $t->status,
                'used_at'    => $t->used_at,
                'created_at' => $t->created_at,
                'lote'       => ['nome' => $t->batch?->name, 'preco' => $t->batch?->price],
                'evento'     => [
                    'nome'       => $t->batch?->event?->name,
                    'data'       => $t->batch?->event?->starts_at,
                    'local'      => $t->batch?->event?->location,
                    'status'     => $t->batch?->event?->status,
                    'banner_url' => $t->batch?->event?->banner
                        ? asset('storage/' . $t->batch->event->banner)
                        : null,
                ],
            ]);
        return response()->json($tickets);
    }
}