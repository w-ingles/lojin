<?php
namespace App\Http\Controllers\Api\Store;
use App\Http\Controllers\Controller;
use App\Http\Requests\Store\StoreOrderRequest;
use App\Models\Order;
use App\Models\Product;
use App\Models\OrderItem;
use App\Models\TicketBatch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function ticketLimits(Request $request): JsonResponse
    {
        $batchIds = array_filter(array_map('intval', (array) $request->query('batch_ids', [])));
        if (empty($batchIds)) return response()->json([]);

        $batches = TicketBatch::with('event:id,name')->whereIn('id', $batchIds)->get();
        $user    = $request->user();

        $eventCounts = [];
        $result      = [];

        foreach ($batches as $batch) {
            $eventId = $batch->event_id;
            if (!isset($eventCounts[$eventId])) {
                $eventCounts[$eventId] = (int) OrderItem::where('itemable_type', TicketBatch::class)
                    ->whereHas('itemable', fn($q) => $q->where('event_id', $eventId))
                    ->whereHas('order', fn($q) => $q->where('user_id', $user->id)->whereIn('status', ['pending', 'paid']))
                    ->sum('quantity');
            }
            $existing = $eventCounts[$eventId];
            $result[$batch->id] = [
                'event_name' => $batch->event->name,
                'existing'   => $existing,
                'limit'      => 5,
                'remaining'  => max(0, 5 - $existing),
            ];
        }

        return response()->json($result);
    }

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

            $subtotal           = 0;
            $purchasingPerEvent = [];

            foreach ($data['items'] as $item) {
                if ($item['type'] === 'ticket_batch') {
                    $batch = TicketBatch::findOrFail($item['id']);
                    abort_if(!$batch->isAvailable(), 422, "Lote \"{$batch->name}\" indisponível.");
                    abort_if($batch->available < $item['qty'], 422, "Estoque insuficiente para \"{$batch->name}\".");

                    $purchasingPerEvent[$batch->event_id] = ($purchasingPerEvent[$batch->event_id] ?? 0) + $item['qty'];
                    $existing = (int) OrderItem::where('itemable_type', TicketBatch::class)
                        ->whereHas('itemable', fn($q) => $q->where('event_id', $batch->event_id))
                        ->whereHas('order', fn($q) => $q->where('user_id', $user->id)->whereIn('status', ['pending', 'paid']))
                        ->sum('quantity');
                    abort_if(
                        $existing + $purchasingPerEvent[$batch->event_id] > 5,
                        422,
                        "Limite de 5 ingressos por CPF atingido para \"{$batch->event->name}\"."
                    );
                    $lineTotal = $batch->price * $item['qty'];
                    $order->items()->create([
                        'itemable_type' => TicketBatch::class,
                        'itemable_id'   => $batch->id,
                        'item_name'     => $batch->event->name . ' — ' . $batch->name,
                        'quantity'      => $item['qty'],
                        'unit_price'    => $batch->price,
                        'total_price'   => $lineTotal,
                    ]);
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

    public function todosIngressos(Request $request): JsonResponse
    {
        $tickets = \App\Models\Ticket::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->with([
                'batch:id,event_id,name,price',
                'batch.event:id,tenant_id,name,starts_at,location,status,banner',
                'batch.event.tenant:id,name,slug',
            ])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get()
            ->map(fn ($t) => [
                'id'         => $t->id,
                'code'       => $t->code,
                'status'     => $t->status,
                'used_at'    => $t->used_at,
                'created_at' => $t->created_at,
                'atletica'   => [
                    'nome' => $t->batch?->event?->tenant?->name,
                    'slug' => $t->batch?->event?->tenant?->slug,
                ],
                'lote'  => ['nome' => $t->batch?->name, 'preco' => $t->batch?->price],
                'evento' => [
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