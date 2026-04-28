import { computed, ref } from 'vue';
import api from '@/service/ApiService';

const user  = ref(JSON.parse(localStorage.getItem('auth_user') || 'null'));
const token = ref(localStorage.getItem('auth_token') || null);

export function useAuth() {
    const isAuthenticated = computed(() => !!token.value);
    const isAdmin         = computed(() => ['admin','super_admin'].includes(user.value?.role));
    const isSuperAdmin    = computed(() => user.value?.role === 'super_admin');

    function syncTenantSlug(userData) {
        const slug = userData?.tenant?.slug;
        if (slug) localStorage.setItem('idealfood_tenant_slug', slug);
        else      localStorage.removeItem('idealfood_tenant_slug');
    }

    async function login(email, password) {
        const { data } = await api.post('/auth/login', { email, password });
        token.value = data.token;
        user.value  = data.user;
        localStorage.setItem('auth_token', data.token);
        localStorage.setItem('auth_user', JSON.stringify(data.user));
        syncTenantSlug(data.user);
    }

    async function register(name, email, password) {
        const { data } = await api.post('/auth/register', { name, email, password });
        token.value = data.token;
        user.value  = data.user;
        localStorage.setItem('auth_token', data.token);
        localStorage.setItem('auth_user', JSON.stringify(data.user));
    }

    async function logout() {
        try { await api.post('/auth/logout'); } catch {}
        token.value = null; user.value = null;
        localStorage.removeItem('auth_token');
        localStorage.removeItem('auth_user');
        localStorage.removeItem('idealfood_tenant_slug');
    }

    async function checkAuth() {
        if (!token.value) return;
        try {
            const { data } = await api.get('/auth/me');
            user.value = data;
            localStorage.setItem('auth_user', JSON.stringify(data));
            syncTenantSlug(data);
        } catch {
            token.value = null; user.value = null;
            localStorage.removeItem('auth_token');
            localStorage.removeItem('auth_user');
        }
    }

    return { user, token, isAuthenticated, isAdmin, isSuperAdmin, login, register, logout, checkAuth };
}
