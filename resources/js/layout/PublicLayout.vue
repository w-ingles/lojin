<template>
    <div class="public-layout">
        <header class="public-header">
            <div class="public-header-inner">
                <router-link to="/" class="public-brand">
                    <i class="pi pi-bolt"></i>
                    <span>Lojin</span>
                </router-link>

                <div class="public-header-actions">
                    <template v-if="isAuthenticated">
                        <router-link v-if="isAdmin" :to="isSuperAdmin ? '/super-admin' : '/admin'" class="header-link">
                            <i class="pi pi-th-large mr-1"></i>Painel
                        </router-link>

                        <router-link to="/ingressos" class="header-link">
                            <i class="pi pi-ticket mr-1"></i>Ingressos
                        </router-link>

                        <router-link to="/perfil" class="user-info">
                            <Avatar :label="iniciais" shape="circle" class="user-avatar" />
                            <span class="user-name">{{ primeiroNome }}</span>
                        </router-link>

                        <button class="header-link header-btn" @click="sair">
                            <i class="pi pi-sign-out mr-1"></i>Sair
                        </button>
                    </template>
                    <template v-else>
                        <router-link to="/login" class="header-link">
                            <i class="pi pi-user mr-1"></i>Entrar
                        </router-link>
                        <router-link to="/register" class="header-btn-primary">
                            Cadastrar
                        </router-link>
                    </template>
                </div>
            </div>
        </header>

        <main class="public-main">
            <router-view />
        </main>

        <footer class="public-footer">
            <p>© {{ new Date().getFullYear() }} Lojin — Plataforma de atléticas universitárias</p>
        </footer>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAuth } from '@/composables/useAuth';

const router = useRouter();
const { isAuthenticated, isAdmin, isSuperAdmin, logout, user } = useAuth();

const primeiroNome = computed(() => user.value?.name?.split(' ')[0] ?? '');

const iniciais = computed(() => {
    const partes = (user.value?.name ?? '').trim().split(' ').filter(Boolean);
    if (partes.length === 0) return '?';
    if (partes.length === 1) return partes[0][0].toUpperCase();
    return (partes[0][0] + partes[partes.length - 1][0]).toUpperCase();
});

async function sair() {
    await logout();
    router.push('/login');
}
</script>

<style scoped>
.public-layout {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    background: #f5f5f5;
}
.public-header {
    background: #1976d2;
    color: #fff;
    padding: 0 1.5rem;
    position: sticky;
    top: 0;
    z-index: 100;
    box-shadow: 0 2px 8px rgba(0,0,0,.18);
}
.public-header-inner {
    max-width: 1280px;
    margin: 0 auto;
    height: 64px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.public-brand {
    display: flex;
    align-items: center;
    gap: .5rem;
    color: #fff;
    text-decoration: none;
    font-size: 1.4rem;
    font-weight: 800;
    letter-spacing: -.5px;
}
.public-header-actions {
    display: flex;
    align-items: center;
    gap: 1rem;
}
.header-link {
    color: rgba(255,255,255,.9);
    text-decoration: none;
    font-size: .9rem;
    background: none;
    border: none;
    cursor: pointer;
    padding: 0;
    font-family: inherit;
    transition: color .2s;
}
.header-link:hover { color: #fff; }
.header-btn {
    color: rgba(255,255,255,.9);
    font-size: .9rem;
}
.header-btn-primary {
    background: rgba(255,255,255,.15);
    color: #fff;
    text-decoration: none;
    padding: .45rem 1.1rem;
    border-radius: 20px;
    font-size: .9rem;
    font-weight: 600;
    transition: background .2s;
}
.header-btn-primary:hover { background: rgba(255,255,255,.28); }
.user-info {
    display: flex;
    align-items: center;
    gap: .5rem;
    text-decoration: none;
    transition: opacity .2s;
}
.user-info:hover { opacity: .85; }
.user-avatar {
    width: 32px !important;
    height: 32px !important;
    font-size: .8rem !important;
    font-weight: 700;
    background: rgba(255,255,255,.9) !important;
    color: #1976d2 !important;
}
.user-name {
    color: #fff;
    font-size: .9rem;
    font-weight: 600;
    max-width: 120px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.public-main {
    flex: 1;
    max-width: 1280px;
    margin: 0 auto;
    width: 100%;
    padding: 2rem 1.5rem;
}
.public-footer {
    text-align: center;
    padding: 1.2rem;
    font-size: .85rem;
    color: #999;
    border-top: 1px solid #e0e0e0;
    background: #fff;
    margin-top: auto;
}
</style>
