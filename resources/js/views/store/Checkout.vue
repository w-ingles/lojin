<template>
    <div>
        <div class="flex align-items-center gap-2 mb-4">
            <Button icon="pi pi-arrow-left" class="p-button-text" @click="$router.back()" />
            <h3 class="m-0">Finalizar Compra</h3>
        </div>

        <!-- Não autenticado -->
        <div v-if="!isAuthenticated" class="card text-center py-5">
            <i class="pi pi-lock text-5xl mb-3 block" style="color:#1976d2"></i>
            <h4 class="mb-2">Login necessário</h4>
            <p class="text-color-secondary mb-4">Para comprar, você precisa estar logado.</p>
            <Button label="Entrar agora" icon="pi pi-sign-in" class="p-button-success"
                @click="router.push({ name: 'login', query: { redirect: route.fullPath } })" />
        </div>

        <!-- Perfil incompleto -->
        <div v-else-if="perfilIncompleto" class="card py-4">
            <div class="flex align-items-start gap-3 mb-4 p-3 border-round"
                style="background:#fff8e1;border:1px solid #ffe082">
                <i class="pi pi-exclamation-triangle mt-1" style="color:#f57f17;font-size:1.3rem"></i>
                <div>
                    <div class="font-semibold mb-1" style="color:#6d4c00">Cadastro incompleto</div>
                    <div class="text-sm" style="color:#795548">
                        Complete estes dados antes de comprar:
                        <strong>{{ camposFaltando.join(', ') }}</strong>.
                    </div>
                </div>
            </div>
            <Button label="Completar meus dados" icon="pi pi-user-edit" class="p-button-warning"
                @click="router.push(`/c/${route.params.slug}/meu-perfil`)" />
        </div>

        <!-- Checkout normal -->
        <div v-else class="grid">
            <div class="col-12 lg:col-7">
                <div class="card">
                    <div class="flex align-items-center justify-content-between mb-3">
                        <h5 class="m-0">
                            <i class="pi pi-user mr-2" style="color:#1976d2"></i>Seus dados
                        </h5>
                        <Button label="Editar perfil" icon="pi pi-pencil" class="p-button-text p-button-sm"
                            @click="router.push(`/c/${route.params.slug}/meu-perfil`)" />
                    </div>

                    <div class="p-3 border-round" style="background:var(--surface-ground)">
                        <div class="grid">
                            <div class="col-12">
                                <div class="text-xs text-color-secondary mb-1 font-semibold">NOME</div>
                                <div class="font-semibold">{{ user?.name }}</div>
                            </div>
                            <div class="col-12 md:col-6 mt-2">
                                <div class="text-xs text-color-secondary mb-1 font-semibold">E-MAIL</div>
                                <div>{{ user?.email }}</div>
                            </div>
                            <div class="col-12 md:col-6 mt-2">
                                <div class="text-xs text-color-secondary mb-1 font-semibold">TELEFONE</div>
                                <div>{{ user?.phone ?? '—' }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="field mt-3 mb-0">
                        <label>Observações <span class="text-color-secondary text-sm">(opcional)</span></label>
                        <Textarea v-model="notes" rows="2" autoResize placeholder="Alguma observação..." />
                    </div>
                </div>
            </div>

            <div class="col-12 lg:col-5">
                <div class="card" style="position:sticky;top:80px">
                    <h5 class="mt-0">Resumo do Pedido</h5>
                    <div v-for="item in items" :key="`${item.type}-${item.id}`"
                        class="flex justify-content-between mb-2 text-sm">
                        <span>{{ item.qty }}× {{ item.name }}</span>
                        <span class="font-semibold">R$ {{ (item.price * item.qty).toFixed(2) }}</span>
                    </div>
                    <Divider />
                    <div class="flex justify-content-between font-bold text-lg mb-4">
                        <span>Total</span>
                        <span style="color:#2e7d32">R$ {{ total.toFixed(2) }}</span>
                    </div>
                    <Button label="Confirmar Pedido" icon="pi pi-check" class="p-button-success w-full"
                        :loading="enviando" :disabled="items.length === 0" @click="confirmar" />
                </div>
            </div>
        </div>
        <Toast />
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import api from '@/service/ApiService';
import { useCart } from '@/composables/useCart';
import { useAuth } from '@/composables/useAuth';

const route  = useRoute();
const router = useRouter();
const toast  = useToast();

const { items, total, clear } = useCart();
const { user, isAuthenticated } = useAuth();

const enviando        = ref(false);
const perfilIncompleto = ref(false);
const camposFaltando  = ref([]);
const notes           = ref('');

async function verificarPerfil() {
    if (!isAuthenticated.value) return;
    try {
        const { data } = await api.get('/user/profile');
        if (!data.is_complete) {
            perfilIncompleto.value = true;
            camposFaltando.value   = data.campos_faltando;
        }
    } catch {}
}

async function confirmar() {
    if (items.value.length === 0) return;
    enviando.value = true;
    try {
        const { data: order } = await api.post('/orders', {
            items: items.value.map(i => ({ type: i.type, id: i.id, qty: i.qty })),
            notes: notes.value || undefined,
        });

        const { data: payment } = await api.post(`/payments/${order.id}/preference`);
        clear();
        window.location.href = payment.checkout_url;
    } catch (err) {
        const code = err.response?.data?.code;
        if (code === 'PROFILE_INCOMPLETE') {
            perfilIncompleto.value = true;
            camposFaltando.value   = err.response.data.campos_faltando ?? [];
            return;
        }
        toast.add({
            severity: 'error',
            summary: 'Erro ao realizar pedido',
            detail: err.response?.data?.message ?? 'Tente novamente.',
            life: 4000,
        });
    } finally { enviando.value = false; }
}

onMounted(() => verificarPerfil());
</script>