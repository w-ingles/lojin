<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Cria triggers de auditoria para todas as tabelas críticas do sistema.
 *
 * Convenção de nomes: trg_{tabela}_{insert|update|delete}
 *
 * Os triggers lêem @audit_user_id e @audit_ip injetados pelo
 * middleware SetAuditContext em cada requisição autenticada.
 *
 * Para UPDATE: só gera log quando campos relevantes realmente mudam,
 * usando o operador <=> (NULL-safe) do MySQL para evitar falsos positivos.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ══════════════════════════════════════════════════════════════════════
        // ORDERS — tabela mais crítica (status financeiro, CPF criptografado)
        // ══════════════════════════════════════════════════════════════════════

        DB::unprepared('
            CREATE TRIGGER trg_orders_insert
            AFTER INSERT ON orders FOR EACH ROW
            BEGIN
                INSERT INTO audit_logs
                    (tabela, operacao, registro_id, tenant_id, usuario_id, ip_address, dados_novos)
                VALUES (
                    "orders", "INSERT", NEW.id, NEW.tenant_id, @audit_user_id, @audit_ip,
                    JSON_OBJECT(
                        "customer_name",  NEW.customer_name,
                        "customer_email", NEW.customer_email,
                        "subtotal",       NEW.subtotal,
                        "total",          NEW.total,
                        "status",         NEW.status,
                        "commissioner_id",NEW.commissioner_id,
                        "user_id",        NEW.user_id
                    )
                );
            END
        ');

        DB::unprepared('
            CREATE TRIGGER trg_orders_update
            AFTER UPDATE ON orders FOR EACH ROW
            BEGIN
                -- Só loga se algum campo financeiro ou de status mudou
                IF NOT (OLD.status         <=> NEW.status)
                OR NOT (OLD.total          <=> NEW.total)
                OR NOT (OLD.subtotal       <=> NEW.subtotal)
                OR NOT (OLD.payment_method <=> NEW.payment_method)
                OR NOT (OLD.payment_id     <=> NEW.payment_id)
                OR NOT (OLD.paid_at        <=> NEW.paid_at)
                THEN
                    INSERT INTO audit_logs
                        (tabela, operacao, registro_id, tenant_id, usuario_id, ip_address,
                         dados_anteriores, dados_novos)
                    VALUES (
                        "orders", "UPDATE", NEW.id, NEW.tenant_id, @audit_user_id, @audit_ip,
                        JSON_OBJECT(
                            "status",         OLD.status,
                            "total",          OLD.total,
                            "subtotal",       OLD.subtotal,
                            "payment_method", OLD.payment_method,
                            "payment_id",     OLD.payment_id,
                            "paid_at",        OLD.paid_at
                        ),
                        JSON_OBJECT(
                            "status",         NEW.status,
                            "total",          NEW.total,
                            "subtotal",       NEW.subtotal,
                            "payment_method", NEW.payment_method,
                            "payment_id",     NEW.payment_id,
                            "paid_at",        NEW.paid_at
                        )
                    );
                END IF;
            END
        ');

        DB::unprepared('
            CREATE TRIGGER trg_orders_delete
            AFTER DELETE ON orders FOR EACH ROW
            BEGIN
                INSERT INTO audit_logs
                    (tabela, operacao, registro_id, tenant_id, usuario_id, ip_address, dados_anteriores)
                VALUES (
                    "orders", "DELETE", OLD.id, OLD.tenant_id, @audit_user_id, @audit_ip,
                    JSON_OBJECT(
                        "customer_name",  OLD.customer_name,
                        "customer_email", OLD.customer_email,
                        "total",          OLD.total,
                        "status",         OLD.status
                    )
                );
            END
        ');

        // ══════════════════════════════════════════════════════════════════════
        // TICKETS — ciclo de vida do ingresso (reservado → pago → usado → cancelado)
        // ══════════════════════════════════════════════════════════════════════

        DB::unprepared('
            CREATE TRIGGER trg_tickets_insert
            AFTER INSERT ON tickets FOR EACH ROW
            BEGIN
                INSERT INTO audit_logs
                    (tabela, operacao, registro_id, tenant_id, usuario_id, ip_address, dados_novos)
                VALUES (
                    "tickets", "INSERT", NEW.id, NEW.tenant_id, @audit_user_id, @audit_ip,
                    JSON_OBJECT(
                        "code",            NEW.code,
                        "status",          NEW.status,
                        "ticket_batch_id", NEW.ticket_batch_id,
                        "user_id",         NEW.user_id,
                        "order_item_id",   NEW.order_item_id
                    )
                );
            END
        ');

        DB::unprepared('
            CREATE TRIGGER trg_tickets_update
            AFTER UPDATE ON tickets FOR EACH ROW
            BEGIN
                -- Só loga mudanças de status (principal evento do ciclo de vida)
                IF NOT (OLD.status  <=> NEW.status)
                OR NOT (OLD.used_at <=> NEW.used_at)
                THEN
                    INSERT INTO audit_logs
                        (tabela, operacao, registro_id, tenant_id, usuario_id, ip_address,
                         dados_anteriores, dados_novos)
                    VALUES (
                        "tickets", "UPDATE", NEW.id, NEW.tenant_id, @audit_user_id, @audit_ip,
                        JSON_OBJECT("status", OLD.status, "used_at", OLD.used_at),
                        JSON_OBJECT("status", NEW.status, "used_at", NEW.used_at)
                    );
                END IF;
            END
        ');

        DB::unprepared('
            CREATE TRIGGER trg_tickets_delete
            AFTER DELETE ON tickets FOR EACH ROW
            BEGIN
                INSERT INTO audit_logs
                    (tabela, operacao, registro_id, tenant_id, usuario_id, ip_address, dados_anteriores)
                VALUES (
                    "tickets", "DELETE", OLD.id, OLD.tenant_id, @audit_user_id, @audit_ip,
                    JSON_OBJECT(
                        "code",   OLD.code,
                        "status", OLD.status,
                        "used_at",OLD.used_at
                    )
                );
            END
        ');

        // ══════════════════════════════════════════════════════════════════════
        // USERS — mudanças de role e dados pessoais sensíveis
        // A senha nunca é gravada no log (substituída por ***)
        // O CPF é armazenado mascarado: XXX.XXX.***-**
        // ══════════════════════════════════════════════════════════════════════

        DB::unprepared('
            CREATE TRIGGER trg_users_insert
            AFTER INSERT ON users FOR EACH ROW
            BEGIN
                INSERT INTO audit_logs
                    (tabela, operacao, registro_id, tenant_id, usuario_id, ip_address, dados_novos)
                VALUES (
                    "users", "INSERT", NEW.id, NEW.tenant_id, @audit_user_id, @audit_ip,
                    JSON_OBJECT(
                        "name",      NEW.name,
                        "email",     NEW.email,
                        "role",      NEW.role,
                        "cpf",       IF(NEW.cpf IS NOT NULL,
                                        CONCAT(LEFT(NEW.cpf, 6), "*****"),
                                        NULL),
                        "tenant_id", NEW.tenant_id
                    )
                );
            END
        ');

        DB::unprepared('
            CREATE TRIGGER trg_users_update
            AFTER UPDATE ON users FOR EACH ROW
            BEGIN
                IF NOT (OLD.role      <=> NEW.role)
                OR NOT (OLD.email     <=> NEW.email)
                OR NOT (OLD.name      <=> NEW.name)
                OR NOT (OLD.tenant_id <=> NEW.tenant_id)
                OR NOT (OLD.cpf       <=> NEW.cpf)
                OR NOT (OLD.password  <=> NEW.password)
                THEN
                    INSERT INTO audit_logs
                        (tabela, operacao, registro_id, tenant_id, usuario_id, ip_address,
                         dados_anteriores, dados_novos)
                    VALUES (
                        "users", "UPDATE", NEW.id, NEW.tenant_id, @audit_user_id, @audit_ip,
                        JSON_OBJECT(
                            "name",      OLD.name,
                            "email",     OLD.email,
                            "role",      OLD.role,
                            "cpf",       IF(OLD.cpf IS NOT NULL, CONCAT(LEFT(OLD.cpf,6),"*****"), NULL),
                            "password",  "***",
                            "tenant_id", OLD.tenant_id
                        ),
                        JSON_OBJECT(
                            "name",      NEW.name,
                            "email",     NEW.email,
                            "role",      NEW.role,
                            "cpf",       IF(NEW.cpf IS NOT NULL, CONCAT(LEFT(NEW.cpf,6),"*****"), NULL),
                            "password",  "***",
                            "tenant_id", NEW.tenant_id
                        )
                    );
                END IF;
            END
        ');

        DB::unprepared('
            CREATE TRIGGER trg_users_delete
            AFTER DELETE ON users FOR EACH ROW
            BEGIN
                INSERT INTO audit_logs
                    (tabela, operacao, registro_id, tenant_id, usuario_id, ip_address, dados_anteriores)
                VALUES (
                    "users", "DELETE", OLD.id, OLD.tenant_id, @audit_user_id, @audit_ip,
                    JSON_OBJECT(
                        "name",  OLD.name,
                        "email", OLD.email,
                        "role",  OLD.role
                    )
                );
            END
        ');

        // ══════════════════════════════════════════════════════════════════════
        // TENANTS — mudanças de plano e status de ativação
        // ══════════════════════════════════════════════════════════════════════

        DB::unprepared('
            CREATE TRIGGER trg_tenants_insert
            AFTER INSERT ON tenants FOR EACH ROW
            BEGIN
                INSERT INTO audit_logs
                    (tabela, operacao, registro_id, usuario_id, ip_address, dados_novos)
                VALUES (
                    "tenants", "INSERT", NEW.id, @audit_user_id, @audit_ip,
                    JSON_OBJECT(
                        "name",          NEW.name,
                        "slug",          NEW.slug,
                        "plan",          NEW.plan,
                        "is_active",     NEW.is_active,
                        "university_id", NEW.university_id
                    )
                );
            END
        ');

        DB::unprepared('
            CREATE TRIGGER trg_tenants_update
            AFTER UPDATE ON tenants FOR EACH ROW
            BEGIN
                IF NOT (OLD.plan          <=> NEW.plan)
                OR NOT (OLD.is_active     <=> NEW.is_active)
                OR NOT (OLD.name          <=> NEW.name)
                OR NOT (OLD.slug          <=> NEW.slug)
                OR NOT (OLD.university_id <=> NEW.university_id)
                THEN
                    INSERT INTO audit_logs
                        (tabela, operacao, registro_id, usuario_id, ip_address,
                         dados_anteriores, dados_novos)
                    VALUES (
                        "tenants", "UPDATE", NEW.id, @audit_user_id, @audit_ip,
                        JSON_OBJECT(
                            "name",          OLD.name,
                            "slug",          OLD.slug,
                            "plan",          OLD.plan,
                            "is_active",     OLD.is_active,
                            "university_id", OLD.university_id
                        ),
                        JSON_OBJECT(
                            "name",          NEW.name,
                            "slug",          NEW.slug,
                            "plan",          NEW.plan,
                            "is_active",     NEW.is_active,
                            "university_id", NEW.university_id
                        )
                    );
                END IF;
            END
        ');

        DB::unprepared('
            CREATE TRIGGER trg_tenants_delete
            AFTER DELETE ON tenants FOR EACH ROW
            BEGIN
                INSERT INTO audit_logs
                    (tabela, operacao, registro_id, usuario_id, ip_address, dados_anteriores)
                VALUES (
                    "tenants", "DELETE", OLD.id, @audit_user_id, @audit_ip,
                    JSON_OBJECT("name", OLD.name, "slug", OLD.slug, "plan", OLD.plan)
                );
            END
        ');

        // ══════════════════════════════════════════════════════════════════════
        // TICKET_BATCHES — preço e estoque são campos financeiros críticos
        // ══════════════════════════════════════════════════════════════════════

        DB::unprepared('
            CREATE TRIGGER trg_ticket_batches_insert
            AFTER INSERT ON ticket_batches FOR EACH ROW
            BEGIN
                INSERT INTO audit_logs
                    (tabela, operacao, registro_id, tenant_id, usuario_id, ip_address, dados_novos)
                VALUES (
                    "ticket_batches", "INSERT", NEW.id, NEW.tenant_id, @audit_user_id, @audit_ip,
                    JSON_OBJECT(
                        "name",      NEW.name,
                        "price",     NEW.price,
                        "quantity",  NEW.quantity,
                        "is_active", NEW.is_active,
                        "event_id",  NEW.event_id
                    )
                );
            END
        ');

        DB::unprepared('
            CREATE TRIGGER trg_ticket_batches_update
            AFTER UPDATE ON ticket_batches FOR EACH ROW
            BEGIN
                IF NOT (OLD.price     <=> NEW.price)
                OR NOT (OLD.quantity  <=> NEW.quantity)
                OR NOT (OLD.sold      <=> NEW.sold)
                OR NOT (OLD.is_active <=> NEW.is_active)
                THEN
                    INSERT INTO audit_logs
                        (tabela, operacao, registro_id, tenant_id, usuario_id, ip_address,
                         dados_anteriores, dados_novos)
                    VALUES (
                        "ticket_batches", "UPDATE", NEW.id, NEW.tenant_id, @audit_user_id, @audit_ip,
                        JSON_OBJECT(
                            "price", OLD.price, "quantity", OLD.quantity,
                            "sold",  OLD.sold,  "is_active", OLD.is_active
                        ),
                        JSON_OBJECT(
                            "price", NEW.price, "quantity", NEW.quantity,
                            "sold",  NEW.sold,  "is_active", NEW.is_active
                        )
                    );
                END IF;
            END
        ');

        DB::unprepared('
            CREATE TRIGGER trg_ticket_batches_delete
            AFTER DELETE ON ticket_batches FOR EACH ROW
            BEGIN
                INSERT INTO audit_logs
                    (tabela, operacao, registro_id, tenant_id, usuario_id, ip_address, dados_anteriores)
                VALUES (
                    "ticket_batches", "DELETE", OLD.id, OLD.tenant_id, @audit_user_id, @audit_ip,
                    JSON_OBJECT(
                        "name",     OLD.name,
                        "price",    OLD.price,
                        "quantity", OLD.quantity,
                        "sold",     OLD.sold
                    )
                );
            END
        ');

        // ══════════════════════════════════════════════════════════════════════
        // PRODUCTS — preço e estoque
        // ══════════════════════════════════════════════════════════════════════

        DB::unprepared('
            CREATE TRIGGER trg_products_insert
            AFTER INSERT ON products FOR EACH ROW
            BEGIN
                INSERT INTO audit_logs
                    (tabela, operacao, registro_id, tenant_id, usuario_id, ip_address, dados_novos)
                VALUES (
                    "products", "INSERT", NEW.id, NEW.tenant_id, @audit_user_id, @audit_ip,
                    JSON_OBJECT(
                        "name", NEW.name, "price", NEW.price,
                        "stock", NEW.stock, "active", NEW.active
                    )
                );
            END
        ');

        DB::unprepared('
            CREATE TRIGGER trg_products_update
            AFTER UPDATE ON products FOR EACH ROW
            BEGIN
                IF NOT (OLD.price  <=> NEW.price)
                OR NOT (OLD.stock  <=> NEW.stock)
                OR NOT (OLD.active <=> NEW.active)
                OR NOT (OLD.name   <=> NEW.name)
                THEN
                    INSERT INTO audit_logs
                        (tabela, operacao, registro_id, tenant_id, usuario_id, ip_address,
                         dados_anteriores, dados_novos)
                    VALUES (
                        "products", "UPDATE", NEW.id, NEW.tenant_id, @audit_user_id, @audit_ip,
                        JSON_OBJECT(
                            "name", OLD.name, "price", OLD.price,
                            "stock", OLD.stock, "active", OLD.active
                        ),
                        JSON_OBJECT(
                            "name", NEW.name, "price", NEW.price,
                            "stock", NEW.stock, "active", NEW.active
                        )
                    );
                END IF;
            END
        ');

        DB::unprepared('
            CREATE TRIGGER trg_products_delete
            AFTER DELETE ON products FOR EACH ROW
            BEGIN
                INSERT INTO audit_logs
                    (tabela, operacao, registro_id, tenant_id, usuario_id, ip_address, dados_anteriores)
                VALUES (
                    "products", "DELETE", OLD.id, OLD.tenant_id, @audit_user_id, @audit_ip,
                    JSON_OBJECT(
                        "name", OLD.name, "price", OLD.price, "stock", OLD.stock
                    )
                );
            END
        ');

        // ══════════════════════════════════════════════════════════════════════
        // COMMISSIONERS
        // ══════════════════════════════════════════════════════════════════════

        DB::unprepared('
            CREATE TRIGGER trg_commissioners_insert
            AFTER INSERT ON commissioners FOR EACH ROW
            BEGIN
                INSERT INTO audit_logs
                    (tabela, operacao, registro_id, tenant_id, usuario_id, ip_address, dados_novos)
                VALUES (
                    "commissioners", "INSERT", NEW.id, NEW.tenant_id, @audit_user_id, @audit_ip,
                    JSON_OBJECT("user_id", NEW.user_id, "is_active", NEW.is_active)
                );
            END
        ');

        DB::unprepared('
            CREATE TRIGGER trg_commissioners_update
            AFTER UPDATE ON commissioners FOR EACH ROW
            BEGIN
                IF NOT (OLD.is_active <=> NEW.is_active) THEN
                    INSERT INTO audit_logs
                        (tabela, operacao, registro_id, tenant_id, usuario_id, ip_address,
                         dados_anteriores, dados_novos)
                    VALUES (
                        "commissioners", "UPDATE", NEW.id, NEW.tenant_id, @audit_user_id, @audit_ip,
                        JSON_OBJECT("is_active", OLD.is_active),
                        JSON_OBJECT("is_active", NEW.is_active)
                    );
                END IF;
            END
        ');

        DB::unprepared('
            CREATE TRIGGER trg_commissioners_delete
            AFTER DELETE ON commissioners FOR EACH ROW
            BEGIN
                INSERT INTO audit_logs
                    (tabela, operacao, registro_id, tenant_id, usuario_id, ip_address, dados_anteriores)
                VALUES (
                    "commissioners", "DELETE", OLD.id, OLD.tenant_id, @audit_user_id, @audit_ip,
                    JSON_OBJECT("user_id", OLD.user_id, "is_active", OLD.is_active)
                );
            END
        ');

        // ══════════════════════════════════════════════════════════════════════
        // EVENTS
        // ══════════════════════════════════════════════════════════════════════

        DB::unprepared('
            CREATE TRIGGER trg_events_insert
            AFTER INSERT ON events FOR EACH ROW
            BEGIN
                INSERT INTO audit_logs
                    (tabela, operacao, registro_id, tenant_id, usuario_id, ip_address, dados_novos)
                VALUES (
                    "events", "INSERT", NEW.id, NEW.tenant_id, @audit_user_id, @audit_ip,
                    JSON_OBJECT(
                        "name", NEW.name, "status", NEW.status,
                        "starts_at", NEW.starts_at
                    )
                );
            END
        ');

        DB::unprepared('
            CREATE TRIGGER trg_events_update
            AFTER UPDATE ON events FOR EACH ROW
            BEGIN
                IF NOT (OLD.status    <=> NEW.status)
                OR NOT (OLD.name      <=> NEW.name)
                OR NOT (OLD.starts_at <=> NEW.starts_at)
                THEN
                    INSERT INTO audit_logs
                        (tabela, operacao, registro_id, tenant_id, usuario_id, ip_address,
                         dados_anteriores, dados_novos)
                    VALUES (
                        "events", "UPDATE", NEW.id, NEW.tenant_id, @audit_user_id, @audit_ip,
                        JSON_OBJECT("name", OLD.name, "status", OLD.status, "starts_at", OLD.starts_at),
                        JSON_OBJECT("name", NEW.name, "status", NEW.status, "starts_at", NEW.starts_at)
                    );
                END IF;
            END
        ');

        DB::unprepared('
            CREATE TRIGGER trg_events_delete
            AFTER DELETE ON events FOR EACH ROW
            BEGIN
                INSERT INTO audit_logs
                    (tabela, operacao, registro_id, tenant_id, usuario_id, ip_address, dados_anteriores)
                VALUES (
                    "events", "DELETE", OLD.id, OLD.tenant_id, @audit_user_id, @audit_ip,
                    JSON_OBJECT("name", OLD.name, "status", OLD.status)
                );
            END
        ');

        // ══════════════════════════════════════════════════════════════════════
        // UNIVERSITIES
        // ══════════════════════════════════════════════════════════════════════

        DB::unprepared('
            CREATE TRIGGER trg_universities_insert
            AFTER INSERT ON universities FOR EACH ROW
            BEGIN
                INSERT INTO audit_logs
                    (tabela, operacao, registro_id, usuario_id, ip_address, dados_novos)
                VALUES (
                    "universities", "INSERT", NEW.id, @audit_user_id, @audit_ip,
                    JSON_OBJECT("name", NEW.name, "acronym", NEW.acronym, "is_active", NEW.is_active)
                );
            END
        ');

        DB::unprepared('
            CREATE TRIGGER trg_universities_update
            AFTER UPDATE ON universities FOR EACH ROW
            BEGIN
                IF NOT (OLD.name      <=> NEW.name)
                OR NOT (OLD.is_active <=> NEW.is_active)
                THEN
                    INSERT INTO audit_logs
                        (tabela, operacao, registro_id, usuario_id, ip_address,
                         dados_anteriores, dados_novos)
                    VALUES (
                        "universities", "UPDATE", NEW.id, @audit_user_id, @audit_ip,
                        JSON_OBJECT("name", OLD.name, "is_active", OLD.is_active),
                        JSON_OBJECT("name", NEW.name, "is_active", NEW.is_active)
                    );
                END IF;
            END
        ');

        DB::unprepared('
            CREATE TRIGGER trg_universities_delete
            AFTER DELETE ON universities FOR EACH ROW
            BEGIN
                INSERT INTO audit_logs
                    (tabela, operacao, registro_id, usuario_id, ip_address, dados_anteriores)
                VALUES (
                    "universities", "DELETE", OLD.id, @audit_user_id, @audit_ip,
                    JSON_OBJECT("name", OLD.name, "acronym", OLD.acronym)
                );
            END
        ');

        // ══════════════════════════════════════════════════════════════════════
        // PERSONAL_ACCESS_TOKENS — login e logout (segurança)
        // ══════════════════════════════════════════════════════════════════════

        DB::unprepared('
            CREATE TRIGGER trg_personal_access_tokens_insert
            AFTER INSERT ON personal_access_tokens FOR EACH ROW
            BEGIN
                INSERT INTO audit_logs
                    (tabela, operacao, registro_id, usuario_id, ip_address, dados_novos)
                VALUES (
                    "personal_access_tokens", "INSERT", NEW.id, @audit_user_id, @audit_ip,
                    JSON_OBJECT(
                        "tokenable_type", NEW.tokenable_type,
                        "tokenable_id",   NEW.tokenable_id,
                        "name",           NEW.name
                    )
                );
            END
        ');

        DB::unprepared('
            CREATE TRIGGER trg_personal_access_tokens_delete
            AFTER DELETE ON personal_access_tokens FOR EACH ROW
            BEGIN
                INSERT INTO audit_logs
                    (tabela, operacao, registro_id, usuario_id, ip_address, dados_anteriores)
                VALUES (
                    "personal_access_tokens", "DELETE", OLD.id, @audit_user_id, @audit_ip,
                    JSON_OBJECT(
                        "tokenable_type", OLD.tokenable_type,
                        "tokenable_id",   OLD.tokenable_id,
                        "name",           OLD.name,
                        "last_used_at",   OLD.last_used_at
                    )
                );
            END
        ');
    }

    // ── DROP de todos os triggers ──────────────────────────────────────────────
    public function down(): void
    {
        $triggers = [
            'trg_orders_insert',                    'trg_orders_update',                    'trg_orders_delete',
            'trg_tickets_insert',                   'trg_tickets_update',                   'trg_tickets_delete',
            'trg_users_insert',                     'trg_users_update',                     'trg_users_delete',
            'trg_tenants_insert',                   'trg_tenants_update',                   'trg_tenants_delete',
            'trg_ticket_batches_insert',            'trg_ticket_batches_update',            'trg_ticket_batches_delete',
            'trg_products_insert',                  'trg_products_update',                  'trg_products_delete',
            'trg_commissioners_insert',             'trg_commissioners_update',             'trg_commissioners_delete',
            'trg_events_insert',                    'trg_events_update',                    'trg_events_delete',
            'trg_universities_insert',              'trg_universities_update',              'trg_universities_delete',
            'trg_personal_access_tokens_insert',    'trg_personal_access_tokens_delete',
        ];

        foreach ($triggers as $trigger) {
            DB::unprepared("DROP TRIGGER IF EXISTS `{$trigger}`");
        }
    }
};