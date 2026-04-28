<template>
    <div class="flex justify-content-center align-items-center" style="min-height:60vh">
        <div class="card text-center" style="max-width:480px;width:100%">
            <i class="pi pi-check-circle text-7xl mb-4 block" style="color:#43a047"></i>
            <h2 class="mb-1" style="color:#2e7d32">Pedido Confirmado!</h2>
            <p class="text-color-secondary text-lg mb-1">Pedido <strong>#{{ id }}</strong></p>
            <p class="text-color-secondary mb-4">Seu pedido foi recebido com sucesso!</p>

            <Divider v-if="order" />
            <div v-if="order" class="text-left mb-4">
                <div v-for="item in order.items" :key="item.id" class="flex justify-content-between py-1 text-sm">
                    <span>{{ item.quantity }}× {{ item.item_name }}</span>
                    <span>R$ {{ (item.unit_price * item.quantity).toFixed(2) }}</span>
                </div>
                <Divider />
                <div class="flex justify-content-between font-bold">
                    <span>Total</span>
                    <span>R$ {{ Number(order.total).toFixed(2) }}</span>
                </div>
            </div>

            <router-link :to="`/c/${$route.params.slug}`">
                <Button label="Voltar à Loja" icon="pi pi-shopping-bag" class="w-full" />
            </router-link>
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import api from '@/service/ApiService';

const route = useRoute();
const id    = route.params.id;
const order = ref(null);

onMounted(async () => {
    try { const { data } = await api.get(`/orders/${id}`); order.value = data; } catch {}
});
</script>
