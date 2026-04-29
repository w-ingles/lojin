<template>
    <div>
        <Button icon="pi pi-arrow-left" class="p-button-text mb-3" label="Voltar" @click="$router.back()" />

        <!-- Sem permissão -->
        <div v-if="!isComissario && !verificando" class="card text-center py-6">
            <i class="pi pi-ban text-6xl text-color-secondary mb-3 block"></i>
            <h4 class="text-color-secondary">Acesso restrito</h4>
            <p class="text-color-secondary">Você não é um comissário ativo desta atlética.</p>
        </div>

        <div v-else-if="!verificando" class="grid">

            <!-- Coluna principal -->
            <div class="col-12 lg:col-8">

                <!-- Busca por CPF -->
                <div class="card mb-3">
                    <h5 class="mt-0">Identificar cliente</h5>

                    <div class="grid p-fluid">
                        <div class="col-12 md:col-9">
                            <div class="field mb-0">
                                <label>CPF do cliente</label>
                                <InputMask
                                    v-model="cpfInput"
                                    mask="999.999.999-99"
                                    placeholder="000.000.000-00"
                                    :disabled="!!clienteEncontrado || buscando"
                                    :class="{ 'p-invalid': erroCpf }"
                                    @keydown.enter="buscarCliente"
                                />
                                <small class="p-error">{{ erroCpf }}</small>
                            </div>
                        </div>
                        <div class="col-12 md:col-3 flex align-items-end">
                            <Button v-if="!clienteEncontrado"
                                label="Buscar" icon="pi pi-search" class="w-full"
                                :loading="buscando" @click="buscarCliente" />
                            <Button v-else
                                label="Trocar" icon="pi pi-times" class="w-full p-button-outlined p-button-secondary"
                                @click="limparCliente" />
                        </div>
                    </div>

                    <!-- CPF não encontrado -->
                    <Message v-if="cpfNaoEncontrado" severity="warn" :closable="false" class="mt-3">
                        Este CPF não está cadastrado. Solicite que a pessoa realize o cadastro na plataforma.
                    </Message>

                    <!-- Cliente encontrado -->
                    <div v-if="clienteEncontrado"
                        class="flex align-items-center gap-3 p-3 border-round mt-3"
                        style="background:var(--surface-ground)">
                        <i class="pi pi-check-circle text-3xl" style="color:#2e7d32"></i>
                        <div>
                            <div class="font-semibold text-lg">{{ clienteEncontrado.name }}</div>
                            <div class="text-sm text-color-secondary">{{ clienteEncontrado.email }}</div>
                            <div class="text-sm text-color-secondary">{{ clienteEncontrado.phone }}</div>
                        </div>
                    </div>
                </div>

                <!-- Eventos e lotes -->
                <template v-if="clienteEncontrado">
                    <div v-if="eventos.length" class="card mb-3">
                        <h5 class="mt-0">Eventos disponíveis</h5>

                        <div v-for="evento in eventos" :key="evento.id" class="mb-4">
                            <div class="font-semibold mb-1">{{ evento.name }}</div>
                            <div class="flex gap-3 text-color-secondary text-sm mb-3">
                                <span><i class="pi pi-calendar mr-1"></i>{{ formatDate(evento.starts_at) }}</span>
                                <span v-if="evento.location">
                                    <i class="pi pi-map-marker mr-1"></i>{{ evento.location }}
                                </span>
                            </div>

                            <div v-for="lote in evento.ticket_batches" :key="lote.id"
                                class="mb-2 p-3 border-round"
                                style="background:var(--surface-ground)">
                                <div class="flex justify-content-between align-items-center">
                                    <div>
                                        <div class="font-semibold">{{ lote.name }}</div>
                                        <div class="text-sm text-color-secondary">
                                            {{ lote.available }} disponíveis ·
                                            <span style="color:#2e7d32">
                                                {{ lote.price > 0 ? 'R$ ' + Number(lote.price).toFixed(2) : 'Gratuito' }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex align-items-center gap-2">
                                        <Button icon="pi pi-minus" class="p-button-text p-button-sm"
                                            :disabled="!getQty('ticket_batch', lote.id)"
                                            @click="decrementar('ticket_batch', lote.id)" />
                                        <span class="font-bold w-2rem text-center">{{ getQty('ticket_batch', lote.id) }}</span>
                                        <Button icon="pi pi-plus" class="p-button-text p-button-sm"
                                            :disabled="lote.available <= 0 || getQty('ticket_batch', lote.id) >= lote.available"
                                            @click="incrementar('ticket_batch', lote.id, lote.name, lote.price, evento.name)" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Produtos -->
                    <div v-if="produtos.length" class="card">
                        <h5 class="mt-0">Produtos</h5>

                        <div v-for="produto in produtos" :key="produto.id"
                            class="mb-2 p-3 border-round"
                            style="background:var(--surface-ground)">
                            <div class="flex justify-content-between align-items-center">
                                <div>
                                    <div class="font-semibold">{{ produto.name }}</div>
                                    <div class="text-sm text-color-secondary">
                                        {{ produto.stock }} em estoque ·
                                        <span style="color:#2e7d32">R$ {{ Number(produto.price).toFixed(2) }}</span>
                                    </div>
                                </div>
                                <div class="flex align-items-center gap-2">
                                    <Button icon="pi pi-minus" class="p-button-text p-button-sm"
                                        :disabled="!getQty('product', produto.id)"
                                        @click="decrementar('product', produto.id)" />
                                    <span class="font-bold w-2rem text-center">{{ getQty('product', produto.id) }}</span>
                                    <Button icon="pi pi-plus" class="p-button-text p-button-sm"
                                        :disabled="produto.stock <= 0 || getQty('product', produto.id) >= produto.stock"
                                        @click="incrementar('product', produto.id, produto.name, produto.price)" />
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Sidebar: resumo e confirmação -->
            <div class="col-12 lg:col-4">
                <div class="card" style="position:sticky;top:80px">
                    <h5 class="mt-0">Resumo da Venda</h5>

                    <div class="mb-3 pb-3" style="border-bottom:1px solid var(--surface-border)">
                        <div class="text-xs text-color-secondary mb-1">CLIENTE</div>
                        <div v-if="clienteEncontrado" class="font-semibold">{{ clienteEncontrado.name }}</div>
                        <div v-else class="text-color-secondary text-sm">Busque o cliente pelo CPF</div>
                    </div>

                    <div v-if="carrinho.length">
                        <div v-for="item in carrinho" :key="`${item.type}-${item.id}`"
                            class="flex justify-content-between mb-2 text-sm">
                            <span>{{ item.qty }}× {{ item.label }}</span>
                            <span class="font-semibold">R$ {{ (item.price * item.qty).toFixed(2) }}</span>
                        </div>
                        <Divider />
                        <div class="flex justify-content-between font-bold text-lg mb-3">
                            <span>Total</span>
                            <span style="color:#2e7d32">R$ {{ totalVenda.toFixed(2) }}</span>
                        </div>
                        <Button label="Confirmar Venda" icon="pi pi-check"
                            class="p-button-success w-full"
                            :loading="confirmando"
                            :disabled="!clienteEncontrado"
                            @click="confirmarVenda" />
                    </div>
                    <div v-else class="text-center text-color-secondary text-sm py-3">
                        Nenhum item selecionado
                    </div>
                </div>
            </div>
        </div>

        <!-- Dialog de sucesso -->
        <Dialog v-model:visible="dialogSucesso" header="Venda realizada!" modal
            :style="{ width: '380px' }" :closable="false">
            <div class="text-center py-2">
                <i class="pi pi-check-circle text-5xl mb-3 block" style="color:#2e7d32"></i>
                <div class="font-semibold text-xl mb-1">Pedido #{{ pedidoCriado?.id }}</div>
                <div class="text-color-secondary mb-1">{{ clienteEncontrado?.name }}</div>
                <div class="font-bold text-xl" style="color:#2e7d32">
                    R$ {{ pedidoCriado ? Number(pedidoCriado.total).toFixed(2) : '' }}
                </div>
                <p class="text-color-secondary text-sm mt-3 mb-0">
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

const verificando       = ref(true);
const isComissario      = ref(false);
const buscando          = ref(false);
const confirmando       = ref(false);
const cpfInput          = ref('');
const erroCpf           = ref('');
const cpfNaoEncontrado  = ref(false);
const clienteEncontrado = ref(null);
const eventos           = ref([]);
const produtos          = ref([]);
const dialogSucesso     = ref(false);
const pedidoCriado      = ref(null);

const carrinho = reactive([]);

const totalVenda = computed(() =>
    carrinho.reduce((acc, i) => acc + i.price * i.qty, 0)
);

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
    const [evRes, prRes] = await Promise.allSettled([
        api.get('/events'),
        api.get('/products'),
    ]);
    eventos.value  = (evRes.value?.data  ?? []).filter(e => e.status === 'active');
    produtos.value = (prRes.value?.data ?? []).filter(p => p.active && p.stock > 0);
}

async function buscarCliente() {
    erroCpf.value = '';
    cpfNaoEncontrado.value = false;
    if (cpfInput.value.replace(/\D/g, '').length !== 11) {
        erroCpf.value = 'Informe um CPF válido com 11 dígitos.';
        return;
    }
    buscando.value = true;
    try {
        const { data } = await api.get('/commissioner/lookup', { params: { cpf: cpfInput.value } });
        if (data.found) clienteEncontrado.value = data.customer;
    } catch (err) {
        if (err.response?.status === 404) cpfNaoEncontrado.value = true;
        else erroCpf.value = err.response?.data?.message ?? 'Erro ao buscar CPF.';
    } finally { buscando.value = false; }
}

function limparCliente() {
    clienteEncontrado.value = null;
    cpfInput.value          = '';
    erroCpf.value           = '';
    cpfNaoEncontrado.value  = false;
    carrinho.splice(0);
}

function getQty(type, id) {
    return carrinho.find(i => i.type === type && i.id === id)?.qty ?? 0;
}

function incrementar(type, id, nome, price, eventoNome) {
    const label = type === 'ticket_batch' ? `${eventoNome} — ${nome}` : nome;
    const idx = carrinho.findIndex(i => i.type === type && i.id === id);
    if (idx >= 0) carrinho[idx].qty++;
    else carrinho.push({ type, id, label, price: Number(price), qty: 1 });
}

function decrementar(type, id) {
    const idx = carrinho.findIndex(i => i.type === type && i.id === id);
    if (idx < 0) return;
    if (carrinho[idx].qty <= 1) carrinho.splice(idx, 1);
    else carrinho[idx].qty--;
}

async function confirmarVenda() {
    if (!clienteEncontrado.value || !carrinho.length) return;
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
    dialogSucesso.value = false;
    pedidoCriado.value  = null;
    limparCliente();
}

function formatDate(d) {
    return new Date(d).toLocaleString('pt-BR', {
        day: '2-digit', month: 'short', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
}

onMounted(() => verificarComissario());
</script>
