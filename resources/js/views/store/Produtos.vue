<template>
    <div>
        <div class="flex align-items-center justify-content-between mb-4">
            <h2 class="m-0">Produtos</h2>
            <router-link :to="`/c/${$route.params.slug}`">
                <Button label="Ver Eventos" icon="pi pi-calendar" class="p-button-outlined" />
            </router-link>
        </div>

        <div v-if="loading" class="flex justify-content-center py-6"><ProgressSpinner /></div>
        <div v-else-if="produtos.length === 0" class="card text-center py-6">
            <i class="pi pi-tag text-6xl text-color-secondary mb-3 block"></i>
            <p class="text-color-secondary text-lg">Nenhum produto disponível.</p>
        </div>
        <div v-else class="grid">
            <div v-for="p in produtos" :key="p.id" class="col-12 md:col-6 lg:col-4 xl:col-3">
                <div class="card h-full flex flex-column" style="padding:0;overflow:hidden">
                    <div style="height:180px;background:#f5f5f5;overflow:hidden">
                        <img v-if="p.image_url" :src="p.image_url" style="width:100%;height:100%;object-fit:cover" />
                        <div v-else class="flex align-items-center justify-content-center h-full">
                            <i class="pi pi-tag text-5xl text-color-secondary"></i>
                        </div>
                    </div>
                    <div class="p-3 flex flex-column flex-1 gap-2">
                        <small v-if="p.category" class="text-primary font-semibold uppercase" style="font-size:.72rem">{{ p.category.name }}</small>
                        <h5 class="m-0">{{ p.name }}</h5>
                        <p class="text-color-secondary text-sm flex-1 m-0">{{ p.description }}</p>
                        <div class="flex align-items-center justify-content-between mt-2">
                            <span class="text-xl font-bold" style="color:#2e7d32">R$ {{ Number(p.price).toFixed(2) }}</span>
                            <Button icon="pi pi-plus" label="Adicionar" size="small" class="p-button-success"
                                :disabled="p.stock === 0" @click="adicionar(p)" />
                        </div>
                        <small v-if="p.stock === 0" class="p-error">Esgotado</small>
                    </div>
                </div>
            </div>
        </div>
        <Toast />
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useToast } from 'primevue/usetoast';
import api from '@/service/ApiService';
import { useCart } from '@/composables/useCart';

const toast = useToast();
const { addItem } = useCart();
const produtos = ref([]);
const loading  = ref(false);

function adicionar(p) {
    addItem({ id: p.id, type: 'product', name: p.name, price: Number(p.price), qty: 1, max: p.stock });
    toast.add({ severity:'success', summary:'Adicionado!', detail: p.name, life:1500 });
}

onMounted(async () => {
    loading.value = true;
    try { const { data } = await api.get('/products'); produtos.value = data; }
    finally { loading.value = false; }
});
</script>
