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
                        <Button label="Novo Comissário" icon="pi pi-plus" class="p-button-success" @click="abrirNovo" />
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
                    <Column field="created_at" header="Cadastrado em" style="width:140px">
                        <template #body="{ data }">
                            <span class="text-sm">{{ formatarData(data.created_at) }}</span>
                        </template>
                    </Column>
                    <Column header="Ações" style="width:140px">
                        <template #body="{ data }">
                            <Button icon="pi pi-list" class="p-button-rounded p-button-text p-button-info mr-1"
                                v-tooltip.top="'Ver vendas'" @click="abrirVendas(data)" />
                            <Button icon="pi pi-pencil" class="p-button-rounded p-button-text mr-1"
                                v-tooltip.top="'Editar'" @click="abrirEdicao(data)" />
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

        <!-- Dialog: criar comissário -->
        <Dialog v-model:visible="dialogNovo" header="Novo Comissário" modal :style="{ width: '560px' }" :closable="!salvando">
            <div class="mb-3">
                <div class="flex gap-2">
                    <Button :label="form.mode === 'new' ? 'Novo usuário' : 'Novo usuário'" icon="pi pi-user-plus"
                        :class="['flex-1', form.mode === 'new' ? '' : 'p-button-outlined']"
                        @click="form.mode = 'new'" />
                    <Button label="Usuário existente" icon="pi pi-users"
                        :class="['flex-1', form.mode === 'existing' ? '' : 'p-button-outlined']"
                        @click="form.mode = 'existing'" />
                </div>
            </div>

            <div class="grid p-fluid">
                <!-- MODO: novo usuário -->
                <template v-if="form.mode === 'new'">
                    <div class="col-12">
                        <div class="field">
                            <label>Nome completo <span class="p-error">*</span></label>
                            <InputText v-model="form.name" :class="{ 'p-invalid': erros.name }" placeholder="Nome do comissário" />
                            <small class="p-error">{{ erros.name }}</small>
                        </div>
                    </div>
                    <div class="col-12 md:col-6">
                        <div class="field">
                            <label>E-mail <span class="p-error">*</span></label>
                            <InputText v-model="form.email" type="email" :class="{ 'p-invalid': erros.email }" />
                            <small class="p-error">{{ erros.email }}</small>
                        </div>
                    </div>
                    <div class="col-12 md:col-6">
                        <div class="field">
                            <label>Senha <span class="p-error">*</span></label>
                            <Password v-model="form.password" :class="{ 'p-invalid': erros.password }" :feedback="false" toggleMask />
                            <small class="p-error">{{ erros.password }}</small>
                        </div>
                    </div>
                </template>

                <!-- MODO: usuário existente -->
                <template v-else>
                    <div class="col-12">
                        <div class="field">
                            <label>E-mail do usuário <span class="p-error">*</span></label>
                            <div class="flex gap-2">
                                <InputText v-model="form.emailBusca" type="email" class="flex-1" placeholder="email@exemplo.com" :disabled="!!usuarioEncontrado" />
                                <Button v-if="!usuarioEncontrado" label="Buscar" icon="pi pi-search" :loading="buscandoUser" @click="buscarUsuario" />
                                <Button v-else icon="pi pi-times" class="p-button-outlined p-button-danger" v-tooltip.top="'Limpar'" @click="limparUsuario" />
                            </div>
                        </div>
                        <div v-if="usuarioEncontrado" class="flex align-items-center gap-2 p-3 border-round mb-2" style="background:var(--surface-ground)">
                            <i class="pi pi-check-circle" style="color:var(--green-500)"></i>
                            <div>
                                <div class="font-semibold">{{ usuarioEncontrado.name }}</div>
                                <div class="text-sm text-color-secondary">{{ usuarioEncontrado.email }}</div>
                            </div>
                        </div>
                    </div>
                </template>

                <div class="col-12">
                    <div class="field">
                        <label>Observações</label>
                        <Textarea v-model="form.notes" rows="2" autoResize placeholder="Observações internas (opcional)" />
                    </div>
                </div>
                <div class="col-12">
                    <div class="field mb-0">
                        <label>Status</label>
                        <div class="flex align-items-center gap-2 mt-1">
                            <InputSwitch v-model="form.is_active" />
                            <span class="text-sm">{{ form.is_active ? 'Ativo' : 'Inativo' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <template #footer>
                <Button label="Cancelar" class="p-button-text" :disabled="salvando" @click="dialogNovo = false" />
                <Button label="Cadastrar comissário" icon="pi pi-check" class="p-button-success" :loading="salvando" @click="salvar" />
            </template>
        </Dialog>

        <!-- Dialog: editar -->
        <Dialog v-model:visible="dialogEditar" header="Editar Comissário" modal :style="{ width: '420px' }" :closable="!salvando">
            <div class="grid p-fluid mt-1">
                <div class="col-12">
                    <div class="p-3 border-round mb-3" style="background:var(--surface-ground)">
                        <div class="font-semibold">{{ editando?.user?.name }}</div>
                        <div class="text-sm text-color-secondary">{{ editando?.user?.email }}</div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="field">
                        <label>Observações</label>
                        <Textarea v-model="formEditar.notes" rows="3" autoResize />
                    </div>
                </div>
                <div class="col-12">
                    <div class="field mb-0">
                        <label>Status</label>
                        <div class="flex align-items-center gap-2 mt-1">
                            <InputSwitch v-model="formEditar.is_active" />
                            <span class="text-sm">{{ formEditar.is_active ? 'Ativo' : 'Inativo' }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <template #footer>
                <Button label="Cancelar" class="p-button-text" :disabled="salvando" @click="dialogEditar = false" />
                <Button label="Salvar" icon="pi pi-check" class="p-button-success" :loading="salvando" @click="atualizar" />
            </template>
        </Dialog>

        <!-- Dialog: vendas do comissário -->
        <Dialog v-model:visible="dialogVendas" :header="`Vendas — ${vendaComissario?.user?.name ?? ''}`" modal :style="{ width: '700px' }">
            <DataTable :value="vendas" :loading="carregandoVendas" responsiveLayout="scroll" stripedRows size="small">
                <template #empty><div class="text-center py-4 text-color-secondary">Nenhuma venda registrada.</div></template>
                <Column field="id" header="#" style="width:60px" />
                <Column field="customer_name" header="Cliente" />
                <Column field="customer_cpf_masked" header="CPF" style="width:140px">
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
import { onMounted, reactive, ref } from 'vue';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import api from '@/service/ApiService';

const confirm = useConfirm();
const toast   = useToast();

const comissarios   = ref([]);
const loading       = ref(false);
const busca         = ref('');
const filtroStatus  = ref(null);
const paginacao     = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 });

const dialogNovo    = ref(false);
const dialogEditar  = ref(false);
const dialogVendas  = ref(false);
const salvando      = ref(false);
const buscandoUser  = ref(false);

const editando         = ref(null);
const vendaComissario  = ref(null);
const usuarioEncontrado = ref(null);

const vendas          = ref([]);
const carregandoVendas = ref(false);
const vendasPaginacao  = ref({ current_page: 1, last_page: 1, per_page: 10, total: 0 });

const opcoesStatus = [
    { label: 'Ativos', value: '1' },
    { label: 'Inativos', value: '0' },
];

const form = reactive({
    mode: 'new', name: '', email: '', password: '', emailBusca: '',
    user_id: null, notes: '', is_active: true,
});
const formEditar = reactive({ notes: '', is_active: true });
const erros      = reactive({ name: '', email: '', password: '' });

let buscaTimer = null;

function buscarDebounce() {
    clearTimeout(buscaTimer);
    buscaTimer = setTimeout(() => carregar(1), 400);
}

function limparFiltros() { busca.value = ''; filtroStatus.value = null; carregar(1); }

function limparForm() {
    Object.assign(form, { mode: 'new', name: '', email: '', password: '', emailBusca: '', user_id: null, notes: '', is_active: true });
    Object.assign(erros, { name: '', email: '', password: '' });
    usuarioEncontrado.value = null;
}

function abrirNovo() { limparForm(); dialogNovo.value = true; }

function abrirEdicao(c) {
    editando.value = c;
    Object.assign(formEditar, { notes: c.notes ?? '', is_active: c.is_active });
    dialogEditar.value = true;
}

function limparUsuario() {
    form.emailBusca = '';
    form.user_id = null;
    usuarioEncontrado.value = null;
}

async function buscarUsuario() {
    if (!form.emailBusca.trim()) return;
    buscandoUser.value = true;
    try {
        const { data } = await api.get('/admin/comissarios/buscar-usuario', { params: { email: form.emailBusca } });
        usuarioEncontrado.value = data;
        form.user_id = data.id;
        toast.add({ severity: 'success', summary: 'Usuário encontrado!', life: 2000 });
    } catch (err) {
        toast.add({ severity: 'warn', summary: err.response?.data?.message ?? 'Usuário não encontrado.', life: 3000 });
    } finally { buscandoUser.value = false; }
}

async function salvar() {
    Object.assign(erros, { name: '', email: '', password: '' });
    if (form.mode === 'existing' && !form.user_id) {
        toast.add({ severity: 'warn', summary: 'Busque e selecione um usuário existente.', life: 3000 });
        return;
    }
    salvando.value = true;
    try {
        await api.post('/admin/comissarios', form);
        toast.add({ severity: 'success', summary: 'Comissário cadastrado!', life: 2500 });
        dialogNovo.value = false;
        carregar(1);
    } catch (err) {
        const e = err.response?.data?.errors ?? {};
        Object.keys(e).forEach(k => { if (k in erros) erros[k] = e[k][0]; });
        if (!Object.values(erros).some(Boolean))
            toast.add({ severity: 'error', summary: 'Erro', detail: err.response?.data?.message ?? 'Erro ao salvar.', life: 3000 });
    } finally { salvando.value = false; }
}

async function atualizar() {
    salvando.value = true;
    try {
        await api.put(`/admin/comissarios/${editando.value.id}`, formEditar);
        toast.add({ severity: 'success', summary: 'Comissário atualizado!', life: 2500 });
        dialogEditar.value = false;
        carregar(paginacao.value.current_page);
    } catch (err) {
        toast.add({ severity: 'error', summary: 'Erro', detail: err.response?.data?.message ?? 'Erro.', life: 3000 });
    } finally { salvando.value = false; }
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

function formatarData(d) {
    if (!d) return '—';
    return new Date(d).toLocaleDateString('pt-BR');
}
function statusLabel(s) {
    return { pending: 'Pendente', paid: 'Pago', cancelled: 'Cancelado', refunded: 'Reembolsado' }[s] ?? s;
}
function statusSeverity(s) {
    return { pending: 'warning', paid: 'success', cancelled: 'danger', refunded: 'info' }[s] ?? 'secondary';
}

onMounted(() => carregar());
</script>