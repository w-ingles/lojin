<?php
namespace App\Http\Controllers\Api\Auth;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ConfirmRegisterRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\PreRegisterRequest;
use App\Http\Requests\Auth\ResendCodeRequest;
use App\Mail\VerificationCodeMail;
use App\Models\EmailVerification;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function preRegister(PreRegisterRequest $request): JsonResponse
    {
        $data        = $request->validated();
        $data['cpf'] = preg_replace('/\D/', '', $data['cpf']);

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

    public function confirmRegister(ConfirmRegisterRequest $request): JsonResponse
    {
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

        if (User::where('email', $payload['email'])->exists()) {
            $verification->delete();
            return response()->json(['message' => 'Este e-mail já foi cadastrado por outro usuário.'], 422);
        }
        if (User::where('cpf', $payload['cpf'])->exists()) {
            $verification->delete();
            return response()->json(['message' => 'Este CPF já foi cadastrado por outro usuário.'], 422);
        }

        $user = User::create([...$payload, 'role' => 'user']);
        $verification->delete();

        return response()->json(['user' => $user, 'token' => $user->createToken('app')->plainTextToken], 201);
    }

    public function resendCode(ResendCodeRequest $request): JsonResponse
    {
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

        return response()->json(['message' => 'Novo código enviado.', 'pending_token' => $newToken]);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $raw   = trim($request->identifier);
        $isCpf = preg_match('/^\d{11}$/', preg_replace('/\D/', '', $raw)) && !str_contains($raw, '@');

        $user = $isCpf
            ? User::where('cpf', preg_replace('/\D/', '', $raw))->first()
            : User::where('email', $raw)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Credenciais inválidas.'], 401);
        }

        return response()->json(['user' => $user->load('tenant'), 'token' => $user->createToken('app')->plainTextToken]);
    }

    public function logout(\Illuminate\Http\Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logout realizado.']);
    }

    public function me(\Illuminate\Http\Request $request): JsonResponse
    {
        return response()->json($request->user()->load('tenant'));
    }
}