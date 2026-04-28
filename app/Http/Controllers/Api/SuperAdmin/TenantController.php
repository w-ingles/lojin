<?php
namespace App\Http\Controllers\Api\SuperAdmin;
use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\CreateTenantUserRequest;
use App\Http\Requests\SuperAdmin\StoreTenantRequest;
use App\Http\Requests\SuperAdmin\UpdateTenantRequest;
use App\Models\Order;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

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
                ->with('university:id,name,acronym')
                ->latest()->get()
                ->map(fn ($t) => [...$t->toArray(), 'faturamento' => Order::where('tenant_id',$t->id)->where('status','paid')->sum('total')])
        );
    }

    public function store(StoreTenantRequest $request): JsonResponse
    {
        $data   = $request->validated();
        $tenant = Tenant::create([
            'name'          => $data['name'],
            'slug'          => $data['slug'],
            'email'         => $data['email'] ?? null,
            'phone'         => $data['phone'] ?? null,
            'university_id' => $data['university_id'],
            'description'   => $data['description'] ?? null,
            'plan'          => $data['plan'] ?? 'basic',
            'is_active'     => true,
        ]);

        User::create([
            'tenant_id' => $tenant->id,
            'name'      => $data['admin_name'],
            'email'     => $data['admin_email'],
            'cpf'       => preg_replace('/\D/', '', $data['admin_cpf']),
            'password'  => Hash::make($data['admin_password']),
            'role'      => 'admin',
        ]);

        return response()->json($tenant->loadCount('users'), 201);
    }

    public function update(UpdateTenantRequest $request, Tenant $tenant): JsonResponse
    {
        $tenant->update($request->validated());
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

    public function criarUsuario(CreateTenantUserRequest $request, Tenant $tenant): JsonResponse
    {
        $data        = $request->validated();
        $data['cpf'] = preg_replace('/\D/', '', $data['cpf']);
        $user        = User::create([...$data, 'tenant_id' => $tenant->id, 'password' => Hash::make($data['password'])]);
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