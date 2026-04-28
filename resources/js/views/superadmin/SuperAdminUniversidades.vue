<template>
    <div class="grid">
        <div class="col-12">
            <div class="card">
                <Toolbar class="mb-4">
                    <template #start>
                        <h5 class="m-0 mr-3">Universidades</h5>
                        <span class="text-color-secondary text-sm">{{ paginacao.total }} cadastrada(s)</span>
                    </template>
                    <template #end>
                        <Button label="Nova Universidade" icon="pi pi-plus" class="p-button-success" @click="abrirNova" />
                    </template>
                </Toolbar>

                <div class="flex gap-2 mb-4">
                    <span class="p-input-icon-left flex-1">
                        <i class="pi pi-search" />
                        <InputText v-model="busca" placeholder="Buscar por nome ou sigla..." class="w-full" @input="buscarDebounce" />
                    </span>
                    <Button icon="pi pi-filter-slash" class="p-button-outlined" v-tooltip.top="'Limpar busca'" @click="limparBusca" />
                </div>

                <DataTable :value="universidades" :loading="loading" responsiveLayout="scroll" stripedRows>
                    <template #empty>
                        <div class="text-center p-4 text-color-secondary">
                            {{ busca ? 'Nenhuma universidade encontrada para a busca.' : 'Nenhuma universidade cadastrada.' }}
                        </div>
                    </template>
                    <Column field="id" header="ID" style="width:60px" />
                    <Column field="name" header="Nome" sortable />
                    <Column field="acronym" header="Sigla" style="width:90px">
                        <template #body="{ data }">
                            <Tag v-if="data.acronym" :value="data.acronym" severity="info" />
                            <span v-else class="text-color-secondary text-sm">—</span>
                        </template>
                    </Column>
                    <Column header="Localização" style="width:200px">
                        <template #body="{ data }">
                            <span v-if="data.city || data.state" class="text-sm">{{ [data.city, data.state].filter(Boolean).join(' — ') }}</span>
                            <span v-else class="text-color-secondary text-sm">—</span>
                        </template>
                    </Column>
                    <Column field="tenants_count" header="Atléticas" style="width:100px">
                        <template #body="{ data }">
                            <span class="text-sm"><i class="pi pi-bolt mr-1" style="color:#1976d2"></i>{{ data.tenants_count }}</span>
                        </template>
                    </Column>
                    <Column field="is_active" header="Status" style="width:100px">
                        <template #body="{ data }">
                            <Tag :value="data.is_active ? 'Ativa' : 'Inativa'" :severity="data.is_active ? 'success' : 'danger'" />
                        </template>
                    </Column>
                    <Column header="Ações" style="width:110px">
                        <template #body="{ data }">
                            <Button icon="pi pi-pencil" class="p-button-rounded p-button-text mr-1" v-tooltip.top="'Editar'" @click="abrirEdicao(data)" />
                            <Button icon="pi pi-trash" class="p-button-rounded p-button-text p-button-danger" v-tooltip.top="'Excluir'" :disabled="data.tenants_count > 0" @click="confirmarExcluir(data)" />
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

        <Dialog v-model:visible="dialog" :header="editando ? 'Editar Universidade' : 'Nova Universidade'" modal :style="{ width: '520px' }" :closable="!salvando">
            <div class="grid p-fluid mt-1">
                <div class="col-12 md:col-8">
                    <div class="field">
                        <label>Nome <span class="p-error">*</span></label>
                        <InputText v-model="form.name" :class="{ 'p-invalid': erros.name }" placeholder="Ex: Universidade de São Paulo" />
                        <small class="p-error">{{ erros.name }}</small>
                    </div>
                </div>
                <div class="col-12 md:col-4">
                    <div class="field">
                        <label>Sigla</label>
                        <InputText v-model="form.acronym" :class="{ 'p-invalid': erros.acronym }" placeholder="Ex: USP" />
                        <small class="p-error">{{ erros.acronym }}</small>
                    </div>
                </div>
                <div class="col-12 md:col-7">
                    <div class="field">
                        <label>Cidade</label>
                        <InputText v-model="form.city" placeholder="Ex: São Paulo" />
                    </div>
                </div>
                <div class="col-12 md:col-5">
                    <div class="field">
                        <label>Estado (UF)</label>
                        <InputText v-model="form.state" maxlength="2" placeholder="Ex: SP" @input="form.state = form.state.toUpperCase()" />
                        <small class="p-error">{{ erros.state }}</small>
                    </div>
                </div>
                <div class="col-12">
                    <div class="field mb-0">
                        <label>Status</label>
                        <div class="flex align-items-center gap-2 mt-1">
                            <InputSwitch v-model="form.is_active" />
                            <span class="text-sm">{{ form.is_active ? 'Ativa' : 'Inativa' }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <template #footer>
                <Button label="Cancelar" class="p-button-text" :disabled="salvando" @click="dialog = false" />
                <Button :label="editando ? 'Salvar alterações' : 'Cadastrar universidade'" icon="pi pi-check" class="p-button-success" :loading="salvando" @click="salvar" />
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

const universidades = ref([]);
const loading       = ref(false);
const busca         = ref('');
const paginacao     = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 });
const dialog        = ref(false);
const editando      = ref(false);
const salvando      = ref(false);

const form  = reactive({ name: '', acronym: '', city: '', state: '', is_active: true, _id: null });
const erros = reactive({ name: '', acronym: '', state: '' });

let buscaTimer = null;

function buscarDebounce() {
    clearTimeout(buscaTimer);
    buscaTimer = setTimeout(() => carregar(1), 400);
}

function limparBusca() { busca.value = ''; carregar(1); }

function limparForm() {
    Object.assign(form,  { name: '', acronym: '', city: '', state: '', is_active: true, _id: null });
    Object.assign(erros, { name: '', acronym: '', state: '' });
}

function abrirNova() { editando.value = false; limparForm(); dialog.value = true; }

function abrirEdicao(u) {
    editando.value = true; limparForm();
    Object.assign(form, { _id: u.id, name: u.name, acronym: u.acronym ?? '', city: u.city ?? '', state: u.state ?? '', is_active: u.is_active });
    dialog.value = true;
}

async function salvar() {
    Object.assign(erros, { name: '', acronym: '', state: '' });
    salvando.value = true;
    try {
        if (editando.value) await api.put(`/super-admin/universidades/${form._id}`, form);
        else                await api.post('/super-admin/universidades', form);
        toast.add({ severity: 'success', summary: editando.value ? 'Universidade atualizada!' : 'Universidade cadastrada!', life: 2500 });
        dialog.value = false;
        carregar(paginacao.value.current_page);
    } catch (err) {
        const e = err.response?.data?.errors ?? {};
        Object.keys(e).forEach(k => { if (k in erros) erros[k] = e[k][0]; });
        if (!Object.values(erros).some(Boolean))
            toast.add({ severity: 'error', summary: 'Erro', detail: err.response?.data?.message ?? 'Erro ao salvar.', life: 3000 });
    } finally { salvando.value = false; }
}

function confirmarExcluir(u) {
    confirm.require({
        message: `Deseja excluir a universidade "${u.name}"? Esta ação não pode ser desfeita.`,
        header: 'Confirmar exclusão',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'Sim, excluir',
        rejectLabel: 'Cancelar',
        acceptClass: 'p-button-danger',
        accept: async () => {
            try {
                await api.delete(`/super-admin/universidades/${u.id}`);
                toast.add({ severity: 'info', summary: 'Universidade excluída.', life: 2500 });
                carregar(paginacao.value.current_page);
            } catch (err) {
                toast.add({ severity: 'error', summary: 'Não foi possível excluir', detail: err.response?.data?.message ?? 'Erro.', life: 4000 });
            }
        },
    });
}

async function carregar(page = 1) {
    loading.value = true;
    try {
        const params = { page, per_page: 15 };
        if (busca.value.trim()) params.search = busca.value.trim();
        const { data } = await api.get('/super-admin/universidades', { params });
        universidades.value = data.data;
        paginacao.value = { current_page: data.current_page, last_page: data.last_page, per_page: data.per_page, total: data.total };
    } finally { loading.value = false; }
}

onMounted(() => carregar());
</script>