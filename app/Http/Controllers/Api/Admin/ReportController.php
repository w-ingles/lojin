<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function vendas(Request $request): JsonResponse
    {
        [$ini, $fim] = $this->periodo($request);

        $base = Order::whereBetween('created_at', [$ini, $fim]);

        $totais = [
            'pedidos'     => (clone $base)->count(),
            'faturamento' => (clone $base)->where('status','paid')->sum('total'),
            'pagos'       => (clone $base)->where('status','paid')->count(),
            'pendentes'   => (clone $base)->where('status','pending')->count(),
        ];
        $totais['ticket_medio'] = $totais['pedidos'] > 0
            ? round($totais['faturamento'] / $totais['pedidos'], 2) : 0;

        $porDia = (clone $base)
            ->selectRaw('DATE(created_at) as data, COUNT(*) as pedidos, SUM(total) as faturamento')
            ->groupBy('data')->orderBy('data')->get()
            ->map(fn ($d) => [
                'data'        => \Carbon\Carbon::parse($d->data)->format('d/m'),
                'pedidos'     => (int) $d->pedidos,
                'faturamento' => (float) $d->faturamento,
            ]);

        $porStatus = (clone $base)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')->pluck('total','status');

        return response()->json(['totais' => $totais, 'por_dia' => $porDia, 'por_status' => $porStatus]);
    }

    public function exportar(Request $request): \Illuminate\Http\Response
    {
        [$ini, $fim] = $this->periodo($request);
        $orders = Order::whereBetween('created_at', [$ini, $fim])->latest()->get();

        $linhas = [implode(';', ['#','Cliente','E-mail','Total','Status','Data'])];
        foreach ($orders as $o) {
            $linhas[] = implode(';', [$o->id, $o->customer_name, $o->customer_email ?? '-',
                number_format($o->total, 2, ',', '.'), $o->status_label,
                $o->created_at->format('d/m/Y H:i')]);
        }

        return response("\xEF\xBB\xBF".implode("\n",$linhas), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=vendas.csv",
        ]);
    }

    private function periodo(Request $request): array
    {
        $ini = $request->filled('inicio') ? \Carbon\Carbon::parse($request->string('inicio'))->startOfDay() : now()->startOfMonth();
        $fim = $request->filled('fim') ? \Carbon\Carbon::parse($request->string('fim'))->endOfDay() : now()->endOfDay();
        return [$ini, $fim];
    }
}
