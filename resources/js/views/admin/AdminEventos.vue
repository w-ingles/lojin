<template>
    <div class="grid">
        <div class="col-12">
            <div class="card">
                <Toolbar class="mb-4">
                    <template #start>
                        <h5 class="m-0 mr-3">Eventos</h5>
                    </template>
                    <template #end>
                        <router-link to="/admin/eventos/novo">
                            <Button label="Novo Evento" icon="pi pi-plus" class="p-button-success" />
                        </router-link>
                    </template>
                </Toolbar>

                <DataTable :value="eventos" :loading="loading" responsiveLayout="scroll" stripedRows
                    :paginator="true" :rows="10">
                    <template #empty><div class="text-center p-4 text-color-secondary">Nenhum evento.</div></template>

                    <Column header="Banner" style="width:80px">
                        <template #body="{ data }">
                            <img v-if="data.banner_url" :src="data.banner_url" style="width:60px;height:40px;object-fit:cover;border-radius:4px" />
                            <i v-else class="pi pi-calendar" style="font-size:1.8rem;color:#ccc" />
                        </template>
                    </Column>
                    <Column field="name" header="Nome" sortable />
                    <Column header="Data" style="width:160px">
                        <template #body="{ data }">{{ formatDate(data.starts_at) }}</template>
                    </Column>
                    <Column header="Ingressos" style="width:130px">
                        <template #body="{ data }">
                            <span>{{ data.total_sold }}/{{ data.total_capacity }}</span>
                        </template>
                    </Column>
                    <Column field="status" header="Status" style="width:160px">
                        <template #body="{ data }">
                            <div class="flex flex-column gap-1">
                                <Tag :value="statusLabel(data.status)" :severity="statusSeverity(data.status)" />
                                <small v-if="data.status === 'draft'" class="text-color-secondary" style="font-size:.72rem">
                                    <i class="pi pi-eye-slash mr-1"></i>Invisível na loja
                                </small>
                            </div>
                        </template>
                    </Column>
                    <Column header="Ações" style="width:120px">
                        <template #body="{ data }">
                            <router-link :to="`/admin/eventos/${data.id}`">
                                <Button icon="pi pi-pencil" class="p-button-rounded p-button-text mr-1" v-tooltip="'Editar'" />
                            </router-link>
                            <Button icon="pi pi-trash" class="p-button-rounded p-button-text p-button-danger"
                                v-tooltip="'Excluir'" @click="excluir(data)" />
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>
        <ConfirmDialog />
        <Toast />
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import api from '@/service/ApiService';

const confirm = useConfirm();
const toast   = useToast();
const eventos = ref([]);
const loading = ref(false);

function statusLabel(s) {
    return { draft:'Rascunho', active:'Ativo', sold_out:'Esgotado', finished:'Encerrado', cancelled:'Cancelado' }[s] ?? s;
}
function statusSeverity(s) {
    return { active:'success', sold_out:'warning', finished:'secondary', cancelled:'danger', draft:'info' }[s] ?? 'secondary';
}
function formatDate(d) {
    return new Date(d).toLocaleString('pt-BR', { day:'2-digit', month:'2-digit', year:'numeric', hour:'2-digit', minute:'2-digit' });
}

async function carregar() {
    loading.value = true;
    try { const { data } = await api.get('/admin/events'); eventos.value = data; }
    finally { loading.value = false; }
}

function excluir(evento) {
    confirm.require({
        message: `Excluir "${evento.name}"?`,
        header: 'Confirmar exclusão',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: async () => {
            await api.delete(`/admin/events/${evento.id}`);
            toast.add({ severity:'info', summary:'Evento excluído', life:2000 });
            carregar();
        },
    });
}

onMounted(carregar);
</script>
