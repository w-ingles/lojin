<template>
    <div class="layout-dashboard">
        <div class="grid">
            <div class="col-12">
                <div class="card card-w-title">
                    <Toolbar class="mb-4">
                        <template #start><h5 class="m-0 mr-3">Relatórios</h5></template>
                        <template #end>
                            <Button label="Exportar CSV" icon="pi pi-download" class="p-button-outlined mr-2" :loading="exportando" @click="exportar" />
                            <Button label="Imprimir" icon="pi pi-print" class="p-button-secondary" @click="imprimir" />
                        </template>
                    </Toolbar>

                    <!-- Filtros -->
                    <div class="p-3 border-round mb-4" style="background:var(--surface-ground)">
                        <div class="grid p-fluid align-items-end">
                            <div class="col-12 md:col-3">
                                <div class="field mb-0">
                                    <label class="text-sm font-medium">Data Inicial</label>
                                    <Calendar v-model="filtros.inicio" dateFormat="dd/mm/yy" showIcon />
                                </div>
                            </div>
                            <div class="col-12 md:col-3">
                                <div class="field mb-0">
                                    <label class="text-sm font-medium">Data Final</label>
                                    <Calendar v-model="filtros.fim" dateFormat="dd/mm/yy" showIcon />
                                </div>
                            </div>
                            <div class="col-12 md:col-3">
                                <div class="flex gap-2 flex-wrap">
                                    <Button v-for="p in periodos" :key="p.label" :label="p.label" size="small"
                                        :class="['p-button-sm', periodoAtivo === p.label ? '' : 'p-button-outlined']"
                                        @click="setPeriodo(p)" />
                                </div>
                            </div>
                            <div class="col-12 md:col-3">
                                <Button label="Aplicar" icon="pi pi-filter" :loading="loading" @click="carregar" />
                            </div>
                        </div>
                    </div>

                    <!-- KPIs -->
                    <div v-if="!loading" class="grid mb-4">
                        <div class="col-12 lg:col-6 xl:col-3">
                            <div class="overview-box sales">
                                <i class="overview-icon pi pi-list"></i>
                                <span class="overview-title">Pedidos</span>
                                <i class="overview-arrow pi pi-chevron-circle-up"></i>
                                <div class="overview-numbers">{{ resumo.totais?.pedidos ?? 0 }}</div>
                                <div class="overview-subinfo">no período</div>
                            </div>
                        </div>
                        <div class="col-12 lg:col-6 xl:col-3">
                            <div class="overview-box checkin">
                                <i class="overview-icon pi pi-dollar"></i>
                                <span class="overview-title">Faturamento</span>
                                <i class="overview-arrow pi pi-chevron-circle-up"></i>
                                <div class="overview-numbers">R$ {{ Number(resumo.totais?.faturamento ?? 0).toFixed(2) }}</div>
                                <div class="overview-subinfo">total pago</div>
                            </div>
                        </div>
                        <div class="col-12 lg:col-6 xl:col-3">
                            <div class="overview-box views">
                                <i class="overview-icon pi pi-ticket"></i>
                                <span class="overview-title">Ticket Médio</span>
                                <i class="overview-arrow pi pi-chevron-circle-right"></i>
                                <div class="overview-numbers">R$ {{ Number(resumo.totais?.ticket_medio ?? 0).toFixed(2) }}</div>
                                <div class="overview-subinfo">por pedido</div>
                            </div>
                        </div>
                        <div class="col-12 lg:col-6 xl:col-3">
                            <div class="overview-box users">
                                <i class="overview-icon pi pi-check-circle"></i>
                                <span class="overview-title">Pagos</span>
                                <i class="overview-arrow pi pi-chevron-circle-up"></i>
                                <div class="overview-numbers">{{ resumo.totais?.pagos ?? 0 }}</div>
                                <div class="overview-subinfo">pedidos confirmados</div>
                            </div>
                        </div>
                    </div>

                    <!-- Gráfico -->
                    <div v-if="chartData.labels?.length" class="card card-w-title mb-4">
                        <h5>Faturamento por Dia</h5>
                        <Chart type="bar" :data="chartData" :options="chartOpts" style="height:280px" />
                    </div>
                </div>
            </div>
        </div>
        <Toast />
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useToast } from 'primevue/usetoast';
import api from '@/service/ApiService';

const toast      = useToast();
const loading    = ref(false);
const exportando = ref(false);
const periodoAtivo = ref('Mês');
const resumo     = ref({ totais: {}, por_dia: [] });

const filtros = reactive({ inicio: primeiroDiaMes(), fim: new Date() });

const periodos = [
    { label: 'Hoje',   dias: 0  },
    { label: '7 dias', dias: 6  },
    { label: 'Mês',    dias: -1 },
];

function primeiroDiaMes() { const d = new Date(); d.setDate(1); d.setHours(0,0,0,0); return d; }
function isoDate(d) { return d instanceof Date ? d.toISOString().split('T')[0] : null; }

function setPeriodo(p) {
    periodoAtivo.value = p.label;
    const hoje = new Date();
    filtros.fim = new Date(hoje);
    if (p.dias === -1) { filtros.inicio = primeiroDiaMes(); }
    else { const ini = new Date(hoje); ini.setDate(ini.getDate() - p.dias); ini.setHours(0,0,0,0); filtros.inicio = ini; }
    carregar();
}

async function carregar() {
    loading.value = true;
    try {
        const { data } = await api.get('/admin/reports/sales', { params: { inicio: isoDate(filtros.inicio), fim: isoDate(filtros.fim) } });
        resumo.value = data;
    } finally { loading.value = false; }
}

async function exportar() {
    exportando.value = true;
    try {
        const { data } = await api.get('/admin/reports/export', {
            params: { inicio: isoDate(filtros.inicio), fim: isoDate(filtros.fim) },
            responseType: 'blob',
        });
        const url = URL.createObjectURL(new Blob([data]));
        const a = document.createElement('a'); a.href = url; a.download = 'vendas.csv'; a.click();
        URL.revokeObjectURL(url);
    } catch { toast.add({ severity:'error', summary:'Erro', life:2000 }); }
    finally { exportando.value = false; }
}

function imprimir() { window.print(); }

const chartData = computed(() => ({
    labels: resumo.value.por_dia?.map(d => d.data) ?? [],
    datasets: [{
        label: 'Faturamento (R$)',
        data: resumo.value.por_dia?.map(d => d.faturamento) ?? [],
        backgroundColor: 'rgba(25,118,210,.25)',
        borderColor: '#1976d2', borderWidth: 2, tension: .4, fill: true,
    }],
}));

const chartOpts = {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { labels: { color: '#495057' } } },
    scales: {
        x: { ticks: { color: '#6c757d' } },
        y: { ticks: { color: '#6c757d', callback: v => 'R$ ' + v } },
    },
};

onMounted(carregar);
</script>
