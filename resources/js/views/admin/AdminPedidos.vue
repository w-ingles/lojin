<template>
    <div class="grid">
        <div class="col-12">
            <div class="card">
                <Toolbar class="mb-4">
                    <template #start>
                        <h5 class="m-0 mr-3">Pedidos</h5>
                        <div class="flex gap-2 flex-wrap">
                            <Button v-for="op in statusOpts" :key="op.value"
                                :label="op.label" size="small"
                                :class="['p-button-sm', filtro === op.value ? '' : 'p-button-outlined']"
                                :severity="op.severity" @click="filtrar(op.value)" />
                        </div>
                    </template>
                    <template #end>
                        <Button icon="pi pi-refresh" class="p-button-text" :loading="loading" @click="carregar" />
                    </template>
                </Toolbar>

                <DataTable :value="pedidos.data" :loading="loading" responsiveLayout="scroll" stripedRows
                    :lazy="true" :paginator="true" :rows="20" :totalRecords="pedidos.total"
                    @page="(e) => carregarPagina(e.page + 1)">
                    <template #empty><div class="text-center p-4 text-color-secondary">Nenhum pedido.</div></template>
                    <Column field="id" header="Nº" style="width:60px" />
                    <Column field="customer_name" header="Cliente" />
                    <Column field="customer_email" header="E-mail">
                        <template #body="{ data }"><span class="text-sm">{{ data.customer_email ?? '—' }}</span></template>
                    </Column>
                    <Column field="total" header="Total" style="width:110px">
                        <template #body="{ data }"><strong>R$ {{ Number(data.total).toFixed(2) }}</strong></template>
                    </Column>
                    <Column field="status" header="Status" style="width:130px">
                        <template #body="{ data }">
                            <Tag :value="statusLabel(data.status)" :severity="statusSeverity(data.status)" />
                        </template>
                    </Column>
                    <Column header="Data" style="width:130px">
                        <template #body="{ data }">{{ formatDate(data.created_at) }}</template>
                    </Column>
                    <Column header="Ações" style="width:160px">
                        <template #body="{ data }">
                            <Dropdown :modelValue="data.status" :options="mudancaStatus" optionLabel="label"
                                optionValue="value" placeholder="Alterar..." style="width:130px"
                                @change="(e) => alterarStatus(data, e.value)" />
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>
        <Toast />
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useToast } from 'primevue/usetoast';
import api from '@/service/ApiService';

const toast   = useToast();
const pedidos = ref({ data:[], total:0 });
const loading = ref(false);
const filtro  = ref('');

const statusOpts = [
    { label:'Todos',     value:'',          severity:'secondary' },
    { label:'Pendentes', value:'pending',   severity:'warning' },
    { label:'Pagos',     value:'paid',      severity:'success' },
    { label:'Cancelados',value:'cancelled', severity:'danger' },
];
const mudancaStatus = [
    { label:'Pendente',   value:'pending' },
    { label:'Pago',       value:'paid' },
    { label:'Cancelado',  value:'cancelled' },
    { label:'Reembolsado',value:'refunded' },
];

function statusLabel(s) {
    return { pending:'Pendente', paid:'Pago', cancelled:'Cancelado', refunded:'Reembolsado' }[s] ?? s;
}
function statusSeverity(s) {
    return { pending:'warning', paid:'success', cancelled:'danger', refunded:'info' }[s] ?? 'secondary';
}
function formatDate(d) {
    return new Date(d).toLocaleString('pt-BR', { day:'2-digit', month:'2-digit', hour:'2-digit', minute:'2-digit' });
}

async function carregar(page = 1) {
    loading.value = true;
    try {
        const { data } = await api.get('/admin/orders', { params: { page, status: filtro.value || undefined } });
        pedidos.value = data;
    } finally { loading.value = false; }
}

function filtrar(s) { filtro.value = s; carregar(); }
async function carregarPagina(page) { await carregar(page); }

async function alterarStatus(pedido, status) {
    try {
        await api.patch(`/admin/orders/${pedido.id}/status`, { status });
        pedido.status = status;
        toast.add({ severity:'success', summary:'Status atualizado', life:2000 });
    } catch {
        toast.add({ severity:'error', summary:'Erro', life:2000 });
    }
}

onMounted(carregar);
</script>
