<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IdentifyTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth('sanctum')->user();

        if ($user) {
            // Super admin: sem escopo de tenant — vê tudo
            if ($user->isSuperAdmin()) {
                return $next($request);
            }

            // Admin da atlética: tenant vem do próprio usuário
            if ($user->isAdmin() && $user->tenant_id) {
                $tenant = Tenant::find($user->tenant_id);
                if ($tenant?->is_active) {
                    app()->instance('current_tenant', $tenant);
                }
                return $next($request);
            }

            // Usuário comum logado navegando na loja pública:
            // não tem tenant_id → usa o header X-Tenant-Slug da URL
        }

        // Sem autenticação OU usuário comum: tenant vem da URL (/c/:slug)
        $slug = $request->header('X-Tenant-Slug');
        if ($slug) {
            $tenant = Tenant::where('slug', $slug)->where('is_active', true)->first();
            if ($tenant) {
                app()->instance('current_tenant', $tenant);
            }
        }

        return $next($request);
    }
}
