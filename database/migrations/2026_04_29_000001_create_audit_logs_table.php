<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('tabela', 64)->comment('Nome da tabela afetada');
            $table->enum('operacao', ['INSERT', 'UPDATE', 'DELETE']);
            $table->unsignedBigInteger('registro_id')->nullable()->comment('PK do registro afetado');
            $table->unsignedBigInteger('tenant_id')->nullable()->comment('Isolamento multi-tenant');
            $table->unsignedBigInteger('usuario_id')->nullable()->comment('Usuário que realizou a ação (via @audit_user_id)');
            $table->string('ip_address', 45)->nullable()->comment('IP via @audit_ip (definido pelo middleware)');
            $table->json('dados_anteriores')->nullable()->comment('Valores BEFORE (UPDATE/DELETE)');
            $table->json('dados_novos')->nullable()->comment('Valores AFTER (INSERT/UPDATE)');
            $table->text('observacao')->nullable()->comment('Contexto adicional opcional');
            $table->timestamp('created_at')->useCurrent();

            // ── Índices para consultas comuns ──────────────────────────────────
            $table->index(['tabela', 'registro_id'],  'idx_tabela_registro');
            $table->index(['tabela', 'operacao'],     'idx_tabela_operacao');
            $table->index('tenant_id',                'idx_tenant');
            $table->index('usuario_id',               'idx_usuario');
            $table->index('created_at',               'idx_created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};