<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    public $timestamps = false;
    public $incrementing = true;

    protected $fillable = [
        'tabela', 'operacao', 'registro_id', 'tenant_id',
        'usuario_id', 'ip_address', 'dados_anteriores', 'dados_novos', 'observacao',
    ];

    protected function casts(): array
    {
        return [
            'dados_anteriores' => 'array',
            'dados_novos'      => 'array',
            'created_at'       => 'datetime',
        ];
    }

    // ── Relacionamentos ────────────────────────────────────────────────────────
    public function usuario(): BelongsTo { return $this->belongsTo(User::class, 'usuario_id'); }
    public function tenant(): BelongsTo  { return $this->belongsTo(Tenant::class, 'tenant_id'); }

    // ── Scopes de consulta ─────────────────────────────────────────────────────
    public function scopeTabela(Builder $q, string $tabela): Builder
    {
        return $q->where('tabela', $tabela);
    }

    public function scopeRegistro(Builder $q, int $id): Builder
    {
        return $q->where('registro_id', $id);
    }

    public function scopePeriodo(Builder $q, string $inicio, string $fim): Builder
    {
        return $q->whereBetween('created_at', [$inicio, $fim]);
    }

    public function scopeUsuario(Builder $q, int $userId): Builder
    {
        return $q->where('usuario_id', $userId);
    }

    // ── Utilitário: campos que mudaram num UPDATE ──────────────────────────────
    public function camposAlterados(): array
    {
        if ($this->operacao !== 'UPDATE') return [];

        $antes  = $this->dados_anteriores ?? [];
        $depois = $this->dados_novos      ?? [];
        $diff   = [];

        foreach ($depois as $campo => $novoValor) {
            $antigoValor = $antes[$campo] ?? null;
            if ($antigoValor !== $novoValor) {
                $diff[$campo] = ['de' => $antigoValor, 'para' => $novoValor];
            }
        }

        return $diff;
    }
}