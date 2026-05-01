<template>
    <div class="flex flex-column align-items-center justify-content-center py-6 text-center">
        <!-- Sucesso -->
        <template v-if="resultado === 'sucesso'">
            <i class="pi pi-check-circle mb-4" style="font-size:4rem;color:#2e7d32"></i>
            <h2 class="mb-2">Pagamento confirmado!</h2>
            <p class="text-color-secondary mb-4">Seu pedido foi pago com sucesso. Seus ingressos já estão disponíveis.</p>
        </template>

        <!-- Pendente -->
        <template v-else-if="resultado === 'pendente'">
            <i class="pi pi-clock mb-4" style="font-size:4rem;color:#f57c00"></i>
            <h2 class="mb-2">Pagamento em análise</h2>
            <p class="text-color-secondary mb-4">Seu pagamento está sendo processado. Você receberá a confirmação em breve.</p>
        </template>

        <!-- Falha -->
        <template v-else>
            <i class="pi pi-times-circle mb-4" style="font-size:4rem;color:#c62828"></i>
            <h2 class="mb-2">Pagamento não concluído</h2>
            <p class="text-color-secondary mb-4">Ocorreu um problema com seu pagamento. Você pode tentar novamente.</p>
        </template>

        <div class="flex gap-3 flex-wrap justify-content-center">
            <Button label="Ver meu pedido" icon="pi pi-receipt"
                class="p-button-outlined"
                @click="router.push(`/c/${route.params.slug}/pedido/${route.params.orderId}`)" />
            <Button label="Meus ingressos" icon="pi pi-ticket"
                class="p-button-success"
                @click="router.push(`/c/${route.params.slug}/meus-ingressos`)" />
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';

const route  = useRoute();
const router = useRouter();

const resultado = computed(() => route.params.resultado);
</script>
