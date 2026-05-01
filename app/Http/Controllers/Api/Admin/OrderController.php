<?php
namespace App\Http\Controllers\Api\Admin;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateOrderStatusRequest;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $orders = Order::with('items')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('search'), fn ($q) =>
                $q->where('customer_name','like','%'.$request->string('search').'%')
                  ->orWhere('customer_email','like','%'.$request->string('search').'%')
            )
            ->latest()->paginate(20);
        return response()->json($orders);
    }

    public function show(Order $order): JsonResponse
    {
        return response()->json($order->load(['items','items.tickets','user']));
    }

    public function updateStatus(UpdateOrderStatusRequest $request, Order $order): JsonResponse
    {
        $order->update($request->validated());
        return response()->json($order->fresh());
    }
}