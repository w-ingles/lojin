<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Mail\VerificationCodeMail;
use App\Models\EmailVerification;
use App\Models\User;
use App\Rules\CpfRule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    // ── Passo 1: valida dados e envia código ──────────────────────────────────
    public function preRegister(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'       => ['required', 'string', 'max:100'],
            'email'      => ['required', 'email', 'unique:users,email'],
            'cpf'        => ['required', 'string', new CpfRule(), Rule::unique('users', 'cpf')],
            'phone'      => ['required', 'string', 'max:20'],
            'birth_date' => ['required', 'date', 'before:today'],
            'password'   => ['required', Password::min(8)],
        ], [
            'email.unique'      => 'Este e-mail já está cadastrado.',
            'cpf.unique'        => 'Este CPF já está cadastrado na plataforma.',
            'cpf.required'      => 'O CPF é obrigatório.',
            'phone.required'    => 'O telefone é obrigatório.',
            'birth_date.required' => 'A data de nascimento é obrigatória.',
            'birth_date.before' => 'A data de nascimento deve ser no passado.',
        ]);

        $data['cpf'] = preg_replace('/\D/', '', $data['cpf']);

        // Remove verificações anteriores para o mesmo e-mail
        EmailVerification::where('email', $data['email'])->delete();

        $code  = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $token = Str::random(48);

        EmailVerification::create([
            'email'      => $data['email'],
            'token'      => $token,
            'code'       => Hash::make($code),
            'data'       => json_encode([
                'name'       => $data['name'],
                'email'      => $data['email'],
                'cpf'        => $data['cpf'],
                'phone'      => $data['phone'],
                'birth_date' => $data['birth_date'],
                'password'   => Hash::make($data['password']),
            ]),
            'expires_at' => now()->addMinutes(10),
        ]);

        Mail::to($data['email'])->send(new VerificationCodeMail($code));

        return response()->json([
            'message'       => 'Código enviado para ' . $data['email'],
            'pending_token' => $token,
        ]);
    }

    // ── Passo 2: confirma código e cria usuário ───────────────────────────────
    public function confirmRegister(Request $request): JsonResponse
    {
        $request->validate([
            'pending_token' => ['required', 'string'],
            'code'          => ['required', 'string', 'size:6'],
        ], [
            'code.size' => 'O código deve ter 6 dígitos.',
        ]);

        $verification = EmailVerification::where('token', $request->pending_token)->first();

        if (!$verification) {
            return response()->json(['message' => 'Solicitação inválida ou expirada. Reinicie o cadastro.'], 422);
        }

        if ($verification->isExpired()) {
            $verification->delete();
            return response()->json(['message' => 'O código expirou. Reinicie o cadastro.'], 422);
        }

        if ($verification->maxAttemptsReached()) {
            $verification->delete();
            return response()->json(['message' => 'Muitas tentativas incorretas. Reinicie o cadastro.'], 422);
        }

        if (!Hash::check($request->code, $verification->code)) {
            $verification->increment('attempts');
            $remaining = 5 - $verification->fresh()->attempts;
            return response()->json([
                'message' => "Código incorreto. {$remaining} tentativa(s) restante(s).",
                'errors'  => ['code' => ["Código incorreto. {$remaining} tentativa(s) restante(s)."]],
            ], 422);
        }

        $payload = json_decode($verification->data, true);

        // Checa unicidade novamente (pode ter mudado enquanto aguardava)
        if (User::where('email', $payload['email'])->exists()) {
            $verification->delete();
            return response()->json(['message' => 'Este e-mail já foi cadastrado por outro usuário.'], 422);
        }
        if (User::where('cpf', $payload['cpf'])->exists()) {
            $verification->delete();
            return response()->json(['message' => 'Este CPF já foi cadastrado por outro usuário.'], 422);
        }

        $user = User::create([
            'name'       => $payload['name'],
            'email'      => $payload['email'],
            'cpf'        => $payload['cpf'],
            'phone'      => $payload['phone'],
            'birth_date' => $payload['birth_date'],
            'password'   => $payload['password'],
            'role'       => 'user',
        ]);

        $verification->delete();

        $token = $user->createToken('app')->plainTextToken;
        return response()->json(['user' => $user, 'token' => $token], 201);
    }

    // ── Reenviar código ───────────────────────────────────────────────────────
    public function resendCode(Request $request): JsonResponse
    {
        $request->validate(['pending_token' => ['required', 'string']]);

        $verification = EmailVerification::where('token', $request->pending_token)->first();

        if (!$verification || $verification->isExpired()) {
            return response()->json(['message' => 'Solicitação inválida ou expirada. Reinicie o cadastro.'], 422);
        }

        $code     = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $newToken = Str::random(48);

        $verification->update([
            'token'      => $newToken,
            'code'       => Hash::make($code),
            'attempts'   => 0,
            'expires_at' => now()->addMinutes(10),
        ]);

        Mail::to($verification->email)->send(new VerificationCodeMail($code));

        return response()->json([
            'message'       => 'Novo código enviado.',
            'pending_token' => $newToken,
        ]);
    }

    // ── Login por e-mail OU CPF ───────────────────────────────────────────────
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'identifier' => ['required', 'string'],
            'password'   => ['required'],
        ], [
            'identifier.required' => 'Informe o e-mail ou CPF.',
        ]);

        $raw  = trim($request->identifier);
        $isCpf = preg_match('/^\d{11}$/', preg_replace('/\D/', '', $raw)) && !str_contains($raw, '@');

        if ($isCpf) {
            $user = User::where('cpf', preg_replace('/\D/', '', $raw))->first();
        } else {
            $user = User::where('email', $raw)->first();
        }

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Credenciais inválidas.'], 401);
        }

        $token = $user->createToken('app')->plainTextToken;
        return response()->json(['user' => $user->load('tenant'), 'token' => $token]);
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