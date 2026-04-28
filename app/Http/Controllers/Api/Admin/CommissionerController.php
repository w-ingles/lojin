<?php
namespace App\Http\Controllers\Api\Admin;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCommissionerRequest;
use App\Http\Requests\Admin\UpdateCommissionerRequest;
use App\Models\Commissioner;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommissionerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Commissioner::with('user:id,name,email,cpf')
            ->withCount('orders')
            ->when($request->filled('search'), fn ($q) =>
                $q->whereHas('user', fn ($u) =>
                    $u->where('name', 'like', '%' . $request->search . '%')
                      ->orWhere('email', 'like', '%' . $request->search . '%')
                )
            )
            ->when($request->filled('status'), fn ($q) =>
                $q->where('is_active', $request->boolean('status'))
            )
            ->latest();

        return response()->json($query->paginate(15));
    }

    public function store(StoreCommissionerRequest $request): JsonResponse
    {
        $cpf  = preg_replace('/\D/', '', $request->cpf);
        $user = User::where('cpf', $cpf)->first();

        if (!$user) {
            return response()->json([
                'message' => 'Este CPF não está cadastrado. Solicite que a pessoa realize o cadastro na plataforma para poder ser vinculada como comissário.',
                'code'    => 'CPF_NOT_FOUND',
            ], 404);
        }

        if (Commissioner::where('user_id', $user->id)->exists()) {
            return response()->json(['message' => "O usuário \"{$user->name}\" já é comissário desta atlética."], 422);
        }

        $commissioner = Commissioner::create(['user_id' => $user->id, 'is_active' => true]);
        return response()->json($commissioner->load('user:id,name,email,cpf')->loadCount('orders'), 201);
    }

    public function update(UpdateCommissionerRequest $request, Commissioner $commissioner): JsonResponse
    {
        $commissioner->update($request->validated());
        return response()->json($commissioner->load('user:id,name,email,cpf')->loadCount('orders'));
    }

    public function destroy(Commissioner $commissioner): JsonResponse
    {
        $commissioner->delete();
        return response()->json(null, 204);
    }

    public function vendas(Request $request, Commissioner $commissioner): JsonResponse
    {
        $orders = Order::where('commissioner_id', $commissioner->id)
            ->with('items')->latest()->paginate(10);
        return response()->json($orders);
    }
}