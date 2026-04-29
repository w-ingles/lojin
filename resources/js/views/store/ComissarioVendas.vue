<template>
    <div>
        <!-- Cabeçalho -->
        <div class="flex align-items-center gap-2 mb-4">
            <Button icon="pi pi-arrow-left" class="p-button-text" @click="$router.back()" />
            <div>
                <h3 class="m-0">Venda de Ingressos</h3>
                <small class="text-color-secondary">Modo Comissário</small>
            </div>
        </div>

        <!-- Não é comissário -->
        <div v-if="!isComissario && !verificando" class="card text-center py-5">
            <i class="pi pi-ban text-5xl mb-3 block" style="color:#ef5350"></i>
            <h4 style="color:#ef5350">Acesso restrito</h4>
            <p class="text-color-secondary">Você não é um comissário ativo desta atlética.</p>
        </div>

        <div v-else-if="!verificando" class="grid">
            <!-- ── Coluna principal ─────────────────────────────────────────── -->
            <div class="col-12 lg:col-8">

                <!-- PASSO 1: Buscar cliente por CPF -->
                <div class="card mb-3">
                    <h5 class="mt-0">
                        <i class="pi pi-search mr-2" style="color:#1976d2"></i>
                        Identificar cliente
                    </h5>

                    <div class="flex gap-2 p-fluid align-items-end">
                        <div class="flex-1 field mb-0">
                            <label>CPF do cliente</label>
                            <InputMask
                                v-model="cpfInput"
                                mask="999.999.999-99"
                                placeholder="000.000.000-00"
                                :class="{ 'p-invalid': erroCpf }"
                                :disabled="!!clienteEncontrado || buscando"
                                @keydown.enter="buscarCliente"
                            />
                            <small class="p-error">{{ erroCpf }}</small>
                        </div>
                        <Button
                            v-if="!clienteEncontrado"
                            label="Buscar"
                            icon="pi pi-search"
                            :loading="buscando"
                            @click="buscarCliente"
                        />
                        <Button
                            v-else
                            icon="pi pi-times"
                            class="p-button-outlined p-button-danger"
                            v-tooltip.top="'Trocar cliente'"
                            @click="limparCliente"
                        />
                    </div>

                    <!-- CPF não encontrado -->
                    <div v-if="cpfNaoEncontrado" class="flex gap-2 p-3 border-round mt-3"
                        style="background:#fff8e1;border:1px solid #ffe082">
                        <i class="pi pi-info-circle mt-1" style="color:#f57f17;flex-shrink:0"></i>
                        <span class="text-sm" style="color:#6d4c00">
                            Este CPF não está cadastrado na plataforma. Solicite que a pessoa realize o cadastro antes de continuar.
                        </span>
                    </div>

                    <!-- Cliente encontrado -->
                    <div v-if="clienteEncontrado" class="flex align-items-center gap-3 p-3 border-round mt-3"
                        style="background:#f0fdf4;border:1px solid #4ade80">
                        <i class="pi pi-user-edit text-3xl" style="color:#16a34a"></i>
                        <div>
                            <div class="font-bold text-lg">{{ clienteEncontrado.name }}</div>
                            <div class="text-sm text-color-secondary">{{ clienteEncontrado.email }}</div>
                            <div class="text-sm text-color-secondary">{{ clienteEncontrado.phone }}</div>
                        </div>
                    </div>
                </div>

                <!-- PASSO 2: Selecionar ingressos (aparece após identificar cliente) -->
                <template v-if="clienteEncontrado">
                    <!-- Eventos e Lotes -->
                    <div v-if="eventos.length > 0" class="card mb-3">
                        <h5 class="mt-0">
                            <i class="pi pi-calendar mr-2" style="color:#1976d2"></i>
                            Eventos disponíveis
                        </h5>

                        <div v-for="evento in eventos" :key="evento.id" class="mb-4">
                            <div class="font-semibold text-base mb-1">{{ evento.name }}</div>
                            <div class="text-sm text-color-secondary mb-2">
                                <i class="pi pi-calendar mr-1"></i>
                                {{ formatarData(evento.starts_at) }}
                                <span v-if="evento.location" class="ml-2">
                                    <i class="pi pi-map-marker mr-1"></i>{{ evento.location }}
                                </span>
                            </div>

                            <div v-for="lote in evento.ticket_batches" :key="lote.id"
                                class="flex align-items-center justify-content-between p-2 border-round mb-2"
                                :class="lote.available > 0 ? 'surface-ground' : 'surface-hover'"
                                style="border:1px solid var(--surface-border)">
                                <div>
                                    <span class="font-semibold">{{ lote.name }}</span>
                                    <span class="text-color-secondary text-sm ml-2">
                                        R$ {{ Number(lote.price).toFixed(2) }}
                                    </span>
                                    <Tag v-if="lote.available <= 0" value="Esgotado" severity="danger"
                                        class="ml-2 text-xs" />
                                    <span v-else class="text-xs text-color-secondary ml-2">
                                        ({{ lote.available }} disponíveis)
                                    </span>
                                </div>
                                <div class="flex align-items-center gap-2">
                                    <Button icon="pi pi-minus" class="p-button-outlined p-button-sm p-button-secondary"
                                        style="width:32px;height:32px"
                                        :disabled="!getQty('ticket_batch', lote.id)"
                                        @click="decrementar('ticket_batch', lote.id)" />
                                    <span class="font-bold" style="min-width:24px;text-align:center">
                                        {{ getQty('ticket_batch', lote.id) }}
                                    </span>
                                    <Button icon="pi pi-plus" class="p-button-sm"
                                        style="width:32px;height:32px"
                                        :disabled="lote.available <= 0 || getQty('ticket_batch', lote.id) >= lote.available"
                                        @click="incrementar('ticket_batch', lote.id, lote.name, lote.price, evento.name)" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Produtos -->
                    <div v-if="produtos.length > 0" class="card mb-3">
                        <h5 class="mt-0">
                            <i class="pi pi-tag mr-2" style="color:#1976d2"></i>
                            Produtos
                        </h5>
                        <div v-for="produto in produtos" :key="produto.id"
                            class="flex align-items-center justify-content-between p-2 border-round mb-2"
                            style="border:1px solid var(--surface-border);background:var(--surface-ground)">
                            <div>
                                <span class="font-semibold">{{ produto.name }}</span>
                                <span class="text-color-secondary text-sm ml-2">
                                    R$ {{ Number(produto.price).toFixed(2) }}
                                </span>
                                <Tag v-if="produto.stock <= 0" value="Sem estoque" severity="danger"
                                    class="ml-2 text-xs" />
                                <span v-else class="text-xs text-color-secondary ml-2">
                                    ({{ produto.stock }} em estoque)
                                </span>
                            </div>
                            <div class="flex align-items-center gap-2">
                                <Button icon="pi pi-minus" class="p-button-outlined p-button-sm p-button-secondary"
                                    style="width:32px;height:32px"
                                    :disabled="!getQty('product', produto.id)"
                                    @click="decrementar('product', produto.id)" />
                                <span class="font-bold" style="min-width:24px;text-align:center">
                                    {{ getQty('product', produto.id) }}
                                </span>
                                <Button icon="pi pi-plus" class="p-button-sm"
                                    style="width:32px;height:32px"
                                    :disabled="produto.stock <= 0 || getQty('product', produto.id) >= produto.stock"
                                    @click="incrementar('product', produto.id, produto.name, produto.price)" />
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- ── Coluna lateral: resumo e confirmação ─────────────────────── -->
            <div class="col-12 lg:col-4">
                <div class="card" style="position:sticky;top:80px">
                    <h5 class="mt-0">Resumo da Venda</h5>

                    <!-- Cliente -->
                    <div v-if="clienteEncontrado" class="mb-3 pb-3 border-bottom-1 surface-border">
                        <div class="text-xs text-color-secondary mb-1 font-semibold">CLIENTE</div>
                        <div class="font-semibold">{{ clienteEncontrado.name }}</div>
                        <div class="text-sm text-color-secondary">{{ clienteEncontrado.email }}</div>
                    </div>
                    <div v-else class="mb-3 text-center text-color-secondary py-2">
                        <i class="pi pi-user mb-2 block text-3xl"></i>
                        Identifique o cliente pelo CPF
                    </div>

                    <!-- Itens selecionados -->
                    <div v-if="carrinho.length > 0">
                        <div v-for="item in carrinho" :key="`${item.type}-${item.id}`"
                            class="flex justify-content-between mb-2 text-sm">
                            <span>{{ item.qty }}× {{ item.label }}</span>
                            <span class="font-semibold">R$ {{ (item.price * item.qty).toFixed(2) }}</span>
                        </div>
                        <Divider />
                        <div class="flex justify-content-between font-bold text-lg mb-4">
                            <span>Total</span>
                            <span style="color:#2e7d32">R$ {{ totalVenda.toFixed(2) }}</span>
                        </div>
                        <Button
                            label="Confirmar Venda"
                            icon="pi pi-check"
                            class="p-button-success w-full"
                            :loading="confirmando"
                            :disabled="!clienteEncontrado"
                            @click="confirmarVenda"
                        />
                    </div>
                    <div v-else class="text-center text-color-secondary text-sm py-2">
                        Nenhum item selecionado
                    </div>
                </div>
            </div>
        </div>

        <!-- Sucesso -->
        <Dialog v-model:visible="dialogSucesso" header="Venda realizada!" modal :style="{ width: '420px' }"
            :closable="false">
            <div class="text-center py-3">
                <i class="pi pi-check-circle text-6xl mb-3 block" style="color:#16a34a"></i>
                <div class="font-semibold text-xl mb-2">Pedido #{{ pedidoCriado?.id }}</div>
                <div class="text-color-secondary mb-1">Cliente: {{ clienteEncontrado?.name }}</div>
                <div class="font-bold text-lg" style="color:#2e7d32">
                    Total: R$ {{ pedidoCriado ? Number(pedidoCriado.total).toFixed(2) : '' }}
                </div>
                <p class="text-color-secondary text-sm mt-3">
                    Os ingressos estão disponíveis na conta do cliente.
                </p>
            </div>
            <template #footer>
                <Button label="Nova venda" icon="pi pi-plus" class="p-button-success w-full"
                    @click="reiniciar" />
            </template>
        </Dialog>

        <Toast />
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useToast } from 'primevue/usetoast';
import api from '@/service/ApiService';

const toast = useToast();

const verificando      = ref(true);
const isComissario     = ref(false);
const buscando         = ref(false);
const confirmando      = ref(false);
const cpfInput         = ref('');
const erroCpf          = ref('');
const cpfNaoEncontrado = ref(false);
const clienteEncontrado = ref(null);
const eventos          = ref([]);
const produtos         = ref([]);
const dialogSucesso    = ref(false);
const pedidoCriado     = ref(null);

// carrinho local: { type, id, label, price, qty }
const carrinho = reactive([]);

const totalVenda = computed(() =>
    carrinho.reduce((acc, item) => acc + item.price * item.qty, 0)
);

// ── Verificações iniciais ──────────────────────────────────────────────────────

async function verificarComissario() {
    try {
        const { data } = await api.get('/commissioner/status');
        isComissario.value = data.is_commissioner;
        if (isComissario.value) await carregarItens();
    } catch {
        isComissario.value = false;
    } finally { verificando.value = false; }
}

async function carregarItens() {
    const [evRes, prRes] = await Promise.all([
        api.get('/events'),
        api.get('/products'),
    ]).catch(() => [{ data: [] }, { data: [] }]);

    eventos.value  = (evRes.data ?? []).filter(e => e.status === 'active');
    produtos.value = (prRes.data ?? []).filter(p => p.active && p.stock > 0);
}

// ── Busca de cliente ───────────────────────────────────────────────────────────

async function buscarCliente() {
    erroCpf.value = '';
    cpfNaoEncontrado.value = false;
    const digitos = cpfInput.value.replace(/\D/g, '');
    if (digitos.length !== 11) {
        erroCpf.value = 'Informe um CPF válido com 11 dígitos.';
        return;
    }
    buscando.value = true;
    try {
        const { data } = await api.get('/commissioner/lookup', {
            params: { cpf: cpfInput.value },
        });
        if (data.found) {
            clienteEncontrado.value = data.customer;
        }
    } catch (err) {
        const status = err.response?.status;
        const msg    = err.response?.data?.message ?? '';
        if (status === 404) {
            cpfNaoEncontrado.value = true;
        } else if (status === 403) {
            toast.add({ severity: 'error', summary: 'Sem permissão', detail: msg, life: 4000 });
        } else {
            erroCpf.value = msg || 'Erro ao buscar CPF.';
        }
    } finally { buscando.value = false; }
}

function limparCliente() {
    clienteEncontrado.value = null;
    cpfInput.value          = '';
    erroCpf.value           = '';
    cpfNaoEncontrado.value  = false;
    carrinho.splice(0);
}

// ── Carrinho local ─────────────────────────────────────────────────────────────

function getQty(type, id) {
    return carrinho.find(i => i.type === type && i.id === id)?.qty ?? 0;
}

function incrementar(type, id, nome, price, eventoNome) {
    const idx = carrinho.findIndex(i => i.type === type && i.id === id);
    const label = type === 'ticket_batch'
        ? `${eventoNome} — ${nome}`
        : nome;
    if (idx >= 0) {
        carrinho[idx].qty++;
    } else {
        carrinho.push({ type, id, label, price: Number(price), qty: 1 });
    }
}

function decrementar(type, id) {
    const idx = carrinho.findIndex(i => i.type === type && i.id === id);
    if (idx < 0) return;
    if (carrinho[idx].qty <= 1) {
        carrinho.splice(idx, 1);
    } else {
        carrinho[idx].qty--;
    }
}

// ── Confirmar venda ────────────────────────────────────────────────────────────

async function confirmarVenda() {
    if (!clienteEncontrado.value || carrinho.length === 0) return;
    confirmando.value = true;
    try {
        const { data } = await api.post('/commissioner/orders', {
            customer_user_id: clienteEncontrado.value.id,
            items: carrinho.map(i => ({ type: i.type, id: i.id, qty: i.qty })),
        });
        pedidoCriado.value  = data;
        dialogSucesso.value = true;
    } catch (err) {
        toast.add({
            severity: 'error',
            summary: 'Erro na venda',
            detail: err.response?.data?.message ?? 'Tente novamente.',
            life: 4000,
        });
    } finally { confirmando.value = false; }
}

function reiniciar() {
    dialogSucesso.value     = false;
    pedidoCriado.value      = null;
    limparCliente();
}

// ── Utilitários ────────────────────────────────────────────────────────────────

function formatarData(d) {
    if (!d) return '';
    return new Date(d).toLocaleString('pt-BR', {
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
}

onMounted(() => verificarComissario());
</script>