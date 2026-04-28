<template>
    <div v-if="loading" class="flex justify-content-center py-8"><ProgressSpinner /></div>
    <div v-else-if="evento">
        <Button icon="pi pi-arrow-left" class="p-button-text mb-3" @click="$router.back()" label="Voltar" />

        <div class="grid">
            <div class="col-12 lg:col-8">
                <!-- Banner -->
                <div style="height:280px;border-radius:8px;overflow:hidden;background:#1976d2;margin-bottom:1rem">
                    <img v-if="evento.banner_url" :src="evento.banner_url" style="width:100%;height:100%;object-fit:cover" />
                    <div v-else class="flex align-items-center justify-content-center h-full">
                        <i class="pi pi-calendar text-8xl" style="color:rgba(255,255,255,.4)"></i>
                    </div>
                </div>

                <div class="card">
                    <h2 class="mt-0">{{ evento.name }}</h2>
                    <div class="flex flex-wrap gap-3 mb-3 text-color-secondary">
                        <span><i class="pi pi-calendar mr-1"></i>{{ formatDate(evento.starts_at) }}</span>
                        <span v-if="evento.location"><i class="pi pi-map-marker mr-1"></i>{{ evento.location }}</span>
                        <span v-if="evento.minimum_age"><i class="pi pi-info-circle mr-1"></i>{{ evento.minimum_age }}+</span>
                    </div>
                    <p v-if="evento.description" class="text-color-secondary" style="line-height:1.7">{{ evento.description }}</p>
                </div>
            </div>

            <div class="col-12 lg:col-4">
                <div class="card" style="position:sticky;top:80px">
                    <h5 class="mt-0">Ingressos</h5>

                    <div v-if="!evento.ticket_batches?.length" class="text-color-secondary text-center py-3">
                        Nenhum lote disponível.
                    </div>

                    <div v-for="lote in evento.ticket_batches" :key="lote.id" class="mb-3 p-3 border-round" style="background:var(--surface-ground)">
                        <div class="flex justify-content-between align-items-start mb-1">
                            <div>
                                <div class="font-semibold">{{ lote.name }}</div>
                                <div v-if="lote.description" class="text-sm text-color-secondary">{{ lote.description }}</div>
                            </div>
                            <span class="font-bold text-lg" style="color:#2e7d32">
                                {{ lote.price > 0 ? 'R$ ' + Number(lote.price).toFixed(2) : 'Gratuito' }}
                            </span>
                        </div>
                        <div class="flex align-items-center justify-content-between">
                            <small class="text-color-secondary">{{ lote.available }} disponíveis</small>
                            <div class="flex align-items-center gap-2">
                                <Button icon="pi pi-minus" class="p-button-text p-button-sm"
                                    :disabled="!qtdLote[lote.id]"
                                    @click="qtdLote[lote.id] = Math.max(0, (qtdLote[lote.id]||0) - 1)" />
                                <span class="font-bold w-2rem text-center">{{ qtdLote[lote.id] || 0 }}</span>
                                <Button icon="pi pi-plus" class="p-button-text p-button-sm"
                                    :disabled="!lote.is_active || lote.available <= 0"
                                    @click="qtdLote[lote.id] = Math.min(lote.available, (qtdLote[lote.id]||0) + 1)" />
                            </div>
                        </div>
                    </div>

                    <Button label="Adicionar ao Carrinho" icon="pi pi-shopping-cart"
                        class="p-button-success w-full mt-2"
                        :disabled="totalSelecionado === 0"
                        @click="adicionarAoCarrinho" />
                </div>
            </div>
        </div>
        <Toast />
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import api from '@/service/ApiService';
import { useCart } from '@/composables/useCart';

const route  = useRoute();
const router = useRouter();
const toast  = useToast();
const { addItem } = useCart();

const evento   = ref(null);
const loading  = ref(false);
const qtdLote  = reactive({});

const totalSelecionado = computed(() =>
    Object.values(qtdLote).reduce((s, v) => s + (Number.isFinite(v) ? v : 0), 0)
);

function formatDate(d) {
    return new Date(d).toLocaleString('pt-BR', { day:'2-digit', month:'long', year:'numeric', hour:'2-digit', minute:'2-digit' });
}

function adicionarAoCarrinho() {
    const slug = route.params.slug;
    let adicionou = false;
    for (const lote of evento.value.ticket_batches) {
        const qty = Number.isFinite(qtdLote[lote.id]) ? qtdLote[lote.id] : 0;
        if (qty > 0) {
            addItem({
                id:    lote.id,
                type:  'ticket_batch',
                name:  `${evento.value.name} — ${lote.name}`,
                price: Number(lote.price),
                qty,
                max:   lote.available ?? 999,
            });
            adicionou = true;
        }
    }
    if (adicionou) {
        toast.add({ severity: 'success', summary: 'Adicionado!', detail: 'Ingressos no carrinho.', life: 1500 });
        router.push(`/c/${slug}/carrinho`);
    }
}

onMounted(async () => {
    loading.value = true;
    try {
        const { data } = await api.get(`/events/${route.params.id}`);
        evento.value = data;
        data.ticket_batches?.forEach(l => qtdLote[l.id] = 0);
    } finally { loading.value = false; }
});
</script>
