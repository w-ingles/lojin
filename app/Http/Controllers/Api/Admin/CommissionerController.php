<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Commissioner;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class CommissionerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Commissioner::with('user:id,name,email')
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

    public function buscarUsuario(Request $request): JsonResponse
    {
        $email = $request->validate(['email' => ['required', 'email']])['email'];

        $user = User::where('email', $email)->whereNotIn('role', ['super_admin', 'admin'])->first();

        if (!$user) {
            return response()->json(['message' => 'Usuário não encontrado com este e-mail.'], 404);
        }

        $jaVinculado = Commissioner::where('user_id', $user->id)->exists();
        if ($jaVinculado) {
            return response()->json(['message' => 'Este usuário já é comissário desta atlética.'], 422);
        }

        return response()->json($user->only('id', 'name', 'email'));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'mode'     => ['required', 'in:existing,new'],
            'user_id'  => ['required_if:mode,existing', 'nullable', 'integer'],
            'name'     => ['required_if:mode,new', 'nullable', 'string', 'max:100'],
            'email'    => ['required_if:mode,new', 'nullable', 'email', 'unique:users,email'],
            'password' => ['required_if:mode,new', 'nullable', 'string', 'min:8'],
            'notes'    => ['nullable', 'string', 'max:500'],
            'is_active'=> ['boolean'],
        ], [
            'user_id.required_if'  => 'Selecione um usuário existente.',
            'name.required_if'     => 'O nome é obrigatório.',
            'email.required_if'    => 'O e-mail é obrigatório.',
            'email.unique'         => 'Este e-mail já está em uso.',
            'password.required_if' => 'A senha é obrigatória.',
        ]);

        if ($data['mode'] === 'existing') {
            $user = User::findOrFail($data['user_id']);
            if (Commissioner::where('user_id', $user->id)->exists()) {
                return response()->json(['message' => 'Este usuário já é comissário desta atlética.'], 422);
            }
            $userId = $user->id;
        } else {
            $user = User::create([
                'name'     => $data['name'],
                'email'    => $data['email'],
                'password' => Hash::make($data['password']),
                'role'     => 'user',
            ]);
            $userId = $user->id;
        }

        $commissioner = Commissioner::create([
            'user_id'   => $userId,
            'is_active' => $data['is_active'] ?? true,
            'notes'     => $data['notes'] ?? null,
        ]);

        return response()->json($commissioner->load('user:id,name,email')->loadCount('orders'), 201);
    }

    public function update(Request $request, Commissioner $commissioner): JsonResponse
    {
        $data = $request->validate([
            'is_active' => ['sometimes', 'boolean'],
            'notes'     => ['nullable', 'string', 'max:500'],
        ]);

        $commissioner->update($data);
        return response()->json($commissioner->load('user:id,name,email')->loadCount('orders'));
    }

    public function destroy(Commissioner $commissioner): JsonResponse
    {
        $commissioner->delete();
        return response()->json(null, 204);
    }

    public function vendas(Request $request, Commissioner $commissioner): JsonResponse
    {
        $orders = Order::where('commissioner_id', $commissioner->id)
            ->with('items')
            ->latest()
            ->paginate(10);

        return response()->json($orders);
    }
}