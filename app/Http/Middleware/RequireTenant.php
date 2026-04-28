<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!app()->bound('current_tenant')) {
            return response()->json(['message' => 'Atlética não identificada.'], 404);
        }
        return $next($request);
    }
}
