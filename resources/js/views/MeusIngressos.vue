<template>
    <div>
        <div class="flex align-items-center gap-2 mb-4">
            <Button icon="pi pi-arrow-left" class="p-button-text" @click="$router.back()" />
            <div>
                <h3 class="m-0">Meus Ingressos</h3>
                <small class="text-color-secondary">{{ ingressos.length }} ingresso(s) em todas as atléticas</small>
            </div>
        </div>

        <!-- Filtro por status -->
        <div v-if="ingressos.length" class="flex gap-2 mb-4 flex-wrap">
            <Button
                v-for="f in filtros" :key="f.value"
                :label="f.label"
                :class="['p-button-sm', filtroAtivo === f.value ? '' : 'p-button-outlined']"
                :severity="f.severity"
                @click="filtroAtivo = f.value"
            />
        </div>

        <!-- Loading -->
        <div v-if="loading" class="grid">
            <div v-for="n in 4" :key="n" class="col-12 md:col-6 lg:col-4">
                <div class="card p-0">
                    <Skeleton height="120px" class="border-round-top" />
                    <div class="p-4">
                        <Skeleton height="160px" class="mb-3" />
                        <Skeleton height="1rem" class="mb-2" />
                        <Skeleton height=".85rem" width="60%" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Vazio -->
        <div v-else-if="ingressosFiltrados.length === 0" class="card text-center py-6">
            <i class="pi pi-ticket text-7xl text-color-secondary mb-4 block"></i>
            <h3 class="text-color-secondary mb-2">Nenhum ingresso encontrado</h3>
            <p class="text-color-secondary mb-4">
                {{ filtroAtivo === 'todos' ? 'Você ainda não comprou ingressos em nenhuma atlética.' : 'Nenhum ingresso com este status.' }}
            </p>
            <router-link to="/">
                <Button label="Ver Atléticas" icon="pi pi-building" class="p-button-outlined" />
            </router-link>
        </div>

        <!-- Grid de ingressos -->
        <div v-else class="grid">
            <div v-for="ingresso in ingressosFiltrados" :key="ingresso.id" class="col-12 md:col-6 lg:col-4">
                <div class="ingresso-ticket" :class="`status-${ingresso.status}`">

                    <!-- Header: banner do evento -->
                    <div class="ingresso-ticket-header">
                        <img v-if="ingresso.evento.banner_url" :src="ingresso.evento.banner_url" class="ingresso-banner" />
                        <div class="ingresso-header-overlay">
                            <Tag :value="statusLabel(ingresso.status)" :severity="statusSeverity(ingresso.status)" class="mb-2" />
                            <h4 class="m-0 text-white line-clamp-2">{{ ingresso.evento.nome }}</h4>
                            <small class="text-white opacity-80 mt-1 block">{{ ingresso.lote.nome }}</small>
                        </div>
                    </div>

                    <!-- Tag da atlética -->
                    <router-link
                        v-if="ingresso.atletica?.slug"
                        :to="`/c/${ingresso.atletica.slug}`"
                        class="atletica-tag"
                    >
                        <i class="pi pi-bolt mr-1"></i>{{ ingresso.atletica.nome }}
                    </router-link>

                    <!-- Divisor estilo ticket -->
                    <div class="ingresso-ticket-divider">
                        <div class="circle-left"></div>
                        <div class="dashed-line"></div>
                        <div class="circle-right"></div>
                    </div>

                    <!-- Corpo: QR Code -->
                    <div class="ingresso-ticket-body">

                        <div v-if="ingresso.status === 'paid' || ingresso.status === 'reserved'" class="qr-wrapper">
                            <img v-if="qrUrls[ingresso.id]" :src="qrUrls[ingresso.id]" class="qr-img" alt="QR Code" />
                            <div v-else class="qr-loading"><i class="pi pi-spin pi-spinner"></i></div>
                            <p class="ingresso-code">{{ ingresso.code }}</p>
                            <div v-if="ingresso.status === 'reserved'" class="reserved-badge">
                                <i class="pi pi-clock mr-1"></i>Pagamento pendente
                            </div>
                        </div>

                        <div v-else-if="ingresso.status === 'used'" class="qr-placeholder used">
                            <i class="pi pi-check-circle text-4xl mb-2" style="color:#6b7280"></i>
                            <p class="font-semibold text-color-secondary">Ingresso Utilizado</p>
                            <small class="text-color-secondary" v-if="ingresso.used_at">
                                Usado em {{ formatDate(ingresso.used_at) }}
                            </small>
                        </div>

                        <div v-else-if="ingresso.status === 'cancelled'" class="qr-placeholder cancelled">
                            <i class="pi pi-times-circle text-4xl mb-2" style="color:#ef4444"></i>
                            <p class="font-semibold" style="color:#ef4444">Ingresso Cancelado</p>
                        </div>

                        <!-- Detalhes do evento -->
                        <div class="ingresso-details">
                            <div v-if="ingresso.evento.data" class="detail-row">
                                <i class="pi pi-calendar detail-icon"></i>
                                <span>{{ formatDate(ingresso.evento.data) }}</span>
                            </div>
                            <div v-if="ingresso.evento.local" class="detail-row">
                                <i class="pi pi-map-marker detail-icon"></i>
                                <span>{{ ingresso.evento.local }}</span>
                            </div>
                            <div class="detail-row">
                                <i class="pi pi-tag detail-icon"></i>
                                <span>R$ {{ Number(ingresso.lote.preco).toFixed(2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import api from '@/service/ApiService';

const ingressos   = ref([]);
const loading     = ref(false);
const qrUrls      = ref({});
const filtroAtivo = ref('todos');

const filtros = [
    { label: 'Todos',     value: 'todos',     severity: 'secondary' },
    { label: 'Válidos',   value: 'paid',      severity: 'success'   },
    { label: 'Pendentes', value: 'reserved',  severity: 'warning'   },
    { label: 'Utilizados',value: 'used',      severity: 'secondary' },
    { label: 'Cancelados',value: 'cancelled', severity: 'danger'    },
];

const ingressosFiltrados = computed(() =>
    filtroAtivo.value === 'todos'
        ? ingressos.value
        : ingressos.value.filter(i => i.status === filtroAtivo.value)
);

function statusLabel(s) {
    return { paid: 'Válido', reserved: 'Pendente', used: 'Utilizado', cancelled: 'Cancelado' }[s] ?? s;
}
function statusSeverity(s) {
    return { paid: 'success', reserved: 'warning', used: 'secondary', cancelled: 'danger' }[s] ?? 'info';
}
function formatDate(d) {
    if (!d) return '';
    return new Date(d).toLocaleString('pt-BR', {
        day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit',
    });
}

async function gerarQRCodes() {
    const { default: QRCode } = await import('qrcode');
    for (const ingresso of ingressos.value) {
        if (ingresso.status === 'paid' || ingresso.status === 'reserved') {
            try {
                qrUrls.value[ingresso.id] = await QRCode.toDataURL(ingresso.code, {
                    width: 220, margin: 2,
                    color: { dark: '#1e293b', light: '#ffffff' },
                });
            } catch {}
        }
    }
}

onMounted(async () => {
    loading.value = true;
    try {
        const { data } = await api.get('/all-tickets');
        ingressos.value = data;
        await gerarQRCodes();
    } finally { loading.value = false; }
});
</script>

<style scoped>
.ingresso-ticket {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,.1);
    overflow: hidden;
    transition: transform .2s;
}
.ingresso-ticket:hover { transform: translateY(-2px); }
.ingresso-ticket.status-used      { opacity: .75; }
.ingresso-ticket.status-cancelled { opacity: .6; }

.ingresso-ticket-header {
    position: relative;
    height: 120px;
    background: linear-gradient(135deg, #1976d2, #1565c0);
    overflow: hidden;
}
.ingresso-banner { width: 100%; height: 100%; object-fit: cover; opacity: .5; }
.ingresso-header-overlay {
    position: absolute;
    inset: 0;
    padding: 1rem;
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    background: linear-gradient(to top, rgba(0,0,0,.65) 0%, transparent 50%);
}
.text-white  { color: #fff !important; }
.opacity-80  { opacity: .8; }
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Tag da atlética */
.atletica-tag {
    display: flex;
    align-items: center;
    padding: .35rem .75rem;
    font-size: .78rem;
    font-weight: 600;
    color: #1976d2;
    background: #e3f2fd;
    text-decoration: none;
    transition: background .15s;
}
.atletica-tag:hover { background: #bbdefb; }

.ingresso-ticket-divider {
    display: flex;
    align-items: center;
    padding: 0 .5rem;
    background: #fff;
}
.circle-left, .circle-right {
    width: 20px; height: 20px;
    background: #f5f5f5;
    border-radius: 50%;
    flex-shrink: 0;
    box-shadow: inset 0 0 0 1px #e0e0e0;
}
.dashed-line {
    flex: 1; height: 1px;
    margin: 0 .25rem;
    border-top: 2px dashed #e0e0e0;
}

.ingresso-ticket-body { padding: 1rem; }

.qr-wrapper { display: flex; flex-direction: column; align-items: center; padding: .75rem 0; }
.qr-img { width: 180px; height: 180px; border-radius: 8px; border: 1px solid #e0e0e0; padding: 6px; display: block; }
.qr-loading {
    width: 180px; height: 180px;
    display: flex; align-items: center; justify-content: center;
    color: #9ca3af; font-size: 2rem;
    border: 1px solid #e0e0e0; border-radius: 8px;
}
.ingresso-code {
    font-family: 'Courier New', monospace;
    font-size: .78rem; color: #6b7280;
    margin: .5rem 0 0; letter-spacing: 2px; text-transform: uppercase;
}
.reserved-badge {
    margin-top: .4rem; font-size: .78rem;
    color: #f59e0b; background: #fffbeb;
    border: 1px solid #fde68a; padding: .2rem .6rem;
    border-radius: 12px; display: inline-flex; align-items: center;
}

.qr-placeholder {
    display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    text-align: center; padding: 1.25rem;
    border-radius: 8px; margin-bottom: .5rem;
}
.qr-placeholder.used      { background: #f9fafb; }
.qr-placeholder.cancelled { background: #fef2f2; }

.ingresso-details { margin-top: .75rem; }
.detail-row {
    display: flex; align-items: flex-start;
    gap: .5rem; margin-bottom: .4rem;
    font-size: .85rem; color: #4b5563;
}
.detail-icon { font-size: .85rem; color: #1976d2; flex-shrink: 0; margin-top: 2px; }
</style>
