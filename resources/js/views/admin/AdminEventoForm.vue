<template>
    <div>
        <div class="flex align-items-center gap-2 mb-4">
            <Button icon="pi pi-arrow-left" class="p-button-text" @click="$router.back()" />
            <h3 class="m-0">{{ editando ? 'Editar Evento' : 'Novo Evento' }}</h3>
        </div>

        <div class="grid">
            <div class="col-12 lg:col-8">
                <div class="card">
                    <h5 class="mt-0">Informações do Evento</h5>
                    <div class="grid p-fluid">
                        <div class="col-12">
                            <div class="field">
                                <label>Nome <span class="p-error">*</span></label>
                                <InputText v-model="form.name" :class="{ 'p-invalid': erros.name }" />
                                <small class="p-error">{{ erros.name }}</small>
                            </div>
                        </div>
                        <div class="col-12 md:col-6">
                            <div class="field">
                                <label>Data e Hora <span class="p-error">*</span></label>
                                <Calendar v-model="form.starts_at" showTime hourFormat="24" dateFormat="dd/mm/yy"
                                    :class="{ 'p-invalid': erros.starts_at }" />
                                <small class="p-error">{{ erros.starts_at }}</small>
                            </div>
                        </div>
                        <div class="col-12 md:col-6">
                            <div class="field">
                                <label>Status</label>
                                <Dropdown v-model="form.status" :options="statusOpts" optionLabel="label" optionValue="value" />
                            </div>
                        </div>
                        <div class="col-12 md:col-6">
                            <div class="field">
                                <label>Local</label>
                                <InputText v-model="form.location" placeholder="Nome do local" />
                            </div>
                        </div>
                        <div class="col-12 md:col-6">
                            <div class="field">
                                <label>Idade mínima</label>
                                <InputNumber v-model="form.minimum_age" placeholder="18" :min="0" :max="99" />
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="field">
                                <label>Endereço</label>
                                <InputText v-model="form.address" placeholder="Endereço completo" />
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="field">
                                <label>Descrição</label>
                                <Textarea v-model="form.description" rows="4" autoResize />
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="field mb-0">
                                <label>Banner</label>
                                <input type="file" accept="image/*" @change="onBanner" class="block w-full mt-1" />
                                <img v-if="preview" :src="preview" class="mt-2 border-round" style="max-height:160px;object-fit:cover" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Lotes de ingresso -->
                <div class="card mt-3">
                    <div class="flex align-items-center justify-content-between mb-3">
                        <h5 class="m-0">Lotes de Ingressos</h5>
                        <Button label="Adicionar Lote" icon="pi pi-plus" size="small" class="p-button-outlined" @click="adicionarLote" />
                    </div>

                    <div v-if="lotes.length === 0" class="text-center text-color-secondary py-3">
                        Nenhum lote. Adicione após salvar o evento.
                    </div>

                    <div v-for="(lote, idx) in lotes" :key="idx" class="p-3 border-round mb-3" style="background:var(--surface-ground)">
                        <div class="grid p-fluid">
                            <div class="col-12 md:col-5">
                                <div class="field mb-2">
                                    <label class="text-sm">Nome do lote</label>
                                    <InputText v-model="lote.name" placeholder="Ex: 1º Lote, Pista..." size="small" />
                                </div>
                            </div>
                            <div class="col-6 md:col-3">
                                <div class="field mb-2">
                                    <label class="text-sm">Preço (R$)</label>
                                    <InputNumber v-model="lote.price" mode="currency" currency="BRL" locale="pt-BR" />
                                </div>
                            </div>
                            <div class="col-6 md:col-3">
                                <div class="field mb-2">
                                    <label class="text-sm">Quantidade</label>
                                    <InputNumber v-model="lote.quantity" :min="1" />
                                </div>
                            </div>
                            <div class="col-12 md:col-1 flex align-items-center justify-content-center">
                                <Button icon="pi pi-trash" class="p-button-text p-button-danger p-button-sm"
                                    @click="lotes.splice(idx, 1)" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 lg:col-4">
                <div class="card" style="position:sticky;top:80px">
                    <Button :label="salvando ? 'Salvando...' : (editando ? 'Salvar Alterações' : 'Criar Evento')"
                        icon="pi pi-check" class="p-button-success w-full" :loading="salvando" @click="salvar" />
                    <Button label="Cancelar" icon="pi pi-times" class="p-button-text w-full mt-2" @click="$router.back()" />
                </div>
            </div>
        </div>
        <Toast />
    </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import api from '@/service/ApiService';

const route  = useRoute();
const router = useRouter();
const toast  = useToast();

const editando = !!route.params.id;
const salvando = ref(false);
const preview  = ref(null);
const bannerFile = ref(null);
const lotes    = ref([]);

const form  = reactive({ name:'', description:'', location:'', address:'', starts_at:null, status:'active', minimum_age:null });
const erros = reactive({ name:'', starts_at:'' });

const statusOpts = [
    { label:'Rascunho', value:'draft' },
    { label:'Ativo',    value:'active' },
    { label:'Encerrado',value:'finished' },
    { label:'Cancelado',value:'cancelled' },
];

function adicionarLote() {
    lotes.value.push({ name:'', price:0, quantity:100 });
}

function onBanner(e) {
    bannerFile.value = e.target.files[0];
    if (bannerFile.value) preview.value = URL.createObjectURL(bannerFile.value);
}

async function salvar() {
    erros.name     = form.name.trim() ? '' : 'Nome obrigatório.';
    erros.starts_at = form.starts_at ? '' : 'Data obrigatória.';
    if (erros.name || erros.starts_at) return;

    salvando.value = true;
    try {
        const fd = new FormData();
        Object.entries(form).forEach(([k,v]) => { if (v !== null && v !== '') fd.append(k, v instanceof Date ? v.toISOString() : v); });
        if (bannerFile.value) fd.append('banner', bannerFile.value);

        let evento;
        if (editando) {
            fd.append('_method', 'PUT'); // Laravel method spoofing: FormData não suporta PUT nativo
            const { data } = await api.post(`/admin/events/${route.params.id}`, fd, { headers:{'Content-Type':'multipart/form-data'} });
            evento = data;
        } else {
            const { data } = await api.post('/admin/events', fd, { headers:{'Content-Type':'multipart/form-data'} });
            evento = data;
            // Criar lotes
            for (const lote of lotes.value) {
                if (lote.name) await api.post(`/admin/events/${evento.id}/batches`, lote);
            }
        }

        toast.add({ severity:'success', summary: editando ? 'Evento atualizado!' : 'Evento criado!', life:2000 });
        setTimeout(() => router.push('/admin/eventos'), 500);
    } catch (err) {
        toast.add({ severity:'error', summary:'Erro', detail:err.response?.data?.message || 'Erro ao salvar.', life:3000 });
    } finally { salvando.value = false; }
}

onMounted(async () => {
    if (editando) {
        const { data } = await api.get(`/admin/events/${route.params.id}`);
        Object.assign(form, { ...data, starts_at: data.starts_at ? new Date(data.starts_at) : null });
        preview.value  = data.banner_url;
        lotes.value    = data.ticket_batches || [];
    }
});
</script>
