<template>
    <div class="layout-dashboard">
        <div class="grid">
            <div class="col-12 lg:col-6 xl:col-3">
                <div class="overview-box sales">
                    <i class="overview-icon pi pi-dollar"></i>
                    <span class="overview-title">Vendas Hoje</span>
                    <i class="overview-arrow pi pi-chevron-circle-up"></i>
                    <div class="overview-numbers">R$ {{ Number(stats.vendas_hoje ?? 0).toFixed(2) }}</div>
                    <div class="overview-subinfo">faturamento do dia</div>
                </div>
            </div>
            <div class="col-12 lg:col-6 xl:col-3">
                <div class="overview-box views">
                    <i class="overview-icon pi pi-list"></i>
                    <span class="overview-title">Pedidos Hoje</span>
                    <i class="overview-arrow pi pi-chevron-circle-right"></i>
                    <div class="overview-numbers">{{ stats.pedidos_hoje ?? 0 }}</div>
                    <div class="overview-subinfo">pedidos recebidos</div>
                </div>
            </div>
            <div class="col-12 lg:col-6 xl:col-3">
                <div class="overview-box users">
                    <i class="overview-icon pi pi-clock"></i>
                    <span class="overview-title">Pendentes</span>
                    <i class="overview-arrow pi pi-chevron-circle-right"></i>
                    <div class="overview-numbers">{{ stats.pedidos_pendentes ?? 0 }}</div>
                    <div class="overview-subinfo">aguardando pagamento</div>
                </div>
            </div>
            <div class="col-12 lg:col-6 xl:col-3">
                <div class="overview-box checkin">
                    <i class="overview-icon pi pi-calendar"></i>
                    <span class="overview-title">Eventos Ativos</span>
                    <i class="overview-arrow pi pi-chevron-circle-up"></i>
                    <div class="overview-numbers">{{ stats.eventos_ativos ?? 0 }}</div>
                    <div class="overview-subinfo">em andamento</div>
                </div>
            </div>
        </div>

        <div class="grid">
            <div class="col-12 lg:col-8">
                <div class="card card-w-title">
                    <div class="flex align-items-center justify-content-between mb-3">
                        <h5 class="m-0">Pedidos Recentes</h5>
                        <router-link to="/admin/pedidos">
                            <Button label="Ver Todos" icon="pi pi-arrow-right" iconPos="right" text size="small" />
                        </router-link>
                    </div>
                    <DataTable :value="stats.pedidos_recentes" :loading="loading" responsiveLayout="scroll" stripedRows>
                        <template #empty><div class="text-center p-4 text-color-secondary">Nenhum pedido.</div></template>
                        <Column field="id" header="Nº" style="width:60px" />
                        <Column field="customer_name" header="Cliente" />
                        <Column field="total" header="Total">
                            <template #body="{ data }">R$ {{ Number(data.total).toFixed(2) }}</template>
                        </Column>
                        <Column field="status" header="Status">
                            <template #body="{ data }">
                                <Tag :value="statusLabel(data.status)" :severity="statusSeverity(data.status)" />
                            </template>
                        </Column>
                        <Column header="Hora">
                            <template #body="{ data }">{{ formatTime(data.created_at) }}</template>
                        </Column>
                    </DataTable>
                </div>
            </div>
            <div class="col-12 lg:col-4">
                <div class="card card-w-title">
                    <h5>Ações Rápidas</h5>
                    <div class="flex flex-column gap-3">
                        <router-link to="/admin/eventos/novo">
                            <Button label="Criar Evento" icon="pi pi-plus" class="w-full p-button-success" />
                        </router-link>
                        <router-link to="/admin/pedidos">
                            <Button label="Ver Pedidos" icon="pi pi-list" class="w-full" />
                        </router-link>
                        <router-link to="/admin/produtos">
                            <Button label="Produtos" icon="pi pi-tag" severity="secondary" class="w-full" />
                        </router-link>
                        <router-link to="/admin/relatorios">
                            <Button label="Relatórios" icon="pi pi-chart-bar" outlined class="w-full" />
                        </router-link>
                    </div>
                </div>
                <div v-if="tenantSlug" class="card card-w-title mt-3">
                    <h5>Link da Loja</h5>
                    <div class="p-2 border-round text-sm text-color-secondary mb-2"
                        style="background:var(--surface-ground);word-break:break-all;font-family:monospace">
                        {{ lojaUrl }}
                    </div>
                    <Button :label="copiado ? 'Copiado!' : 'Copiar Link'" :icon="copiado ? 'pi pi-check' : 'pi pi-copy'"
                        :severity="copiado ? 'success' : 'secondary'" size="small" outlined class="w-full" @click="copiarLink" />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import api from '@/service/ApiService';

const loading    = ref(true);
const stats      = ref({});
const copiado    = ref(false);
const tenantSlug = computed(() => localStorage.getItem('idealfood_tenant_slug'));
const lojaUrl    = computed(() => tenantSlug.value ? `${window.location.origin}/c/${tenantSlug.value}` : null);

function statusLabel(s) {
    return { pending:'Pendente', paid:'Pago', cancelled:'Cancelado', refunded:'Reembolsado' }[s] ?? s;
}
function statusSeverity(s) {
    return { pending:'warning', paid:'success', cancelled:'danger', refunded:'info' }[s] ?? 'secondary';
}
function formatTime(d) {
    return new Date(d).toLocaleTimeString('pt-BR', { hour:'2-digit', minute:'2-digit' });
}
async function copiarLink() {
    await navigator.clipboard.writeText(lojaUrl.value);
    copiado.value = true;
    setTimeout(() => copiado.value = false, 2500);
}

onMounted(async () => {
    try { const { data } = await api.get('/admin/dashboard'); stats.value = data; }
    finally { loading.value = false; }
});
</script>
