import { createRouter, createWebHistory } from 'vue-router';
import AppLayout    from '@/layout/AppLayout.vue';
import PublicLayout from '@/layout/PublicLayout.vue';
import StoreLayout  from '@/layout/StoreLayout.vue';
import { useAuth }  from '@/composables/useAuth';

const routes = [

    // ── Catálogo público (raiz) ─────────────────────────────────────────────
    {
        path: '/',
        component: PublicLayout,
        children: [
            { path: '', name: 'catalogo', component: () => import('@/views/Catalogo.vue') },
        ],
    },

    // ── Loja pública por atlética (/c/:slug) ────────────────────────────────
    {
        path: '/c/:slug',
        component: StoreLayout,
        children: [
            { path: '',             name: 'store-home',        component: () => import('@/views/store/Eventos.vue') },
            { path: 'produtos',     name: 'store-produtos',    component: () => import('@/views/store/Produtos.vue') },
            { path: 'eventos/:id',  name: 'store-evento',      component: () => import('@/views/store/EventoDetalhe.vue') },
            { path: 'carrinho',     name: 'store-carrinho',    component: () => import('@/views/store/Carrinho.vue') },
            { path: 'checkout',     name: 'store-checkout',    meta: { requiresAuth: true }, component: () => import('@/views/store/Checkout.vue') },
            { path: 'pedido/:id',   name: 'store-pedido',      component: () => import('@/views/store/PedidoConfirmado.vue') },
            { path: 'meus-ingressos', name: 'store-meus-ingressos', meta: { requiresAuth: true },
              component: () => import('@/views/store/MeusIngressos.vue') },
            { path: 'meus-pedidos',   name: 'store-meus-pedidos',   meta: { requiresAuth: true },
              component: () => import('@/views/store/MeusPedidos.vue') },
            { path: 'meu-perfil',     name: 'store-meu-perfil',     meta: { requiresAuth: true },
              component: () => import('@/views/store/MeuPerfil.vue') },
            { path: 'comissario',     name: 'store-comissario',     meta: { requiresAuth: true },
              component: () => import('@/views/store/ComissarioVendas.vue') },
        ],
    },

    // ── Super Admin ─────────────────────────────────────────────────────────
    {
        path: '/super-admin',
        component: AppLayout,
        meta: { requiresAuth: true, requiresSuperAdmin: true },
        children: [
            { path: '',               name: 'super-dashboard',      component: () => import('@/views/superadmin/SuperAdminDashboard.vue'),
              meta: { breadcrumb: [{ parent: 'Super Admin', label: 'Dashboard' }] } },
            { path: 'universidades',  name: 'super-universidades',  component: () => import('@/views/superadmin/SuperAdminUniversidades.vue'),
              meta: { breadcrumb: [{ parent: 'Super Admin', label: 'Universidades' }] } },
            { path: 'atleticas',      name: 'super-atleticas',      component: () => import('@/views/superadmin/SuperAdminAtleticas.vue'),
              meta: { breadcrumb: [{ parent: 'Super Admin', label: 'Atléticas' }] } },
        ],
    },

    // ── Admin da atlética (/admin/*) ────────────────────────────────────────
    {
        path: '/admin',
        component: AppLayout,
        meta: { requiresAuth: true, requiresAdmin: true },
        children: [
            { path: '',             name: 'dashboard',        component: () => import('@/views/admin/AdminDashboard.vue'),
              meta: { breadcrumb: [{ parent: 'Gestão', label: 'Dashboard' }] } },
            { path: 'eventos',      name: 'admin-eventos',    component: () => import('@/views/admin/AdminEventos.vue'),
              meta: { breadcrumb: [{ parent: 'Gestão', label: 'Eventos' }] } },
            { path: 'eventos/novo', name: 'admin-evento-novo', component: () => import('@/views/admin/AdminEventoForm.vue'),
              meta: { breadcrumb: [{ parent: 'Eventos', label: 'Novo Evento' }] } },
            { path: 'eventos/:id',  name: 'admin-evento-edit', component: () => import('@/views/admin/AdminEventoForm.vue'),
              meta: { breadcrumb: [{ parent: 'Eventos', label: 'Editar' }] } },
            { path: 'produtos',     name: 'admin-produtos',    component: () => import('@/views/admin/AdminProdutos.vue'),
              meta: { breadcrumb: [{ parent: 'Gestão', label: 'Produtos' }] } },
            { path: 'pedidos',      name: 'admin-pedidos',     component: () => import('@/views/admin/AdminPedidos.vue'),
              meta: { breadcrumb: [{ parent: 'Gestão', label: 'Pedidos' }] } },
            { path: 'comissarios',  name: 'admin-comissarios',  component: () => import('@/views/admin/AdminComissarios.vue'),
              meta: { breadcrumb: [{ parent: 'Gestão', label: 'Comissários' }] } },
            { path: 'validar',      name: 'admin-validar',      component: () => import('@/views/admin/AdminValidarIngresso.vue'),
              meta: { breadcrumb: [{ parent: 'Gestão', label: 'Validar Ingressos' }] } },
            { path: 'perfil',       name: 'admin-perfil',       component: () => import('@/views/admin/AdminPerfil.vue'),
              meta: { breadcrumb: [{ parent: 'Gestão', label: 'Perfil da Atlética' }] } },
            { path: 'relatorios',   name: 'admin-relatorios',  component: () => import('@/views/admin/AdminRelatorios.vue'),
              meta: { breadcrumb: [{ parent: 'Gestão', label: 'Relatórios' }] } },
        ],
    },

    // ── Auth ────────────────────────────────────────────────────────────────
    { path: '/login',    name: 'login',    component: () => import('@/views/auth/Login.vue') },
    { path: '/register', name: 'register', component: () => import('@/views/auth/Register.vue') },
    { path: '/access',   name: 'access',   component: () => import('@/views/pages/Access.vue') },
    { path: '/:pathMatch(.*)*', redirect: '/' },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
    scrollBehavior: () => ({ left: 0, top: 0 }),
});

router.beforeEach(async (to) => {
    if (!to.meta.requiresAuth) return true;

    const auth = useAuth();
    if (!auth.isAuthenticated.value) await auth.checkAuth();
    if (!auth.isAuthenticated.value) return { name: 'login', query: { redirect: to.fullPath } };
    if (to.meta.requiresSuperAdmin && !auth.isSuperAdmin.value) return { name: 'access' };
    if (to.meta.requiresAdmin      && !auth.isAdmin.value)      return { name: 'access' };

    return true;
});

export default router;
