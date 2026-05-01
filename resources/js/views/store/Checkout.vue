<template>
    <div>
        <div class="flex align-items-center gap-2 mb-4">
            <Button v-if="passo === 1" icon="pi pi-arrow-left" class="p-button-text" @click="$router.back()" />
            <h3 class="m-0">{{ passo === 1 ? 'Finalizar Compra' : 'Pagamento' }}</h3>
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
        <div v-else-if="perfilIncompleto && passo === 1" class="card py-4">
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

        <!-- Passo 1: Resumo -->
        <div v-else-if="passo === 1" class="grid">
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
                    <div v-for="item in items" :key="`${item.type}-${item.id}`" class="mb-2">
                        <div class="flex justify-content-between text-sm">
                            <span>{{ item.qty }}× {{ item.name }}</span>
                            <span class="font-semibold">R$ {{ (item.price * item.qty).toFixed(2) }}</span>
                        </div>
                        <div v-if="item.type === 'ticket_batch' && ticketLimits[item.id]">
                            <small v-if="item.qty > ticketLimits[item.id].remaining" style="color:#c62828">
                                Você já tem {{ ticketLimits[item.id].existing }} ingresso(s) para este evento — limite de 5 por CPF.
                            </small>
                            <small v-else-if="ticketLimits[item.id].existing > 0" class="text-color-secondary">
                                Você já tem {{ ticketLimits[item.id].existing }} ingresso(s) para este evento (máx. 5).
                            </small>
                        </div>
                    </div>
                    <Divider />
                    <div class="flex justify-content-between font-bold text-lg mb-4">
                        <span>Total</span>
                        <span style="color:#2e7d32">R$ {{ total.toFixed(2) }}</span>
                    </div>
                    <Button label="Ir para Pagamento" icon="pi pi-lock" class="p-button-success w-full"
                        :loading="enviando" :disabled="items.length === 0 || limiteExcedido" @click="confirmar" />
                </div>
            </div>
        </div>

        <!-- Passo 2: Brick de Pagamento -->
        <div v-else-if="passo === 2">
            <div class="card">
                <div v-if="erroMontagem" class="p-3 border-round mb-3"
                    style="background:#ffebee;border:1px solid #ef9a9a;color:#b71c1c">
                    <i class="pi pi-exclamation-circle mr-2"></i>{{ erroMontagem }}
                </div>
                <div v-if="brickCarregando && !erroMontagem" class="text-center py-5">
                    <i class="pi pi-spin pi-spinner text-4xl" style="color:#1976d2"></i>
                    <p class="mt-3 text-color-secondary">Carregando formulário de pagamento...</p>
                </div>
                <div id="mp-payment-brick"></div>
            </div>
        </div>

        <!-- Passo 3: Pagamento Pendente (PIX / Boleto) -->
        <div v-else-if="passo === 3" class="card text-center">
            <i class="pi pi-clock text-5xl mb-3 block" style="color:#f57f17"></i>
            <h4>Aguardando confirmação</h4>

            <!-- PIX -->
            <div v-if="pagamentoPendente?.pix_base64" class="mb-4">
                <p class="text-color-secondary mb-3">Escaneie o QR Code abaixo com seu app de banco:</p>
                <img :src="`data:image/png;base64,${pagamentoPendente.pix_base64}`"
                    alt="QR Code PIX" style="max-width:220px;margin:0 auto;display:block" />
                <div v-if="pagamentoPendente.pix_code" class="mt-3">
                    <p class="text-sm text-color-secondary mb-2">Ou copie o código PIX:</p>
                    <div class="flex gap-2 justify-content-center">
                        <InputText :value="pagamentoPendente.pix_code" readonly
                            style="font-size:.75rem;max-width:320px" />
                        <Button icon="pi pi-copy" class="p-button-outlined" @click="copiarPix" />
                    </div>
                </div>
            </div>

            <!-- Boleto -->
            <div v-else-if="pagamentoPendente?.boleto_url" class="mb-4">
                <p class="text-color-secondary mb-3">Seu boleto foi gerado:</p>
                <a :href="pagamentoPendente.boleto_url" target="_blank" rel="noopener">
                    <Button label="Abrir Boleto" icon="pi pi-external-link" class="p-button-outlined" />
                </a>
            </div>

            <p class="text-sm text-color-secondary mt-3">
                Após o pagamento, seu ingresso será gerado automaticamente e ficará disponível em
                <strong>Meus Ingressos</strong>.
            </p>
            <Button label="Ver meus pedidos" icon="pi pi-list" class="p-button-text mt-2"
                @click="router.push(`/c/${route.params.slug}/meus-pedidos`)" />
        </div>

        <Toast />
    </div>
</template>

<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref } from 'vue';
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

const passo            = ref(1);
const enviando         = ref(false);
const brickCarregando  = ref(false);
const perfilIncompleto = ref(false);
const camposFaltando   = ref([]);
const notes            = ref('');
const ticketLimits     = ref({});
const pagamentoPendente = ref(null);
const brickController  = ref(null);
const pedidoId         = ref(null);
const erroMontagem     = ref('');

const limiteExcedido = computed(() =>
    items.value.some(i => {
        if (i.type !== 'ticket_batch') return false;
        const l = ticketLimits.value[i.id];
        return l && i.qty > l.remaining;
    })
);

async function verificarLimites() {
    if (!isAuthenticated.value) return;
    const batchIds = items.value.filter(i => i.type === 'ticket_batch').map(i => i.id);
    if (batchIds.length === 0) return;
    try {
        const { data } = await api.get('/user/ticket-limits', { params: { batch_ids: batchIds } });
        ticketLimits.value = data;
    } catch {}
}

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
    if (items.value.length === 0 || limiteExcedido.value) return;
    enviando.value = true;
    try {
        const { data: order } = await api.post('/orders', {
            items: items.value.map(i => ({ type: i.type, id: i.id, qty: i.qty })),
            notes: notes.value || undefined,
        });
        pedidoId.value = order.id;
        passo.value    = 2;
        await nextTick();
        await montarBrick(order.id, parseFloat(order.total));
    } catch (err) {
        const code = err.response?.data?.code;
        if (code === 'PROFILE_INCOMPLETE') {
            perfilIncompleto.value = true;
            camposFaltando.value   = err.response.data.campos_faltando ?? [];
            return;
        }
        toast.add({
            severity: 'error',
            summary: 'Erro ao criar pedido',
            detail: err.response?.data?.message ?? 'Tente novamente.',
            life: 4000,
        });
    } finally {
        enviando.value = false;
    }
}

function carregarSdkMp() {
    return new Promise((resolve, reject) => {
        if (window.MercadoPago) { resolve(); return; }
        const script = document.createElement('script');
        script.src = 'https://sdk.mercadopago.com/js/v2';
        script.onload = resolve;
        script.onerror = () => reject(new Error('Falha ao carregar SDK do Mercado Pago.'));
        document.head.appendChild(script);
    });
}

async function montarBrick(orderId, amount) {
    brickCarregando.value = true;
    erroMontagem.value    = '';

    const publicKey = import.meta.env.VITE_MERCADOPAGO_PUBLIC_KEY;
    if (!publicKey) {
        brickCarregando.value = false;
        erroMontagem.value = 'Chave pública do Mercado Pago não configurada (VITE_MERCADOPAGO_PUBLIC_KEY).';
        return;
    }

    try {
        await carregarSdkMp();

        const mp = new window.MercadoPago(publicKey, { locale: 'pt-BR' });

        brickController.value = await mp.bricks().create('payment', 'mp-payment-brick', {
            initialization: {
                amount,
                payer: { email: user.value?.email },
            },
            customization: {
                paymentMethods: {
                    creditCard:   'all',
                    debitCard:    'all',
                    ticket:       'all',
                    bankTransfer: 'all',
                },
            },
            callbacks: {
                onReady: () => { brickCarregando.value = false; },
                onSubmit: ({ formData }) => new Promise(async (resolve, reject) => {
                    try {
                        const { data } = await api.post(`/payments/${orderId}/process`, formData);
                        if (data.status === 'approved') {
                            clear();
                            router.push({ name: 'store-pedido', params: { slug: route.params.slug, id: orderId } });
                        } else if (data.status === 'pending') {
                            pagamentoPendente.value = data.details;
                            passo.value = 3;
                        }
                        resolve();
                    } catch (err) {
                        reject(err.response?.data?.message ?? 'Erro ao processar pagamento.');
                    }
                }),
                onError: (err) => {
                    console.error('MP Brick error:', err);
                    brickCarregando.value = false;
                    erroMontagem.value = `Erro ao inicializar formulário de pagamento: ${err?.message ?? JSON.stringify(err)}`;
                },
            },
        });
    } catch (err) {
        brickCarregando.value = false;
        erroMontagem.value = err.message ?? 'Erro ao carregar formulário de pagamento.';
    }
}

async function copiarPix() {
    try {
        await navigator.clipboard.writeText(pagamentoPendente.value.pix_code);
        toast.add({ severity: 'success', summary: 'Copiado!', detail: 'Código PIX copiado.', life: 2000 });
    } catch {}
}

onMounted(() => { verificarPerfil(); verificarLimites(); });
onUnmounted(() => { brickController.value?.unmount(); });
</script>
