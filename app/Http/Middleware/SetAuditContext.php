<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Define variáveis de sessão MySQL que os triggers usam para
 * registrar qual usuário e IP realizou cada operação auditada.
 *
 * @audit_user_id → ID do usuário autenticado (ou NULL)
 * @audit_ip      → IP da requisição (ou NULL)
 */
class SetAuditContext
{
    public function handle(Request $request, Closure $next): mixed
    {
        $userId = auth('sanctum')->id();
        $ip     = $request->ip();

        DB::statement('SET @audit_user_id = ?, @audit_ip = ?', [
            $userId,
            $ip,
        ]);

        return $next($request);
    }
}