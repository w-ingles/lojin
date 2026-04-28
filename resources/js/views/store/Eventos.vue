<template>
    <div>
        <div class="flex align-items-center justify-content-between mb-4">
            <h2 class="m-0">Próximos Eventos</h2>
            <router-link :to="`/c/${$route.params.slug}/produtos`">
                <Button label="Ver Produtos" icon="pi pi-tag" class="p-button-outlined" />
            </router-link>
        </div>

        <div v-if="loading" class="flex justify-content-center py-6"><ProgressSpinner /></div>

        <div v-else-if="eventos.length === 0" class="card text-center py-6">
            <i class="pi pi-calendar text-6xl text-color-secondary mb-3 block"></i>
            <p class="text-color-secondary text-lg">Nenhum evento disponível no momento.</p>
            <p class="text-color-secondary text-sm">Em breve novos eventos serão anunciados!</p>
        </div>

        <div v-else class="grid">
            <div v-for="evento in eventos" :key="evento.id" class="col-12 md:col-6 lg:col-4">
                <div class="card h-full flex flex-column" style="padding:0;overflow:hidden;cursor:pointer"
                    @click="$router.push(`/c/${$route.params.slug}/eventos/${evento.id}`)">
                    <div style="height:200px;overflow:hidden;background:#1976d2;position:relative">
                        <img v-if="evento.banner_url" :src="evento.banner_url" :alt="evento.name"
                            style="width:100%;height:100%;object-fit:cover" />
                        <div v-else class="flex align-items-center justify-content-center h-full">
                            <i class="pi pi-calendar text-6xl" style="color:rgba(255,255,255,.5)"></i>
                        </div>
                        <span class="event-badge">{{ statusLabel(evento.status) }}</span>
                    </div>
                    <div class="p-3 flex flex-column flex-1">
                        <h4 class="m-0 mb-2">{{ evento.name }}</h4>
                        <div class="flex align-items-center gap-2 text-color-secondary text-sm mb-1">
                            <i class="pi pi-calendar"></i>
                            <span>{{ formatDate(evento.starts_at) }}</span>
                        </div>
                        <div v-if="evento.location" class="flex align-items-center gap-2 text-color-secondary text-sm mb-3">
                            <i class="pi pi-map-marker"></i>
                            <span>{{ evento.location }}</span>
                        </div>
                        <div class="mt-auto flex align-items-center justify-content-between">
                            <div class="text-sm text-color-secondary">
                                {{ evento.total_sold }} / {{ evento.total_capacity }} ingressos
                            </div>
                            <Button label="Ver Ingressos" icon="pi pi-ticket" size="small" class="p-button-success" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <Toast />
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import api from '@/service/ApiService';

const route  = useRoute();
const eventos = ref([]);
const loading = ref(false);

function statusLabel(s) {
    return { active:'Disponível', sold_out:'Esgotado', finished:'Encerrado', draft:'Em Breve' }[s] ?? s;
}

function formatDate(d) {
    return new Date(d).toLocaleString('pt-BR', { day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit' });
}

onMounted(async () => {
    loading.value = true;
    try {
        const { data } = await api.get('/events');
        eventos.value = data;
    } finally { loading.value = false; }
});
</script>

<style scoped>
.event-badge { position:absolute; top:.5rem; right:.5rem; background:rgba(0,0,0,.55); color:#fff; padding:.2rem .7rem; border-radius:12px; font-size:.75rem; font-weight:600; }
</style>
