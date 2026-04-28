<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Tenants (atléticas) ───────────────────────────────────────────────
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('university')->nullable();
            $table->text('description')->nullable();
            $table->string('logo')->nullable();
            $table->string('plan')->default('basic');
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        // ── Adiciona role + tenant_id na tabela de usuários ───────────────────
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('tenant_id')
                ->nullable()->after('id')
                ->constrained('tenants')->nullOnDelete();
            $table->enum('role', ['super_admin', 'admin', 'user'])
                ->default('user')->after('email');
        });

        // ── Tokens Sanctum ────────────────────────────────────────────────────
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_access_tokens');
        Schema::table('users', fn ($t) => $t->dropColumn(['tenant_id', 'role']));
        Schema::dropIfExists('tenants');
    }
};
