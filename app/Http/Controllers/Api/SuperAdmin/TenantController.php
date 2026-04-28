<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class TenantController extends Controller
{
    public function overview(): JsonResponse
    {
        return response()->json([
            'total_atleticas'   => Tenant::count(),
            'atleticas_ativas'  => Tenant::where('is_active', true)->count(),
            'total_pedidos'     => Order::count(),
            'faturamento_total' => Order::where('status','paid')->sum('total'),
            'atleticas_recentes'=> Tenant::withCount(['users','orders','events'])->latest()->limit(5)->get(),
        ]);
    }

    public function index(): JsonResponse
    {
        return response()->json(
            Tenant::withCount(['users','orders','events'])
                ->latest()->get()
                ->map(fn ($t) => [...$t->toArray(), 'faturamento' => Order::where('tenant_id',$t->id)->where('status','paid')->sum('total')])
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'           => ['required','string','max:100'],
            'slug'           => ['required','string','max:50','unique:tenants,slug','regex:/^[a-z0-9-]+$/'],
            'email'          => ['nullable','email'],
            'phone'          => ['nullable','string','max:20'],
            'university'     => ['nullable','string','max:100'],
            'description'    => ['nullable','string'],
            'plan'           => ['nullable','in:basic,premium'],
            'admin_name'     => ['required','string','max:100'],
            'admin_email'    => ['required','email','unique:users,email'],
            'admin_password' => ['required','string','min:8'],
        ]);

        $tenant = Tenant::create([
            'name'       => $data['name'],
            'slug'       => $data['slug'],
            'email'      => $data['email'] ?? null,
            'phone'      => $data['phone'] ?? null,
            'university' => $data['university'] ?? null,
            'description'=> $data['description'] ?? null,
            'plan'       => $data['plan'] ?? 'basic',
            'is_active'  => true,
        ]);

        User::create([
            'tenant_id' => $tenant->id,
            'name'      => $data['admin_name'],
            'email'     => $data['admin_email'],
            'password'  => Hash::make($data['admin_password']),
            'role'      => 'admin',
        ]);

        return response()->json($tenant->loadCount('users'), 201);
    }

    public function update(Request $request, Tenant $tenant): JsonResponse
    {
        $data = $request->validate([
            'name'        => ['sometimes','required','string','max:100'],
            'slug'        => ['sometimes','required','string','max:50','regex:/^[a-z0-9-]+$/', Rule::unique('tenants')->ignore($tenant->id)],
            'email'       => ['nullable','email'],
            'phone'       => ['nullable','string','max:20'],
            'university'  => ['nullable','string','max:100'],
            'description' => ['nullable','string'],
            'plan'        => ['nullable','in:basic,premium'],
            'is_active'   => ['sometimes','boolean'],
        ]);

        $tenant->update($data);
        return response()->json($tenant);
    }

    public function toggleAtivo(Tenant $tenant): JsonResponse
    {
        $tenant->update(['is_active' => !$tenant->is_active]);
        return response()->json($tenant);
    }

    public function impersonate(Tenant $tenant): JsonResponse
    {
        abort_if(!$tenant->is_active, 403, 'Atlética inativa.');
        $admin = User::where('tenant_id', $tenant->id)->where('role','admin')->firstOrFail();
        return response()->json(['token' => $admin->createToken('impersonate')->plainTextToken, 'user' => $admin->load('tenant')]);
    }

    public function usuarios(Tenant $tenant): JsonResponse
    {
        return response()->json(User::where('tenant_id',$tenant->id)->latest()->get());
    }

    public function criarUsuario(Request $request, Tenant $tenant): JsonResponse
    {
        $data = $request->validate([
            'name'     => ['required','string','max:100'],
            'email'    => ['required','email','unique:users,email'],
            'password' => ['required','string','min:8'],
            'role'     => ['required','in:admin,user'],
        ]);
        $user = User::create([...$data, 'tenant_id' => $tenant->id, 'password' => Hash::make($data['password'])]);
        return response()->json($user, 201);
    }

    public function excluirUsuario(Tenant $tenant, User $user): JsonResponse
    {
        abort_if($user->tenant_id !== $tenant->id, 403);
        $user->tokens()->delete();
        $user->delete();
        return response()->json(null, 204);
    }
}
