<template>
    <div class="grid">
        <div class="col-12">
            <div class="card">
                <Toolbar class="mb-4">
                    <template #start>
                        <h5 class="m-0 mr-3">Atléticas</h5>
                        <span class="text-color-secondary text-sm">{{ atleticas.length }} cadastrada(s)</span>
                    </template>
                    <template #end>
                        <Button label="Nova Atlética" icon="pi pi-plus" class="p-button-success" @click="abrirNova" />
                    </template>
                </Toolbar>

                <DataTable :value="atleticas" :loading="loading" responsiveLayout="scroll" stripedRows :paginator="true" :rows="15">
                    <template #empty><div class="text-center p-4 text-color-secondary">Nenhuma atlética.</div></template>
                    <Column field="id" header="ID" style="width:60px" />
                    <Column field="name" header="Atlética" sortable />
                    <Column field="slug" header="Slug"><template #body="{ data }"><code class="text-sm">{{ data.slug }}</code></template></Column>
                    <Column header="Universidade">
                        <template #body="{ data }">
                            <span v-if="data.university">
                                {{ data.university.name }}
                                <span v-if="data.university.acronym" class="text-color-secondary text-sm ml-1">({{ data.university.acronym }})</span>
                            </span>
                            <span v-else class="text-color-secondary text-sm">—</span>
                        </template>
                    </Column>
                    <Column field="plan" header="Plano" style="width:100px">
                        <template #body="{ data }"><Tag :value="data.plan" :severity="data.plan === 'premium' ? 'warning' : 'info'" /></template>
                    </Column>
                    <Column header="Stats" style="width:130px">
                        <template #body="{ data }">
                            <span class="text-sm mr-2"><i class="pi pi-users mr-1"></i>{{ data.users_count }}</span>
                            <span class="text-sm"><i class="pi pi-list mr-1"></i>{{ data.orders_count }}</span>
                        </template>
                    </Column>
                    <Column field="is_active" header="Status" style="width:90px">
                        <template #body="{ data }">
                            <Tag :value="data.is_active ? 'Ativa' : 'Inativa'" :severity="data.is_active ? 'success' : 'danger'" />
                        </template>
                    </Column>
                    <Column header="Ações" style="width:170px">
                        <template #body="{ data }">
                            <Button icon="pi pi-sign-in" class="p-button-rounded p-button-text p-button-info mr-1"
                                v-tooltip.top="'Acessar como Admin'" :disabled="!data.is_active"
                                :loading="acessando === data.id" @click="acessar(data)" />
                            <Button icon="pi pi-users" class="p-button-rounded p-button-text p-button-secondary mr-1"
                                v-tooltip.top="'Usuários'" @click="abrirUsuarios(data)" />
                            <Button icon="pi pi-pencil" class="p-button-rounded p-button-text mr-1"
                                v-tooltip.top="'Editar'" @click="abrirEdicao(data)" />
                            <Button :icon="data.is_active ? 'pi pi-pause' : 'pi pi-play'"
                                :class="['p-button-rounded p-button-text', data.is_active ? 'p-button-warning' : 'p-button-success']"
                                @click="toggleAtivo(data)" />
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>

        <!-- Dialog criar/editar atlética -->
        <Dialog v-model:visible="dialog" :header="editando ? 'Editar Atlética' : 'Nova Atlética'" modal :style="{ width: '580px' }" :closable="!salvando">
            <div class="grid p-fluid">
                <div class="col-12 md:col-8">
                    <div class="field">
                        <label>Nome <span class="p-error">*</span></label>
                        <InputText v-model="form.name" :class="{ 'p-invalid': erros.name }" @input="gerarSlug" />
                        <small class="p-error">{{ erros.name }}</small>
                    </div>
                </div>
                <div class="col-12 md:col-4">
                    <div class="field">
                        <label>Plano</label>
                        <Dropdown v-model="form.plan" :options="[{label:'Basic',value:'basic'},{label:'Premium',value:'premium'}]"
                            optionLabel="label" optionValue="value" />
                    </div>
                </div>
                <div class="col-12 md:col-6">
                    <div class="field">
                        <label>Slug <span class="p-error">*</span></label>
                        <InputText v-model="form.slug" :class="{ 'p-invalid': erros.slug }" />
                        <small class="p-error" v-if="erros.slug">{{ erros.slug }}</small>
                        <small class="text-color-secondary" v-else>URL: /c/<strong>{{ form.slug || 'slug' }}</strong></small>
                    </div>
                </div>
                <div class="col-12 md:col-6">
                    <div class="field">
                        <label>Universidade <span class="p-error">*</span></label>
                        <Dropdown
                            v-model="form.university_id"
                            :options="universidades"
                            optionLabel="name"
                            optionValue="id"
                            placeholder="Selecione a universidade"
                            filter
                            filterPlaceholder="Buscar..."
                            :class="{ 'p-invalid': erros.university_id }"
                            class="w-full"
                        >
                            <template #option="{ option }">
                                <span>{{ option.name }}<span v-if="option.acronym" class="text-color-secondary ml-1 text-sm">({{ option.acronym }})</span></span>
                            </template>
                        </Dropdown>
                        <small class="p-error">{{ erros.university_id }}</small>
                    </div>
                </div>
                <div class="col-12 md:col-6">
                    <div class="field"><label>E-mail</label><InputText v-model="form.email" /></div>
                </div>
                <div class="col-12 md:col-6">
                    <div class="field"><label>Telefone</label><InputMask v-model="form.phone" mask="(99) 99999-9999" /></div>
                </div>
                <div class="col-12">
                    <div class="field"><label>Descrição</label><Textarea v-model="form.description" rows="2" autoResize /></div>
                </div>

                <template v-if="!editando">
                    <div class="col-12"><div class="p-3 border-round mb-1" style="background:var(--surface-ground)">
                        <span class="font-semibold text-sm"><i class="pi pi-user mr-1" style="color:#1976d2"></i>Admin Inicial</span>
                    </div></div>
                    <div class="col-12">
                        <div class="field"><label>Nome <span class="p-error">*</span></label>
                            <InputText v-model="form.admin_name" :class="{ 'p-invalid': erros.admin_name }" />
                            <small class="p-error">{{ erros.admin_name }}</small></div>
                    </div>
                    <div class="col-12 md:col-6">
                        <div class="field"><label>E-mail <span class="p-error">*</span></label>
                            <InputText v-model="form.admin_email" :class="{ 'p-invalid': erros.admin_email }" />
                            <small class="p-error">{{ erros.admin_email }}</small></div>
                    </div>
                    <div class="col-12 md:col-6">
                        <div class="field mb-0"><label>Senha <span class="p-error">*</span></label>
                            <Password v-model="form.admin_password" :class="{ 'p-invalid': erros.admin_password }" :feedback="false" toggleMask />
                            <small class="p-error">{{ erros.admin_password }}</small></div>
                    </div>
                </template>
            </div>
            <template #footer>
                <Button label="Cancelar" class="p-button-text" :disabled="salvando" @click="dialog = false" />
                <Button :label="editando ? 'Salvar' : 'Criar Atlética'" icon="pi pi-check" class="p-button-success" :loading="salvando" @click="salvar" />
            </template>
        </Dialog>

        <!-- Dialog usuários -->
        <Dialog v-model:visible="dialogUsuarios" :header="`Usuários — ${atleticaSelecionada?.name ?? ''}`" modal :style="{ width:'640px' }">
            <div class="flex justify-content-between align-items-center mb-3">
                <span class="text-color-secondary text-sm">{{ usuarios.length }} usuário(s)</span>
                <Button label="Novo Usuário" icon="pi pi-plus" size="small" class="p-button-success" @click="abrirNovoUsuario" />
            </div>
            <DataTable :value="usuarios" :loading="carregandoUsuarios" responsiveLayout="scroll" stripedRows size="small">
                <template #empty><div class="text-center py-4 text-color-secondary">Nenhum usuário.</div></template>
                <Column field="name" header="Nome" />
                <Column field="email" header="E-mail" />
                <Column field="role" header="Perfil" style="width:100px">
                    <template #body="{ data }"><Tag :value="data.role === 'admin' ? 'Admin' : 'Usuário'" :severity="data.role === 'admin' ? 'info' : 'secondary'" /></template>
                </Column>
                <Column style="width:50px">
                    <template #body="{ data }">
                        <Button icon="pi pi-trash" class="p-button-rounded p-button-text p-button-danger" size="small" @click="confirmarExcluirUser(data)" />
                    </template>
                </Column>
            </DataTable>
            <div v-if="formUser.visivel" class="mt-4 p-3 border-round" style="background:var(--surface-ground)">
                <h6 class="mt-0 mb-3"><i class="pi pi-user-plus mr-2" style="color:#1976d2"></i>Novo Usuário</h6>
                <div class="grid p-fluid">
                    <div class="col-12 md:col-6"><div class="field"><label>Nome</label><InputText v-model="formUser.name" /></div></div>
                    <div class="col-12 md:col-6"><div class="field"><label>Perfil</label>
                        <Dropdown v-model="formUser.role" :options="[{label:'Admin',value:'admin'},{label:'Usuário',value:'user'}]" optionLabel="label" optionValue="value" /></div></div>
                    <div class="col-12 md:col-6"><div class="field"><label>E-mail</label><InputText v-model="formUser.email" /></div></div>
                    <div class="col-12 md:col-6"><div class="field mb-0"><label>Senha</label><Password v-model="formUser.password" :feedback="false" toggleMask /></div></div>
                </div>
                <div class="flex justify-content-end gap-2 mt-3">
                    <Button label="Cancelar" class="p-button-text" size="small" @click="formUser.visivel = false" />
                    <Button label="Criar" icon="pi pi-check" class="p-button-success" size="small" :loading="salvandoUser" @click="criarUsuario" />
                </div>
            </div>
            <template #footer>
                <Button label="Fechar" class="p-button-text" @click="dialogUsuarios = false" />
            </template>
        </Dialog>

        <ConfirmDialog /><Toast />
    </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import { useRoute } from 'vue-router';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import api from '@/service/ApiService';

const route   = useRoute();
const confirm = useConfirm();
const toast   = useToast();

const atleticas     = ref([]); const loading = ref(false);
const universidades = ref([]);
const dialog = ref(false); const editando = ref(false); const salvando = ref(false);
const acessando = ref(null);
const dialogUsuarios = ref(false); const atleticaSelecionada = ref(null);
const usuarios = ref([]); const carregandoUsuarios = ref(false); const salvandoUser = ref(false);

const form  = reactive({ name:'', slug:'', email:'', phone:'', university_id:null, description:'', plan:'basic', admin_name:'', admin_email:'', admin_password:'', _id:null });
const erros = reactive({ name:'', slug:'', university_id:'', admin_name:'', admin_email:'', admin_password:'' });
const formUser = reactive({ visivel:false, name:'', email:'', password:'', role:'admin' });

function gerarSlug() {
    if (!editando.value) form.slug = form.name.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g,'').replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,'');
}
function limpar() {
    Object.assign(form, { name:'', slug:'', email:'', phone:'', university_id:null, description:'', plan:'basic', admin_name:'', admin_email:'', admin_password:'', _id:null });
    Object.assign(erros, { name:'', slug:'', university_id:'', admin_name:'', admin_email:'', admin_password:'' });
}
function abrirNova()  { editando.value = false; limpar(); dialog.value = true; }
function abrirEdicao(a) {
    editando.value = true; limpar();
    Object.assign(form, { _id:a.id, name:a.name, slug:a.slug, email:a.email??'', phone:a.phone??'', university_id:a.university_id??null, description:a.description??'', plan:a.plan });
    dialog.value = true;
}

async function salvar() {
    Object.assign(erros, { name:'', slug:'', university_id:'', admin_name:'', admin_email:'', admin_password:'' });
    salvando.value = true;
    try {
        if (editando.value) await api.put(`/super-admin/atleticas/${form._id}`, form);
        else                await api.post('/super-admin/atleticas', form);
        toast.add({ severity:'success', summary: editando.value ? 'Atualizado!' : 'Atlética criada!', life:2000 });
        dialog.value = false; carregar();
    } catch (err) {
        const e = err.response?.data?.errors ?? {};
        Object.keys(e).forEach(k => { if (k in erros) erros[k] = e[k][0]; });
        if (!Object.values(erros).some(Boolean)) toast.add({ severity:'error', summary:'Erro', detail:err.response?.data?.message??'Erro.', life:3000 });
    } finally { salvando.value = false; }
}

async function toggleAtivo(a) {
    try { const { data } = await api.patch(`/super-admin/atleticas/${a.id}/toggle`); a.is_active = data.is_active; toast.add({ severity:'info', summary: data.is_active ? 'Ativada' : 'Desativada', life:2000 }); }
    catch { toast.add({ severity:'error', summary:'Erro', life:2000 }); }
}

async function acessar(a) {
    acessando.value = a.id;
    try {
        const { data } = await api.post(`/super-admin/atleticas/${a.id}/impersonate`);
        localStorage.setItem('auth_token', data.token); localStorage.setItem('auth_user', JSON.stringify(data.user));
        if (data.user.tenant?.slug) localStorage.setItem('idealfood_tenant_slug', data.user.tenant.slug);
        window.location.href = '/';
    } catch (err) { toast.add({ severity:'error', summary:'Erro', detail:err.response?.data?.message??'Falha.', life:3000 }); }
    finally { acessando.value = null; }
}

async function abrirUsuarios(a) {
    atleticaSelecionada.value = a; formUser.visivel = false; dialogUsuarios.value = true;
    carregandoUsuarios.value = true;
    try { const { data } = await api.get(`/super-admin/atleticas/${a.id}/users`); usuarios.value = data; }
    finally { carregandoUsuarios.value = false; }
}
function abrirNovoUsuario() { Object.assign(formUser, { name:'', email:'', password:'', role:'admin' }); formUser.visivel = true; }
async function criarUsuario() {
    salvandoUser.value = true;
    try {
        const { data } = await api.post(`/super-admin/atleticas/${atleticaSelecionada.value.id}/users`, formUser);
        usuarios.value.unshift(data); formUser.visivel = false;
        toast.add({ severity:'success', summary:'Usuário criado!', life:2000 });
    } catch (err) { toast.add({ severity:'error', summary:'Erro', detail:err.response?.data?.message??'Erro.', life:3000 }); }
    finally { salvandoUser.value = false; }
}
function confirmarExcluirUser(u) {
    confirm.require({
        message:`Excluir "${u.name}"?`, header:'Confirmar', icon:'pi pi-exclamation-triangle', acceptClass:'p-button-danger',
        accept: async () => {
            await api.delete(`/super-admin/atleticas/${atleticaSelecionada.value.id}/users/${u.id}`);
            usuarios.value = usuarios.value.filter(x => x.id !== u.id);
            toast.add({ severity:'info', summary:'Excluído', life:2000 });
        },
    });
}

async function carregar() {
    loading.value = true;
    try { const { data } = await api.get('/super-admin/atleticas'); atleticas.value = data; }
    finally { loading.value = false; }
}

async function carregarUniversidades() {
    try { const { data } = await api.get('/super-admin/universidades', { params: { per_page: 200 } }); universidades.value = data.data; }
    catch {}
}

onMounted(() => { carregar(); carregarUniversidades(); if (route.query.novo) abrirNova(); });
</script>
