<template>
    <div class="store-layout">
        <!-- Header -->
        <header class="store-header">
            <div class="store-header-inner">
                <router-link :to="`/c/${slug}`" class="store-brand">
                    <i class="pi pi-bolt"></i>
                    <span>{{ atleticaName }}</span>
                </router-link>

                <div class="store-header-actions">
                    <router-link :to="`/c/${slug}/produtos`" class="store-nav-link">
                        <i class="pi pi-tag mr-1"></i>Produtos
                    </router-link>
                    <template v-if="isAuthenticated">
                        <router-link v-if="isAdmin" to="/admin" class="store-nav-link admin-link">
                            <i class="pi pi-shield mr-1"></i>Painel Admin
                        </router-link>
                        <router-link v-if="isCommissioner" :to="`/c/${slug}/comissario`"
                            class="store-nav-link commissioner-link">
                            <i class="pi pi-shopping-bag mr-1"></i>Vender
                        </router-link>
                        <router-link :to="`/c/${slug}/meus-ingressos`" class="store-nav-link">
                            <i class="pi pi-ticket mr-1"></i>Ingressos
                        </router-link>
                        <router-link :to="`/c/${slug}/meus-pedidos`" class="store-nav-link">
                            <i class="pi pi-list mr-1"></i>Pedidos
                        </router-link>
                        <router-link :to="`/c/${slug}/meu-perfil`" class="store-nav-link">
                            <i class="pi pi-user-edit mr-1"></i>Perfil
                        </router-link>
                    </template>
                    <router-link v-else :to="{ name: 'login' }" class="store-nav-link">
                        <i class="pi pi-user mr-1"></i>Entrar
                    </router-link>
                    <router-link :to="`/c/${slug}/carrinho`" class="cart-btn">
                        <i class="pi pi-shopping-cart"></i>
                        <Badge v-if="count > 0" :value="count" severity="danger" />
                        <span v-if="count > 0" class="cart-total">R$ {{ total.toFixed(2) }}</span>
                    </router-link>
                </div>
            </div>
        </header>

        <main class="store-main">
            <div v-if="tenantInvalido" class="flex flex-column align-items-center justify-content-center py-8 gap-3">
                <i class="pi pi-exclamation-circle text-6xl" style="color:#ef5350"></i>
                <h2 style="color:#ef5350">Atlética não encontrada</h2>
                <p class="text-color-secondary">O link que você acessou é inválido ou esta atlética está inativa.</p>
            </div>
            <router-view v-else-if="!carregando" />
            <div v-else class="flex justify-content-center py-8"><ProgressSpinner /></div>
        </main>

        <footer class="store-footer">
            <p>© {{ new Date().getFullYear() }} {{ atleticaName }} — Powered by Lojin</p>
        </footer>
        <Toast />
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import api from '@/service/ApiService';
import { useAuth } from '@/composables/useAuth';
import { useCart } from '@/composables/useCart';

const route  = useRoute();
const { isAuthenticated, isAdmin } = useAuth();
const { count, total } = useCart();

const slug           = computed(() => route.params.slug);
const atleticaName   = ref('Atlética');
const carregando     = ref(false);
const tenantInvalido = ref(false);
const isCommissioner = ref(false);

onMounted(async () => {
    if (!slug.value) { tenantInvalido.value = true; return; }
    carregando.value = true;
    try {
        const { data } = await api.get(`/atletica/${slug.value}`);
        atleticaName.value = data.name;
    } catch { tenantInvalido.value = true; }
    finally { carregando.value = false; }

    if (isAuthenticated.value) {
        try {
            const { data } = await api.get('/commissioner/status');
            isCommissioner.value = data.is_commissioner;
        } catch {}
    }
});
</script>

<style scoped>
.store-layout { min-height:100vh; display:flex; flex-direction:column; background:#f5f5f5; }
.store-header { background:#1976d2; color:#fff; padding:0 1.5rem; position:sticky; top:0; z-index:100; box-shadow:0 2px 8px rgba(0,0,0,.15); }
.store-header-inner { max-width:1200px; margin:0 auto; height:64px; display:flex; align-items:center; justify-content:space-between; }
.store-brand { display:flex; align-items:center; gap:.5rem; color:#fff; text-decoration:none; font-size:1.3rem; font-weight:700; }
.store-header-actions { display:flex; align-items:center; gap:1rem; }
.store-nav-link { color:rgba(255,255,255,.9); text-decoration:none; font-size:.9rem; transition:color .2s; }
.store-nav-link:hover { color:#fff; }
.commissioner-link { background:rgba(255,255,255,.2); padding:.3rem .8rem; border-radius:16px; font-weight:600; }
.admin-link { background:rgba(255,255,255,.15); padding:.3rem .8rem; border-radius:16px; font-weight:600; border:1px solid rgba(255,255,255,.4); }
.cart-btn { display:flex; align-items:center; gap:.4rem; color:#fff; text-decoration:none; background:rgba(255,255,255,.15); padding:.4rem .9rem; border-radius:20px; transition:background .2s; }
.cart-btn:hover { background:rgba(255,255,255,.25); }
.cart-total { font-weight:600; font-size:.9rem; }
.store-main { flex:1; max-width:1200px; margin:0 auto; width:100%; padding:1.5rem; }
.store-footer { text-align:center; padding:1rem; font-size:.85rem; color:#888; }
</style>
