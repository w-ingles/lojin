<?php

use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureSuperAdmin;
use App\Http\Middleware\IdentifyTenant;
use App\Http\Middleware\RequireCompleteProfile;
use App\Http\Middleware\RequireTenant;
use App\Http\Middleware\SetAuditContext;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->appendToGroup('api', IdentifyTenant::class);
        $middleware->appendToGroup('api', SetAuditContext::class);
        $middleware->alias([
            'admin'            => EnsureAdmin::class,
            'super_admin'      => EnsureSuperAdmin::class,
            'require.tenant'   => RequireTenant::class,
            'profile.complete' => RequireCompleteProfile::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
