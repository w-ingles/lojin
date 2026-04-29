# Auditoria e Logs do Sistema — Lojin

## Visão Geral

O sistema de auditoria do Lojin registra automaticamente todas as operações críticas
(INSERT, UPDATE, DELETE) nas tabelas sensíveis do banco de dados utilizando **triggers MySQL**.

O objetivo é garantir **rastreabilidade completa** de quem fez o quê, quando e de qual IP,
sem depender exclusivamente da camada de aplicação Laravel.

---

## Arquitetura

```
Requisição API
      │
      ▼
SetAuditContext (Middleware)
      │  → SET @audit_user_id = {id do usuário autenticado}
      │  → SET @audit_ip = {IP da requisição}
      │
      ▼
Controller → Model → MySQL
      │
      ▼
Trigger AFTER INSERT / UPDATE / DELETE
      │  → Lê @audit_user_id e @audit_ip da sessão
      │  → INSERT INTO audit_logs (...)
      ▼
   audit_logs
```

O middleware `SetAuditContext` injeta variáveis de sessão MySQL antes de cada
requisição autenticada. Os triggers lêem essas variáveis para saber qual usuário
e qual IP realizaram cada operação.

---

## Estrutura da Tabela `audit_logs`

```sql
CREATE TABLE audit_logs (
    id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tabela            VARCHAR(64)                        NOT NULL,  -- Tabela afetada
    operacao          ENUM('INSERT','UPDATE','DELETE')   NOT NULL,  -- Tipo de operação
    registro_id       BIGINT UNSIGNED                    NULL,      -- PK do registro afetado
    tenant_id         BIGINT UNSIGNED                    NULL,      -- Isolamento multi-tenant
    usuario_id        BIGINT UNSIGNED                    NULL,      -- Quem fez a ação
    ip_address        VARCHAR(45)                        NULL,      -- IP de origem
    dados_anteriores  JSON                               NULL,      -- Valores BEFORE (UPDATE/DELETE)
    dados_novos       JSON                               NULL,      -- Valores AFTER  (INSERT/UPDATE)
    observacao        TEXT                               NULL,      -- Contexto adicional
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,

    INDEX idx_tabela_registro (tabela, registro_id),
    INDEX idx_tabela_operacao (tabela, operacao),
    INDEX idx_tenant          (tenant_id),
    INDEX idx_usuario         (usuario_id),
    INDEX idx_created_at      (created_at)
);
```

### Campos explicados

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `tabela` | VARCHAR(64) | Nome exato da tabela afetada (`orders`, `tickets`, etc.) |
| `operacao` | ENUM | `INSERT`, `UPDATE` ou `DELETE` |
| `registro_id` | BIGINT | Chave primária do registro que sofreu a operação |
| `tenant_id` | BIGINT | Permite filtrar logs por atlética (multi-tenant) |
| `usuario_id` | BIGINT | ID do usuário autenticado via `@audit_user_id` |
| `ip_address` | VARCHAR(45) | IPv4 ou IPv6 via `@audit_ip` |
| `dados_anteriores` | JSON | Snapshot dos campos **antes** da alteração (UPDATE/DELETE) |
| `dados_novos` | JSON | Snapshot dos campos **após** a alteração (INSERT/UPDATE) |
| `created_at` | TIMESTAMP | Momento exato da operação |

---

## Tabelas Auditadas

### Criticidade Crítica 🔴

#### `orders` — Pedidos

Operações auditadas: `INSERT`, `UPDATE`, `DELETE`

O UPDATE só gera log quando um destes campos muda:
- `status` — transição do pedido (pending → paid → cancelled → refunded)
- `total` / `subtotal` — valores financeiros
- `payment_method` — forma de pagamento
- `payment_id` — identificador de pagamento externo
- `paid_at` — timestamp de confirmação do pagamento

**Dados registrados:**
```json
// dados_anteriores / dados_novos
{
  "status":         "pending",
  "total":          "150.00",
  "subtotal":       "150.00",
  "payment_method": null,
  "payment_id":     null,
  "paid_at":        null
}
```

---

#### `tickets` — Ingressos

Operações auditadas: `INSERT`, `UPDATE`, `DELETE`

O UPDATE só gera log quando muda:
- `status` — ciclo de vida: `reserved → paid → used → cancelled`
- `used_at` — momento da validação na portaria

**Dados registrados:**
```json
// INSERT
{
  "code":            "A1B2C3D4E5F6G7H8",
  "status":          "reserved",
  "ticket_batch_id": 3,
  "user_id":         12,
  "order_item_id":   45
}

// UPDATE (validação na portaria)
// dados_anteriores: { "status": "paid",   "used_at": null }
// dados_novos:      { "status": "used",   "used_at": "2026-04-29 21:30:00" }
```

---

#### `users` — Usuários

Operações auditadas: `INSERT`, `UPDATE`, `DELETE`

O UPDATE só gera log quando muda:
- `role` — mudança de permissão (crítico para segurança)
- `email` — mudança de identificador de acesso
- `name` — mudança de nome
- `cpf` — dado pessoal sensível
- `password` — troca de senha
- `tenant_id` — mudança de vínculo com atlética

**Proteção de dados sensíveis (LGPD):**
- `password` → nunca salvo em texto claro; substituído por `"***"`
- `cpf` → mascarado: os primeiros 6 dígitos + `*****`
  - Exemplo: `"123456*****"` (CPF real: `123.456.789-10`)

```json
// UPDATE (mudança de role)
// dados_anteriores: { "role": "user",  "email": "joao@x.com", "cpf": "123456*****", "password": "***" }
// dados_novos:      { "role": "admin", "email": "joao@x.com", "cpf": "123456*****", "password": "***" }
```

---

### Criticidade Alta 🟠

#### `tenants` — Atléticas

UPDATE registra mudanças em: `plan`, `is_active`, `name`, `slug`, `university_id`

```json
// Exemplo: atlética desativada pelo super admin
// dados_anteriores: { "plan": "premium", "is_active": true }
// dados_novos:      { "plan": "premium", "is_active": false }
```

---

#### `ticket_batches` — Lotes de Ingressos

UPDATE registra mudanças em: `price`, `quantity`, `sold`, `is_active`

Importante: toda alteração de **preço** fica registrada com o valor antigo e novo,
permitindo auditoria de reprecificação de lotes.

---

#### `products` — Produtos

UPDATE registra mudanças em: `price`, `stock`, `active`, `name`

---

#### `commissioners` — Comissários

UPDATE registra mudanças em: `is_active` (ativação/desativação do comissário)

---

### Criticidade Média 🟡

#### `events` — Eventos

UPDATE registra mudanças em: `status`, `name`, `starts_at`

#### `universities` — Universidades

UPDATE registra mudanças em: `name`, `is_active`

#### `personal_access_tokens` — Tokens de Acesso

- `INSERT` → rastreia **login** (criação de token)
- `DELETE` → rastreia **logout** (revogação de token)

Não registra o valor do token em si, apenas metadados:
```json
{
  "tokenable_type": "App\\Models\\User",
  "tokenable_id":   42,
  "name":           "app",
  "last_used_at":   "2026-04-29 21:00:00"
}
```

---

### Tabelas não auditadas ⚪

| Tabela | Motivo |
|--------|--------|
| `email_verifications` | Dados temporários, expiram em 10 min |
| `password_reset_tokens` | Dados temporários, uso único |
| `sessions` | Alto volume, ruído excessivo para o log |
| `order_items` | Imutável após criação, risco baixo |
| `product_categories` | Baixo risco, raramente alterada |

---

## Middleware SetAuditContext

**Localização:** `app/Http/Middleware/SetAuditContext.php`

**Registro:** `bootstrap/app.php` — grupo `api` (executa em toda requisição API)

**Funcionamento:**

```php
// Antes de cada requisição autenticada:
DB::statement('SET @audit_user_id = ?, @audit_ip = ?', [
    auth('sanctum')->id(),  // NULL se não autenticado
    $request->ip(),
]);
```

Essas variáveis de sessão MySQL são lidas pelos triggers no momento da operação.

**Importante:** Se uma operação ocorrer fora do ciclo de uma requisição HTTP
(ex: `php artisan` commands, jobs em background), `@audit_user_id` será `NULL`.
Isso é esperado e o log ainda é gerado com `usuario_id = NULL`.

---

## Model AuditLog

**Localização:** `app/Models/AuditLog.php`

### Scopes disponíveis

```php
// Todos os logs de uma tabela específica
AuditLog::tabela('orders')->get();

// Histórico de um registro específico
AuditLog::tabela('tickets')->registro(42)->get();

// Logs de um período
AuditLog::periodo('2026-04-01', '2026-04-30')->get();

// Logs de um usuário específico
AuditLog::usuario(15)->get();

// Combinando scopes
AuditLog::tabela('orders')
    ->operacao('UPDATE')
    ->tenant(3)
    ->periodo('2026-04-01', '2026-04-30')
    ->with('usuario:id,name,email')
    ->orderByDesc('created_at')
    ->paginate(20);
```

### Método `camposAlterados()`

```php
$log = AuditLog::find(100);

// Retorna apenas os campos que mudaram num UPDATE
$diff = $log->camposAlterados();

// Resultado:
// [
//   'status' => ['de' => 'pending', 'para' => 'paid'],
//   'paid_at' => ['de' => null, 'para' => '2026-04-29 21:30:00'],
// ]
```

---

## Consultas SQL Úteis

### Histórico completo de um pedido

```sql
SELECT
    al.operacao,
    al.dados_anteriores,
    al.dados_novos,
    u.name  AS usuario,
    al.ip_address,
    al.created_at
FROM audit_logs al
LEFT JOIN users u ON u.id = al.usuario_id
WHERE al.tabela = 'orders'
  AND al.registro_id = 42
ORDER BY al.created_at;
```

---

### Quem alterou o role de algum usuário

```sql
SELECT
    al.created_at,
    al.ip_address,
    executor.name              AS alterado_por,
    JSON_UNQUOTE(JSON_EXTRACT(al.dados_anteriores, '$.role')) AS role_anterior,
    JSON_UNQUOTE(JSON_EXTRACT(al.dados_novos,      '$.role')) AS role_novo,
    JSON_UNQUOTE(JSON_EXTRACT(al.dados_novos,      '$.email')) AS usuario_afetado
FROM audit_logs al
LEFT JOIN users executor ON executor.id = al.usuario_id
WHERE al.tabela    = 'users'
  AND al.operacao  = 'UPDATE'
  AND JSON_EXTRACT(al.dados_anteriores, '$.role')
   != JSON_EXTRACT(al.dados_novos,      '$.role')
ORDER BY al.created_at DESC;
```

---

### Ingressos validados hoje por comissário

```sql
SELECT
    al.registro_id              AS ticket_id,
    JSON_UNQUOTE(JSON_EXTRACT(al.dados_novos, '$.status'))   AS status_novo,
    JSON_UNQUOTE(JSON_EXTRACT(al.dados_novos, '$.used_at'))  AS usado_em,
    u.name                      AS validado_por,
    al.ip_address
FROM audit_logs al
LEFT JOIN users u ON u.id = al.usuario_id
WHERE al.tabela   = 'tickets'
  AND al.operacao = 'UPDATE'
  AND JSON_UNQUOTE(JSON_EXTRACT(al.dados_novos, '$.status')) = 'used'
  AND DATE(al.created_at) = CURDATE()
ORDER BY al.created_at DESC;
```

---

### Mudanças de preço em lotes de ingresso

```sql
SELECT
    al.registro_id              AS lote_id,
    JSON_EXTRACT(al.dados_anteriores, '$.price') AS preco_anterior,
    JSON_EXTRACT(al.dados_novos,      '$.price') AS preco_novo,
    u.name                      AS alterado_por,
    al.created_at
FROM audit_logs al
LEFT JOIN users u ON u.id = al.usuario_id
WHERE al.tabela   = 'ticket_batches'
  AND al.operacao = 'UPDATE'
  AND JSON_EXTRACT(al.dados_anteriores, '$.price')
   != JSON_EXTRACT(al.dados_novos,      '$.price')
ORDER BY al.created_at DESC;
```

---

### Histórico de logins de um usuário

```sql
SELECT
    al.ip_address,
    al.created_at                                                   AS login_em,
    JSON_UNQUOTE(JSON_EXTRACT(al.dados_novos, '$.name'))            AS token_nome
FROM audit_logs al
WHERE al.tabela    = 'personal_access_tokens'
  AND al.operacao  = 'INSERT'
  AND al.usuario_id = 42
ORDER BY al.created_at DESC
LIMIT 20;
```

---

### Volume de operações por tabela (últimos 30 dias)

```sql
SELECT
    tabela,
    operacao,
    COUNT(*) AS total,
    DATE(MIN(created_at)) AS primeiro,
    DATE(MAX(created_at)) AS ultimo
FROM audit_logs
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY tabela, operacao
ORDER BY total DESC;
```

---

## Estratégia de Retenção e Arquivamento

### Ciclo de vida dos logs

```
0 ──── 90 dias ──── 365 dias ──── 5 anos ──── ∞
│                  │             │
│   audit_logs     │  arquivo    │  cold storage
│   (tabela viva)  │  (S3/GCS)   │  (compliance)
```

### Job de arquivamento (Laravel Scheduler)

Registrar em `routes/console.php`:

```php
Schedule::call(function () {
    // 1. Exportar logs antigos para arquivo JSON/CSV
    $logs = AuditLog::where('created_at', '<', now()->subDays(90))
        ->orderBy('created_at')
        ->chunk(1000, function ($chunk) {
            // Salvar em storage ou enviar para S3
            Storage::disk('s3')->append(
                'audit/' . now()->format('Y-m') . '.jsonl',
                $chunk->toJson()
            );
        });

    // 2. Deletar logs arquivados da tabela principal
    AuditLog::where('created_at', '<', now()->subDays(90))->delete();

})->monthly()->name('audit:archive')->withoutOverlapping();
```

### Recomendação de particionamento (alto volume)

Para sistemas com mais de 1 milhão de operações/mês, considerar
particionamento por range de data no MySQL:

```sql
ALTER TABLE audit_logs PARTITION BY RANGE (UNIX_TIMESTAMP(created_at)) (
    PARTITION p_2026_04 VALUES LESS THAN (UNIX_TIMESTAMP('2026-05-01')),
    PARTITION p_2026_05 VALUES LESS THAN (UNIX_TIMESTAMP('2026-06-01')),
    PARTITION p_future  VALUES LESS THAN MAXVALUE
);
```

Isso permite `ALTER TABLE audit_logs DROP PARTITION p_2026_04` para purge
instantâneo sem impacto na tabela principal.

---

## Conformidade LGPD

### Dados pessoais nos logs

| Campo auditado | Tratamento |
|----------------|------------|
| `users.password` | Nunca salvo — substituído por `"***"` |
| `users.cpf` | Mascarado: `"123456*****"` (últimos 5 dígitos ocultos) |
| `orders.customer_cpf` | Já criptografado pelo Laravel antes de chegar ao trigger; o log contém apenas o blob criptografado |
| `users.email` | Salvo em claro — necessário para rastreabilidade |
| `orders.customer_name` | Salvo em claro — necessário para rastreabilidade |

### Base legal para auditoria (Art. 7º LGPD)

O registro de logs de auditoria se enquadra no **legítimo interesse** do
controlador (Art. 7º, IX) e na **obrigação legal** de rastreabilidade
em sistemas que processam pagamentos e dados pessoais.

Recomenda-se documentar essa finalidade na Política de Privacidade da plataforma.

### Direito de acesso e exclusão

Em caso de solicitação de exclusão de dados por um titular (Art. 18º LGPD):
- Os registros de **audit_logs** que contêm dados do titular devem ser
  avaliados individualmente
- Logs de operações financeiras podem ser mantidos por obrigação legal
  (até 5 anos para fins contábeis/fiscais)
- Dados meramente identificativos podem ser anonimizados no log

---

## Riscos e Limitações Conhecidas

### 1. Operações via `DB::statement()` raw

Triggers capturam **todas** as operações no banco, inclusive raw SQL.
Porém, se `@audit_user_id` não for definido antes (ex: migrações, seeds),
o log terá `usuario_id = NULL`. Isso é esperado e aceitável.

### 2. Jobs em background / Queue

Workers Horizon/Queue não passam pelo middleware HTTP. Para auditar
operações sensíveis em jobs, usar o Observer Laravel em conjunto:

```php
// app/Observers/TicketObserver.php
class TicketObserver
{
    public function updated(Ticket $ticket): void
    {
        // Complementa o trigger para contexto de jobs
        if ($ticket->wasChanged('status')) {
            DB::statement('SET @audit_user_id = ?', [
                auth()->id() ?? 0  // 0 = sistema/automático
            ]);
        }
    }
}
```

### 3. Cascata de deletes

Quando um tenant é deletado, registros filhos são deletados por
`cascadeOnDelete`. Os triggers de DELETE **são disparados** para cada
registro filho individualmente — portanto o log captura tudo.
O volume de logs pode ser alto nesse caso.

### 4. Performance

Cada operação auditada executa um INSERT adicional em `audit_logs`.
Em operações de alto volume (ex: importação em massa de tickets), isso
pode causar lentidão. Nesses casos, desativar temporariamente os triggers:

```sql
SET @audit_user_id = NULL; -- Triggers ainda executam mas usuario_id fica NULL
-- ou
ALTER TABLE tickets DISABLE KEYS; -- Apenas para MyISAM; InnoDB não suporta
```

Alternativa recomendada para bulk inserts: usar `DB::unprepared()` fora
do ciclo de triggers ou inserir diretamente via `LOAD DATA INFILE`.

---

## Arquivos Relacionados

| Arquivo | Descrição |
|---------|-----------|
| `app/Http/Middleware/SetAuditContext.php` | Injeta @audit_user_id e @audit_ip |
| `app/Models/AuditLog.php` | Model com scopes e helpers de consulta |
| `database/migrations/2026_04_29_000001_create_audit_logs_table.php` | Cria a tabela audit_logs |
| `database/migrations/2026_04_29_000002_create_audit_triggers.php` | Cria os 29 triggers |
| `bootstrap/app.php` | Registro do middleware no grupo api |

---

## Como Aplicar

```bash
# Executar as migrations (cria a tabela e os triggers)
php artisan migrate

# Verificar triggers criados no banco
SHOW TRIGGERS;

# Testar: fazer qualquer operação e consultar
SELECT * FROM audit_logs ORDER BY created_at DESC LIMIT 10;
```
