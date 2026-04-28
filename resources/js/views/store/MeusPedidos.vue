<template>
    <div>
        <div class="flex align-items-center gap-2 mb-4">
            <Button icon="pi pi-arrow-left" class="p-button-text" @click="$router.back()" />
            <h3 class="m-0">Meus Pedidos</h3>
        </div>
        <div v-if="loading" class="flex justify-content-center py-6"><ProgressSpinner /></div>
        <div v-else-if="!pedidos.data?.length" class="card text-center py-6">
            <i class="pi pi-list text-6xl text-color-secondary mb-3 block"></i>
            <p class="text-color-secondary">Você ainda não tem pedidos.</p>
        </div>
        <div v-else>
            <div v-for="pedido in pedidos.data" :key="pedido.id" class="card mb-3">
                <div class="flex align-items-center justify-content-between mb-2">
                    <span class="font-bold">Pedido #{{ pedido.id }}</span>
                    <Tag :value="statusLabel(pedido.status)" :severity="statusSeverity(pedido.status)" />
                </div>
                <div v-for="item in pedido.items" :key="item.id" class="flex justify-content-between text-sm py-1">
                    <span>{{ item.quantity }}× {{ item.item_name }}</span>
                    <span>R$ {{ (item.unit_price * item.quantity).toFixed(2) }}</span>
                </div>
                <Divider />
                <div class="flex justify-content-between font-bold">
                    <span>Total</span>
                    <span style="color:#2e7d32">R$ {{ Number(pedido.total).toFixed(2) }}</span>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import api from '@/service/ApiService';

const pedidos = ref({ data:[] });
const loading = ref(false);

function statusLabel(s) {
    return { pending:'Pendente', paid:'Pago', cancelled:'Cancelado' }[s] ?? s;
}
function statusSeverity(s) {
    return { pending:'warning', paid:'success', cancelled:'danger' }[s] ?? 'secondary';
}

onMounted(async () => {
    loading.value = true;
    try { const { data } = await api.get('/my-orders'); pedidos.value = data; }
    finally { loading.value = false; }
});
</script>
