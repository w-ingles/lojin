<?php

use App\Http\Controllers\Api\Admin;
use App\Http\Controllers\Api\Admin\TicketValidationController;
use App\Http\Controllers\Api\Admin\TenantProfileController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Store;
use App\Http\Controllers\Api\Store\CommissionerSalesController;
use App\Http\Controllers\Api\Store\UserProfileController;
use App\Http\Controllers\Api\SuperAdmin\TenantController;
use App\Http\Controllers\Api\SuperAdmin\UniversityController;
use App\Http\Controllers\Api\SuperAdmin\CommissionerController as SuperAdminCommissionerController;
use Illuminate\Support\Facades\Route;

// ── Autenticação ──────────────────────────────────────────────────────────────
Route::post('/auth/pre-register',     [AuthController::class, 'preRegister']);
Route::post('/auth/confirm-register', [AuthController::class, 'confirmRegister']);
Route::post('/auth/resend-code',      [AuthController::class, 'resendCode']);
Route::post('/auth/login',            [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me',      [AuthController::class, 'me']);
});

// ── Catálogo público de atléticas ────────────────────────────────────────────
Route::get('/catalogo', function (\Illuminate\Http\Request $request) {
    $query = \App\Models\Tenant::where('is_active', true)
        ->with('university:id,name,acronym')
        ->withCount(['events' => fn ($q) => $q->where('status', 'active')])
        ->when($request->filled('search'), fn ($q) =>
            $q->where('name', 'like', '%' . $request->string('search') . '%')
        )
        ->when($request->filled('university_id'), fn ($q) =>
            $q->where('university_id', $request->integer('university_id'))
        )
        ->orderBy('name');

    return response()->json($query->paginate(24));
});

Route::get('/catalogo/universidades', function () {
    return response()->json(
        \App\Models\University::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'acronym'])
    );
});

// ── Info pública da atlética ──────────────────────────────────────────────────
Route::get('/atletica/{slug}', function (string $slug) {
    $tenant = \App\Models\Tenant::where('slug', $slug)->where('is_active', true)
        ->with('university:id,name,acronym')
        ->select('id','name','slug','university_id','description')->first();
    abort_unless($tenant, 404, 'Atlética não encontrada.');
    return response()->json($tenant);
});

// ── Loja pública (requer tenant via X-Tenant-Slug) ────────────────────────────
Route::middleware('require.tenant')->group(function () {
    Route::get('/events',             [Store\EventController::class, 'index']);
    Route::get('/events/{event}',     [Store\EventController::class, 'show']);
    Route::get('/products',           [Store\ProductController::class, 'index']);
    Route::get('/products/{product}', [Store\ProductController::class, 'show']);
    Route::get('/orders/{id}',        [Store\OrderController::class, 'show']);
});

// ── Usuário logado na loja ────────────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'require.tenant'])->group(function () {
    Route::get('/my-orders',           [Store\OrderController::class, 'myOrders']);
    Route::get('/my-tickets',          [Store\OrderController::class, 'meusIngressos']);
    Route::get('/commissioner/status', [Store\OrderController::class, 'commissionerStatus']);

    // Perfil do usuário na loja
    Route::get('/user/profile', [UserProfileController::class, 'show']);
    Route::put('/user/profile', [UserProfileController::class, 'update']);

    // ── Fluxo exclusivo de comissários (CPF → cliente → venda) ───────────────
    Route::get('/commissioner/lookup',  [CommissionerSalesController::class, 'lookup']);
    Route::post('/commissioner/orders', [CommissionerSalesController::class, 'store']);
});

// ── Checkout do usuário — exige autenticação E perfil completo ────────────────
Route::middleware(['auth:sanctum', 'require.tenant', 'profile.complete'])->group(function () {
    Route::post('/orders', [Store\OrderController::class, 'store']);
});

// ── Admin da atlética ─────────────────────────────────────────────────────────
Route::middleware(['auth:sanctum','admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [Admin\DashboardController::class, 'index']);

    Route::apiResource('events', Admin\EventController::class);
    Route::apiResource('events.batches', Admin\TicketBatchController::class)
        ->only(['store','update','destroy'])->shallow();

    Route::get('/products',              [Admin\ProductController::class, 'index']);
    Route::post('/products',             [Admin\ProductController::class, 'store']);
    Route::post('/products/{product}',   [Admin\ProductController::class, 'update']);
    Route::delete('/products/{product}', [Admin\ProductController::class, 'destroy']);
    Route::get('/categories',            [Admin\ProductController::class, 'categories']);
    Route::post('/categories',           [Admin\ProductController::class, 'storeCategory']);

    Route::get('/orders',                  [Admin\OrderController::class, 'index']);
    Route::get('/orders/{order}',          [Admin\OrderController::class, 'show']);
    Route::patch('/orders/{order}/status', [Admin\OrderController::class, 'updateStatus']);

    Route::get('/reports/sales',  [Admin\ReportController::class, 'vendas']);
    Route::get('/reports/export', [Admin\ReportController::class, 'exportar']);

    Route::post('/tickets/validate', [TicketValidationController::class, 'validate']);

    Route::get('/tenant/profile',           [TenantProfileController::class, 'show']);
    Route::post('/tenant/profile',          [TenantProfileController::class, 'update']);
    Route::post('/tenant/profile/banner',   [TenantProfileController::class, 'uploadBanner']);
    Route::delete('/tenant/profile/banner', [TenantProfileController::class, 'removeBanner']);
    Route::post('/tenant/profile/logo',     [TenantProfileController::class, 'uploadLogo']);
    Route::delete('/tenant/profile/logo',   [TenantProfileController::class, 'removeLogo']);

    Route::get('/comissarios',                              [Admin\CommissionerController::class, 'index']);
    Route::post('/comissarios',                             [Admin\CommissionerController::class, 'store']);
    Route::put('/comissarios/{commissioner}',               [Admin\CommissionerController::class, 'update']);
    Route::delete('/comissarios/{commissioner}',            [Admin\CommissionerController::class, 'destroy']);
    Route::get('/comissarios/{commissioner}/vendas',        [Admin\CommissionerController::class, 'vendas']);
});

// ── Super Admin ───────────────────────────────────────────────────────────────
Route::middleware(['auth:sanctum','super_admin'])->prefix('super-admin')->group(function () {
    Route::get('/overview',                           [TenantController::class, 'overview']);
    Route::get('/atleticas',                          [TenantController::class, 'index']);
    Route::post('/atleticas',                         [TenantController::class, 'store']);
    Route::put('/atleticas/{tenant}',                 [TenantController::class, 'update']);
    Route::patch('/atleticas/{tenant}/toggle',        [TenantController::class, 'toggleAtivo']);
    Route::post('/atleticas/{tenant}/impersonate',    [TenantController::class, 'impersonate']);
    Route::get('/atleticas/{tenant}/users',           [TenantController::class, 'usuarios']);
    Route::post('/atleticas/{tenant}/users',          [TenantController::class, 'criarUsuario']);
    Route::delete('/atleticas/{tenant}/users/{user}', [TenantController::class, 'excluirUsuario']);

    Route::get('/universidades',                      [UniversityController::class, 'index']);
    Route::post('/universidades',                     [UniversityController::class, 'store']);
    Route::put('/universidades/{university}',         [UniversityController::class, 'update']);
    Route::delete('/universidades/{university}',      [UniversityController::class, 'destroy']);

    Route::get('/comissarios',                        [SuperAdminCommissionerController::class, 'index']);
});
