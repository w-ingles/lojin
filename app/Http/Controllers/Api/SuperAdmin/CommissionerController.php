<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Commissioner;
use App\Scopes\TenantScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommissionerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Commissioner::withoutGlobalScope(TenantScope::class)
            ->with(['user:id,name,email', 'tenant:id,name,slug'])
            ->withCount('orders')
            ->when($request->filled('search'), fn ($q) =>
                $q->whereHas('user', fn ($u) =>
                    $u->where('name', 'like', '%' . $request->search . '%')
                      ->orWhere('email', 'like', '%' . $request->search . '%')
                )
            )
            ->when($request->filled('tenant_id'), fn ($q) =>
                $q->where('tenant_id', $request->integer('tenant_id'))
            )
            ->when($request->filled('status'), fn ($q) =>
                $q->where('is_active', $request->boolean('status'))
            )
            ->latest();

        return response()->json($query->paginate(20));
    }
}