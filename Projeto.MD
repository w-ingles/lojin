Lojin — Plataforma Multi-Tenant de Ingressos e Loja

Stack Tecnológica

┌──────────┬──────────────────────────────────┐
│  Camada  │            Tecnologia            │
├──────────┼──────────────────────────────────┤
│ Backend  │ Laravel 13 + PHP 8.3             │
├──────────┼──────────────────────────────────┤
│ Frontend │ Vue 3 + Composition API          │
├──────────┼──────────────────────────────────┤
│ UI       │ PrimeVue 3 + PrimeFlex           │
├──────────┼──────────────────────────────────┤
│ Auth     │ Laravel Sanctum (token JWT-like) │
├──────────┼──────────────────────────────────┤
│ Banco    │ MySQL                            │
├──────────┼──────────────────────────────────┤
│ Build    │ Vite 5 + laravel-vite-plugin     │
└──────────┴──────────────────────────────────┘

---
Conceito Central

É uma plataforma multi-tenant para atléticas universitárias. Cada atlética tem seu próprio espaço ("slug") onde gerencia eventos, ingressos e loja de produtos. O isolamento entre tenants é feito via:
- tenant_id em todas as tabelas de dados
- Middleware IdentifyTenant que resolve o tenant pelo header X-Tenant-Slug ou pelo tenant_id do usuário logado
- Trait BelongsToTenant + TenantScope que filtram automaticamente todas as queries

---
Estrutura de Diretórios-Chave

lojin/
├── app/
│   ├── Http/Controllers/Api/
│   │   ├── Admin/        ← Gestão (eventos, produtos, pedidos, relatórios)
│   │   ├── Auth/         ← Login e cadastro
│   │   ├── Store/        ← Loja pública (cliente final)
│   │   └── SuperAdmin/   ← Gestão de tenants
│   ├── Models/           ← User, Tenant, Event, TicketBatch, Ticket, Product, Order, OrderItem
│   ├── Middleware/       ← IdentifyTenant, EnsureAdmin, EnsureSuperAdmin
│   └── Traits/           ← BelongsToTenant (isola queries por tenant)
│
├── routes/
│   ├── api.php           ← Todas as rotas JSON
│   └── web.php           ← Catch-all → app.blade.php (SPA)
│
├── database/migrations/  ← Schema: multi-tenancy, eventos/ingressos, produtos/pedidos
│
└── resources/js/
    ├── app.js            ← Bootstrap do Vue
    ├── router/index.js   ← Rotas + guards de auth
    ├── composables/
    │   ├── useAuth.js    ← Estado de auth (localStorage)
    │   └── useCart.js    ← Carrinho (localStorage)
    ├── service/          ← ApiService (Axios), ProductService, EventService...
    └── views/
        ├── auth/         ← Login, Register
        ├── store/        ← Eventos, Produtos, Carrinho, Checkout, MeusIngressos
        ├── admin/        ← Dashboard, Eventos, Produtos, Pedidos, Relatórios
        └── superadmin/   ← Dashboard, Gestão de Atléticas

---
Papéis de Usuário (3 níveis)

2. Vai ao checkout → Checkout.vue → POST /api/orders
3. ApiService.js injeta automaticamente Authorization: Bearer <token> + X-Tenant-Slug: <slug>
4. Laravel resolve o tenant via middleware → cria pedido com tenant_id correto
5. OrderItem é polimórfico: pode ser TicketBatch ou Product

---
Padrões Arquiteturais Notáveis

- SPA pura: Laravel serve apenas API; Vue Router controla toda a navegação
- Sem Pinia/Vuex: estado gerenciado por composables (useAuth, useCart)
- Persistência via localStorage: sessão e carrinho sobrevivem ao reload
- Multi-tenancy via header: frontend extrai o slug da URL (/c/:slug/...) e envia no header
- Items polimórficos: um único Order pode ter tanto ingressos quanto produtos