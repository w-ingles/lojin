<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $hoje = today();

        return response()->json([
            'vendas_hoje'      => Order::whereDate('created_at', $hoje)->sum('total'),
            'pedidos_hoje'     => Order::whereDate('created_at', $hoje)->count(),
            'pedidos_pendentes'=> Order::where('status','pending')->count(),
            'eventos_ativos'   => Event::where('status','active')->count(),
            'total_produtos'   => Product::where('active', true)->count(),
            'pedidos_recentes' => Order::with('items')->latest()->limit(10)->get(),
            'eventos_proximos' => Event::with('ticketBatches')
                ->where('status','active')
                ->where('starts_at', '>', now())
                ->orderBy('starts_at')
                ->limit(5)
                ->get()
                ->append(['total_capacity','total_sold']),
        ]);
    }
}
