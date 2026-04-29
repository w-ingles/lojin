<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Bloqueia o checkout se o usuário não tiver nome e telefone preenchidos.
 * Retorna code = PROFILE_INCOMPLETE para o frontend redirecionar para o perfil.
 */
class RequireCompleteProfile
{
    public function handle(Request $request, Closure $next): mixed
    {
        $user = auth('sanctum')->user();

        if (!$user) {
            return response()->json(['message' => 'Autenticação necessária.'], 401);
        }

        if (!$user->isProfileComplete()) {
            return response()->json([
                'message'         => 'Seu cadastro está incompleto. Atualize seus dados antes de realizar compras.',
                'code'            => 'PROFILE_INCOMPLETE',
                'campos_faltando' => $user->profileMissingFields(),
            ], 422);
        }

        return $next($request);
    }
}