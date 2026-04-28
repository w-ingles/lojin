<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\CpfRule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'     => ['required','string','max:100'],
            'email'    => ['required','email','unique:users,email'],
            'cpf'      => ['required','string', new CpfRule(), Rule::unique('users','cpf')],
            'password' => ['required', Password::min(8)],
        ], [
            'cpf.required' => 'O CPF é obrigatório.',
            'cpf.unique'   => 'Este CPF já está cadastrado na plataforma.',
        ]);

        $data['cpf'] = preg_replace('/\D/', '', $data['cpf']);

        $user  = User::create([...$data, 'role' => 'user']);
        $token = $user->createToken('app')->plainTextToken;

        return response()->json(['user' => $user, 'token' => $token], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => ['required','email'],
            'password' => ['required'],
        ]);

        if (!Auth::attempt($request->only('email','password'))) {
            return response()->json(['message' => 'Credenciais inválidas.'], 401);
        }

        $user  = Auth::user()->load('tenant');
        $token = $user->createToken('app')->plainTextToken;

        return response()->json(['user' => $user, 'token' => $token]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logout realizado.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user()->load('tenant'));
    }
}
