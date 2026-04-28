<template>
    <div class="grid">
        <div class="col-12">
            <div class="card">
                <Toolbar class="mb-4">
                    <template #start><h5 class="m-0 mr-3">Produtos</h5></template>
                    <template #end>
                        <Button label="Nova Categoria" icon="pi pi-plus" class="p-button-outlined mr-2" @click="abrirCategoria" />
                        <Button label="Novo Produto" icon="pi pi-plus" class="p-button-success" @click="abrirNovo" />
                    </template>
                </Toolbar>

                <DataTable :value="produtos" :loading="loading" responsiveLayout="scroll" stripedRows :paginator="true" :rows="15">
                    <template #empty><div class="text-center p-4 text-color-secondary">Nenhum produto.</div></template>
                    <Column header="Imagem" style="width:80px">
                        <template #body="{ data }">
                            <img v-if="data.image_url" :src="data.image_url" style="width:50px;height:50px;object-fit:cover;border-radius:6px" />
                            <i v-else class="pi pi-image" style="font-size:1.8rem;color:#ccc" />
                        </template>
                    </Column>
                    <Column field="name" header="Nome" sortable />
                    <Column header="Categoria">
                        <template #body="{ data }">
                            <Tag v-if="data.category" :value="data.category.name" severity="info" />
                            <span v-else class="text-color-secondary">—</span>
                        </template>
                    </Column>
                    <Column field="price" header="Preço" style="width:110px">
                        <template #body="{ data }"><strong>R$ {{ Number(data.price).toFixed(2) }}</strong></template>
                    </Column>
                    <Column field="stock" header="Estoque" style="width:90px" />
                    <Column field="active" header="Status" style="width:90px">
                        <template #body="{ data }">
                            <Tag :value="data.active ? 'Ativo' : 'Inativo'" :severity="data.active ? 'success' : 'danger'" />
                        </template>
                    </Column>
                    <Column header="Ações" style="width:100px">
                        <template #body="{ data }">
                            <Button icon="pi pi-pencil" class="p-button-rounded p-button-text mr-1" @click="abrirEdicao(data)" />
                            <Button icon="pi pi-trash" class="p-button-rounded p-button-text p-button-danger" @click="excluir(data)" />
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>

        <!-- Dialog Produto -->
        <Dialog v-model:visible="dialog" :header="editando ? 'Editar Produto' : 'Novo Produto'" modal :style="{ width: '520px' }" :closable="!salvando">
            <div class="grid p-fluid">
                <div class="col-12">
                    <div class="field">
                        <label>Nome <span class="p-error">*</span></label>
                        <InputText v-model="form.name" :class="{ 'p-invalid': erros.name }" autofocus />
                        <small class="p-error">{{ erros.name }}</small>
                    </div>
                </div>
                <div class="col-12 md:col-6">
                    <div class="field">
                        <label>Categoria</label>
                        <div class="flex gap-2">
                            <Dropdown v-model="form.product_category_id" :options="categorias" optionLabel="name" optionValue="id"
                                placeholder="Selecione" showClear class="flex-1" />
                            <Button icon="pi pi-plus" class="p-button-outlined" v-tooltip.top="'Nova categoria'" @click="abrirCategoria" />
                        </div>
                    </div>
                </div>
                <div class="col-12 md:col-6">
                    <div class="field">
                        <label>Preço <span class="p-error">*</span></label>
                        <InputNumber v-model="form.price" mode="currency" currency="BRL" locale="pt-BR" :class="{ 'p-invalid': erros.price }" />
                        <small class="p-error">{{ erros.price }}</small>
                    </div>
                </div>
                <div class="col-12 md:col-6">
                    <div class="field">
                        <label>Estoque</label>
                        <InputNumber v-model="form.stock" :min="0" />
                    </div>
                </div>
                <div class="col-12 md:col-6">
                    <div class="field">
                        <label>Status</label>
                        <div class="flex align-items-center gap-2 mt-2">
                            <InputSwitch v-model="form.active" inputId="ativo" />
                            <label for="ativo" class="mb-0">{{ form.active ? 'Ativo' : 'Inativo' }}</label>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="field">
                        <label>Descrição</label>
                        <Textarea v-model="form.description" rows="2" autoResize />
                    </div>
                </div>
                <div class="col-12">
                    <div class="field mb-0">
                        <label>Imagem</label>
                        <input type="file" accept="image/*" @change="onFile" class="block w-full mt-1" />
                        <img v-if="preview" :src="preview" class="mt-2 border-round shadow-2" style="width:90px;height:90px;object-fit:cover" />
                    </div>
                </div>
            </div>
            <template #footer>
                <Button label="Cancelar" icon="pi pi-times" class="p-button-text" @click="dialog = false" :disabled="salvando" />
                <Button :label="editando ? 'Salvar' : 'Criar'" icon="pi pi-check" class="p-button-success" :loading="salvando" @click="salvar" />
            </template>
        </Dialog>

        <!-- Dialog Categoria -->
        <Dialog v-model:visible="dialogCat" header="Nova Categoria" modal :style="{ width: '360px' }">
            <div class="p-fluid">
                <div class="field mb-0">
                    <label>Nome <span class="p-error">*</span></label>
                    <InputText v-model="novaCategoria" placeholder="Ex: Camisetas, Acessórios..." @keyup.enter="salvarCategoria" />
                </div>
            </div>
            <template #footer>
                <Button label="Cancelar" class="p-button-text" @click="dialogCat = false" />
                <Button label="Criar" icon="pi pi-check" class="p-button-success" :loading="salvandoCat" @click="salvarCategoria" />
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

const confirm = useConfirm(); const toast = useToast();
const produtos = ref([]); const categorias = ref([]);
const loading = ref(false); const dialog = ref(false);
const editando = ref(false); const salvando = ref(false);
const preview = ref(null); const fileRef = ref(null);
const dialogCat = ref(false); const novaCategoria = ref(''); const salvandoCat = ref(false);

const form  = reactive({ id:null, name:'', product_category_id:null, description:'', price:null, stock:0, active:true });
const erros = reactive({ name:'', price:'' });

function limpar() {
    Object.assign(form, { id:null, name:'', product_category_id:null, description:'', price:null, stock:0, active:true });
    Object.assign(erros, { name:'', price:'' });
    preview.value = null; fileRef.value = null;
}

function abrirNovo() { editando.value = false; limpar(); dialog.value = true; }
function abrirEdicao(p) {
    editando.value = true; limpar();
    Object.assign(form, { id:p.id, name:p.name, product_category_id:p.product_category_id, description:p.description||'', price:Number(p.price), stock:p.stock, active:p.active });
    preview.value = p.image_url; dialog.value = true;
}
function abrirCategoria() { novaCategoria.value = ''; dialogCat.value = true; }
function onFile(e) { fileRef.value = e.target.files[0]; if (fileRef.value) preview.value = URL.createObjectURL(fileRef.value); }

function validar() {
    erros.name  = form.name.trim() ? '' : 'Nome obrigatório.';
    erros.price = (form.price > 0) ? '' : 'Preço inválido.';
    return !erros.name && !erros.price;
}

async function salvar() {
    if (!validar()) return;
    salvando.value = true;
    try {
        const fd = new FormData();
        fd.append('name', form.name); fd.append('price', form.price); fd.append('stock', form.stock);
        fd.append('active', form.active ? 1 : 0);
        if (form.product_category_id) fd.append('product_category_id', form.product_category_id);
        if (form.description) fd.append('description', form.description);
        if (fileRef.value) fd.append('image', fileRef.value);
        const opts = { headers: { 'Content-Type': 'multipart/form-data' } };
        if (editando.value) {
            fd.append('_method', 'PUT'); // Laravel method spoofing para FormData com arquivo
            await api.post(`/admin/products/${form.id}`, fd, opts);
        } else {
            await api.post('/admin/products', fd, opts);
        }
        toast.add({ severity:'success', summary: editando.value ? 'Produto atualizado!' : 'Produto criado!', life:2000 });
        dialog.value = false; carregar();
    } catch (err) {
        toast.add({ severity:'error', summary:'Erro', detail:err.response?.data?.message||'Erro ao salvar.', life:3000 });
    } finally { salvando.value = false; }
}

async function salvarCategoria() {
    if (!novaCategoria.value.trim()) return;
    salvandoCat.value = true;
    try {
        const { data } = await api.post('/admin/categories', { name: novaCategoria.value.trim() });
        categorias.value.push(data);
        form.product_category_id = data.id;
        dialogCat.value = false;
        toast.add({ severity:'success', summary:'Categoria criada!', life:2000 });
    } finally { salvandoCat.value = false; }
}

function excluir(p) {
    confirm.require({
        message:`Excluir "${p.name}"?`, header:'Confirmar', icon:'pi pi-exclamation-triangle',
        acceptClass:'p-button-danger',
        accept: async () => { await api.delete(`/admin/products/${p.id}`); carregar(); toast.add({ severity:'info', summary:'Excluído', life:2000 }); },
    });
}

async function carregar() {
    loading.value = true;
    try {
        const [p, c] = await Promise.all([api.get('/admin/products'), api.get('/admin/categories')]);
        produtos.value = p.data; categorias.value = c.data;
    } finally { loading.value = false; }
}

onMounted(carregar);
</script>
