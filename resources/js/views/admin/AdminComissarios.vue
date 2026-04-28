<template>
    <div class="grid">
        <div class="col-12">
            <div class="card">
                <Toolbar class="mb-4">
                    <template #start>
                        <h5 class="m-0 mr-3">Comissários</h5>
                        <span class="text-color-secondary text-sm">{{ paginacao.total }} cadastrado(s)</span>
                    </template>
                    <template #end>
                        <Button label="Adicionar Comissário" icon="pi pi-plus" class="p-button-success" @click="abrirNovo" />
                    </template>
                </Toolbar>

                <div class="flex gap-2 mb-4">
                    <span class="p-input-icon-left flex-1">
                        <i class="pi pi-search" />
                        <InputText v-model="busca" placeholder="Buscar por nome ou e-mail..." class="w-full" @input="buscarDebounce" />
                    </span>
                    <Dropdown v-model="filtroStatus" :options="opcoesStatus" optionLabel="label" optionValue="value"
                        placeholder="Todos os status" showClear style="width:180px" @change="carregar(1)" />
                    <Button icon="pi pi-filter-slash" class="p-button-outlined" v-tooltip.top="'Limpar'" @click="limparFiltros" />
                </div>

                <DataTable :value="comissarios" :loading="loading" responsiveLayout="scroll" stripedRows>
                    <template #empty>
                        <div class="text-center p-4 text-color-secondary">
                            {{ busca ? 'Nenhum comissário encontrado.' : 'Nenhum comissário cadastrado.' }}
                        </div>
                    </template>
                    <Column header="Comissário">
                        <template #body="{ data }">
                            <div class="font-semibold">{{ data.user?.name }}</div>
                            <div class="text-sm text-color-secondary">{{ data.user?.email }}</div>
                        </template>
                    </Column>
                    <Column header="CPF" style="width:140px">
                        <template #body="{ data }">
                            <span class="text-sm font-mono">{{ formatCpf(data.user?.cpf) }}</span>
                        </template>
                    </Column>
                    <Column field="orders_count" header="Vendas" style="width:90px">
                        <template #body="{ data }">
                            <span class="text-sm"><i class="pi pi-shopping-cart mr-1" style="color:#1976d2"></i>{{ data.orders_count }}</span>
                        </template>
                    </Column>
                    <Column field="is_active" header="Status" style="width:100px">
                        <template #body="{ data }">
                            <Tag :value="data.is_active ? 'Ativo' : 'Inativo'" :severity="data.is_active ? 'success' : 'danger'" />
                        </template>
                    </Column>
                    <Column field="created_at" header="Adicionado em" style="width:140px">
                        <template #body="{ data }">
                            <span class="text-sm">{{ formatarData(data.created_at) }}</span>
                        </template>
                    </Column>
                    <Column header="Ações" style="width:130px">
                        <template #body="{ data }">
                            <Button icon="pi pi-list" class="p-button-rounded p-button-text p-button-info mr-1"
                                v-tooltip.top="'Ver vendas'" @click="abrirVendas(data)" />
                            <Button :icon="data.is_active ? 'pi pi-pause' : 'pi pi-play'"
                                :class="['p-button-rounded p-button-text mr-1', data.is_active ? 'p-button-warning' : 'p-button-success']"
                                :v-tooltip="data.is_active ? 'Desativar' : 'Ativar'" @click="toggleAtivo(data)" />
                            <Button icon="pi pi-trash" class="p-button-rounded p-button-text p-button-danger"
                                v-tooltip.top="'Remover'" @click="confirmarRemover(data)" />
                        </template>
                    </Column>
                </DataTable>

                <Paginator v-if="paginacao.last_page > 1"
                    :first="(paginacao.current_page - 1) * paginacao.per_page"
                    :rows="paginacao.per_page"
                    :totalRecords="paginacao.total"
                    class="mt-3"
                    @page="(e) => carregar(e.page + 1)"
                />
            </div>
        </div>

        <!-- Dialog: adicionar por CPF -->
        <Dialog v-model:visible="dialogNovo" header="Adicionar Comissário" modal :style="{ width: '440px' }" :closable="!salvando">
            <div class="p-fluid mt-1">
                <p class="text-color-secondary text-sm mt-0 mb-4">
                    Informe o CPF da pessoa. Se ela já tiver cadastro na plataforma, será vinculada automaticamente.
                </p>
                <div class="field mb-0">
                    <label>CPF <span class="p-error">*</span></label>
                    <InputMask v-model="cpfInput" mask="999.999.999-99" placeholder="000.000.000-00"
                        :class="{ 'p-invalid': erroCpf }" :disabled="salvando"
                        @keydown.enter="salvar" />
                    <small class="p-error" v-if="erroCpf">{{ erroCpf }}</small>
                </div>

                <!-- Resultado: CPF não encontrado -->
                <div v-if="cpfNaoEncontrado" class="flex gap-2 p-3 border-round mt-3" style="background:#fff8e1;border:1px solid #ffe082">
                    <i class="pi pi-info-circle mt-1" style="color:#f57f17;flex-shrink:0"></i>
                    <span class="text-sm" style="color:#6d4c00">
                        Este CPF não está cadastrado. Solicite que a pessoa realize o cadastro na plataforma para poder ser vinculada como comissário.
                    </span>
                </div>
            </div>

            <template #footer>
                <Button label="Cancelar" class="p-button-text" :disabled="salvando" @click="fecharDialog" />
                <Button label="Vincular como comissário" icon="pi pi-check" class="p-button-success" :loading="salvando" @click="salvar" />
            </template>
        </Dialog>

        <!-- Dialog: vendas do comissário -->
        <Dialog v-model:visible="dialogVendas" :header="`Vendas — ${vendaComissario?.user?.name ?? ''}`" modal :style="{ width: '700px' }">
            <DataTable :value="vendas" :loading="carregandoVendas" responsiveLayout="scroll" stripedRows size="small">
                <template #empty><div class="text-center py-4 text-color-secondary">Nenhuma venda registrada.</div></template>
                <Column field="id" header="#" style="width:60px" />
                <Column field="customer_name" header="Cliente" />
                <Column field="customer_cpf_masked" header="CPF do cliente" style="width:140px">
                    <template #body="{ data }">
                        <span class="text-sm font-mono">{{ data.customer_cpf_masked ?? '—' }}</span>
                    </template>
                </Column>
                <Column field="total" header="Total" style="width:100px">
                    <template #body="{ data }"><span>R$ {{ Number(data.total).toFixed(2) }}</span></template>
                </Column>
                <Column field="status" header="Status" style="width:100px">
                    <template #body="{ data }">
                        <Tag :value="statusLabel(data.status)" :severity="statusSeverity(data.status)" />
                    </template>
                </Column>
                <Column field="created_at" header="Data" style="width:130px">
                    <template #body="{ data }"><span class="text-sm">{{ formatarData(data.created_at) }}</span></template>
                </Column>
            </DataTable>
            <Paginator v-if="vendasPaginacao.last_page > 1"
                :first="(vendasPaginacao.current_page - 1) * vendasPaginacao.per_page"
                :rows="vendasPaginacao.per_page"
                :totalRecords="vendasPaginacao.total"
                class="mt-3"
                @page="(e) => carregarVendas(vendaComissario, e.page + 1)"
            />
            <template #footer>
                <Button label="Fechar" class="p-button-text" @click="dialogVendas = false" />
            </template>
        </Dialog>

        <ConfirmDialog /><Toast />
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import api from '@/service/ApiService';

const confirm = useConfirm();
const toast   = useToast();

const comissarios     = ref([]);
const loading         = ref(false);
const busca           = ref('');
const filtroStatus    = ref(null);
const paginacao       = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 });

const dialogNovo      = ref(false);
const dialogVendas    = ref(false);
const salvando        = ref(false);
const cpfInput        = ref('');
const erroCpf         = ref('');
const cpfNaoEncontrado = ref(false);

const vendaComissario  = ref(null);
const vendas           = ref([]);
const carregandoVendas = ref(false);
const vendasPaginacao  = ref({ current_page: 1, last_page: 1, per_page: 10, total: 0 });

const opcoesStatus = [
    { label: 'Ativos',   value: '1' },
    { label: 'Inativos', value: '0' },
];

let buscaTimer = null;

function buscarDebounce() {
    clearTimeout(buscaTimer);
    buscaTimer = setTimeout(() => carregar(1), 400);
}

function limparFiltros() { busca.value = ''; filtroStatus.value = null; carregar(1); }

function abrirNovo() {
    cpfInput.value = '';
    erroCpf.value  = '';
    cpfNaoEncontrado.value = false;
    dialogNovo.value = true;
}

function fecharDialog() {
    dialogNovo.value = false;
    cpfNaoEncontrado.value = false;
}

async function salvar() {
    erroCpf.value = '';
    cpfNaoEncontrado.value = false;

    const digitos = cpfInput.value.replace(/\D/g, '');
    if (digitos.length !== 11) { erroCpf.value = 'Informe um CPF válido.'; return; }

    salvando.value = true;
    try {
        await api.post('/admin/comissarios', { cpf: cpfInput.value });
        toast.add({ severity: 'success', summary: 'Comissário vinculado com sucesso!', life: 2500 });
        dialogNovo.value = false;
        carregar(1);
    } catch (err) {
        const code = err.response?.data?.code;
        const msg  = err.response?.data?.message ?? 'Erro ao vincular.';
        if (err.response?.status === 404 || code === 'CPF_NOT_FOUND') {
            cpfNaoEncontrado.value = true;
        } else if (err.response?.data?.errors?.cpf) {
            erroCpf.value = err.response.data.errors.cpf[0];
        } else {
            toast.add({ severity: 'error', summary: 'Erro', detail: msg, life: 4000 });
        }
    } finally { salvando.value = false; }
}

async function toggleAtivo(c) {
    try {
        await api.put(`/admin/comissarios/${c.id}`, { is_active: !c.is_active });
        c.is_active = !c.is_active;
        toast.add({ severity: 'info', summary: c.is_active ? 'Comissário ativado.' : 'Comissário desativado.', life: 2000 });
    } catch (err) {
        toast.add({ severity: 'error', summary: 'Erro', detail: err.response?.data?.message ?? 'Erro.', life: 3000 });
    }
}

function confirmarRemover(c) {
    confirm.require({
        message: `Remover "${c.user?.name}" dos comissários? O usuário não será excluído do sistema.`,
        header: 'Confirmar remoção',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'Sim, remover',
        rejectLabel: 'Cancelar',
        acceptClass: 'p-button-danger',
        accept: async () => {
            try {
                await api.delete(`/admin/comissarios/${c.id}`);
                toast.add({ severity: 'info', summary: 'Comissário removido.', life: 2500 });
                carregar(paginacao.value.current_page);
            } catch (err) {
                toast.add({ severity: 'error', summary: 'Erro', detail: err.response?.data?.message ?? 'Erro.', life: 3000 });
            }
        },
    });
}

async function abrirVendas(c) {
    vendaComissario.value = c;
    dialogVendas.value = true;
    await carregarVendas(c, 1);
}

async function carregarVendas(c, page = 1) {
    carregandoVendas.value = true;
    try {
        const { data } = await api.get(`/admin/comissarios/${c.id}/vendas`, { params: { page } });
        vendas.value = data.data;
        vendasPaginacao.value = { current_page: data.current_page, last_page: data.last_page, per_page: data.per_page, total: data.total };
    } finally { carregandoVendas.value = false; }
}

async function carregar(page = 1) {
    loading.value = true;
    try {
        const params = { page, per_page: 15 };
        if (busca.value.trim()) params.search = busca.value.trim();
        if (filtroStatus.value !== null) params.status = filtroStatus.value;
        const { data } = await api.get('/admin/comissarios', { params });
        comissarios.value = data.data;
        paginacao.value = { current_page: data.current_page, last_page: data.last_page, per_page: data.per_page, total: data.total };
    } finally { loading.value = false; }
}

function formatCpf(cpf) {
    if (!cpf) return '—';
    const d = cpf.replace(/\D/g, '');
    return d.length === 11 ? `${d.slice(0,3)}.${d.slice(3,6)}.${d.slice(6,9)}-${d.slice(9)}` : cpf;
}
function formatarData(d) {
    return d ? new Date(d).toLocaleDateString('pt-BR') : '—';
}
function statusLabel(s) {
    return { pending: 'Pendente', paid: 'Pago', cancelled: 'Cancelado', refunded: 'Reembolsado' }[s] ?? s;
}
function statusSeverity(s) {
    return { pending: 'warning', paid: 'success', cancelled: 'danger', refunded: 'info' }[s] ?? 'secondary';
}

onMounted(() => carregar());
</script>