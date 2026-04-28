<template>
    <div class="layout-dashboard">
        <div class="grid">
            <div class="col-12 lg:col-6 xl:col-3">
                <div class="overview-box sales">
                    <i class="overview-icon pi pi-building"></i>
                    <span class="overview-title">Atléticas</span>
                    <i class="overview-arrow pi pi-chevron-circle-up"></i>
                    <div class="overview-numbers">{{ stats.total_atleticas ?? 0 }}</div>
                    <div class="overview-subinfo">cadastradas</div>
                </div>
            </div>
            <div class="col-12 lg:col-6 xl:col-3">
                <div class="overview-box users">
                    <i class="overview-icon pi pi-check-circle"></i>
                    <span class="overview-title">Ativas</span>
                    <i class="overview-arrow pi pi-chevron-circle-up"></i>
                    <div class="overview-numbers">{{ stats.atleticas_ativas ?? 0 }}</div>
                    <div class="overview-subinfo">em operação</div>
                </div>
            </div>
            <div class="col-12 lg:col-6 xl:col-3">
                <div class="overview-box views">
                    <i class="overview-icon pi pi-list"></i>
                    <span class="overview-title">Pedidos</span>
                    <i class="overview-arrow pi pi-chevron-circle-right"></i>
                    <div class="overview-numbers">{{ stats.total_pedidos ?? 0 }}</div>
                    <div class="overview-subinfo">na plataforma</div>
                </div>
            </div>
            <div class="col-12 lg:col-6 xl:col-3">
                <div class="overview-box checkin">
                    <i class="overview-icon pi pi-dollar"></i>
                    <span class="overview-title">Faturamento</span>
                    <i class="overview-arrow pi pi-chevron-circle-up"></i>
                    <div class="overview-numbers">R$ {{ Number(stats.faturamento_total ?? 0).toFixed(2) }}</div>
                    <div class="overview-subinfo">total pago</div>
                </div>
            </div>
        </div>
        <div class="grid">
            <div class="col-12 lg:col-8">
                <div class="card card-w-title">
                    <div class="flex align-items-center justify-content-between mb-3">
                        <h5 class="m-0">Atléticas Recentes</h5>
                        <router-link to="/super-admin/atleticas">
                            <Button label="Ver Todas" icon="pi pi-arrow-right" iconPos="right" text size="small" />
                        </router-link>
                    </div>
                    <DataTable :value="stats.atleticas_recentes" :loading="loading" responsiveLayout="scroll" stripedRows>
                        <template #empty><div class="text-center p-4 text-color-secondary">Nenhuma atlética.</div></template>
                        <Column field="name" header="Atlética" />
                        <Column field="slug" header="Slug"><template #body="{ data }"><code>{{ data.slug }}</code></template></Column>
                        <Column field="users_count" header="Usuários" style="width:90px" />
                        <Column field="orders_count" header="Pedidos" style="width:90px" />
                        <Column field="is_active" header="Status" style="width:90px">
                            <template #body="{ data }">
                                <Tag :value="data.is_active ? 'Ativa' : 'Inativa'" :severity="data.is_active ? 'success' : 'danger'" />
                            </template>
                        </Column>
                    </DataTable>
                </div>
            </div>
            <div class="col-12 lg:col-4">
                <div class="card card-w-title">
                    <h5>Ações Rápidas</h5>
                    <div class="flex flex-column gap-3">
                        <router-link to="/super-admin/atleticas">
                            <Button label="Gerenciar Atléticas" icon="pi pi-building" class="w-full" />
                        </router-link>
                        <router-link to="/super-admin/atleticas?novo=1">
                            <Button label="Nova Atlética" icon="pi pi-plus" severity="success" class="w-full" />
                        </router-link>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import api from '@/service/ApiService';
const loading = ref(true);
const stats   = ref({});
onMounted(async () => {
    try { const { data } = await api.get('/super-admin/overview'); stats.value = data; }
    finally { loading.value = false; }
});
</script>
