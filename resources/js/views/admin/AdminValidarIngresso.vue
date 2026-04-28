<template>
    <div>
        <div class="flex align-items-center gap-2 mb-4">
            <Button icon="pi pi-arrow-left" class="p-button-text" @click="pararEFechar" />
            <h3 class="m-0">Validar Ingressos</h3>
        </div>

        <!-- Painel principal -->
        <div class="card mb-3">
            <!-- Botao de escanear (funciona tanto no app quanto no browser) -->
            <div v-if="!scannerAberto" class="text-center py-4">
                <Button
                    label="Abrir câmera para escanear"
                    icon="pi pi-qrcode"
                    class="p-button-success p-button-lg"
                    style="min-width:280px"
                    :loading="iniciando"
                    :disabled="validando"
                    @click="abrirScanner"
                />
                <p class="text-color-secondary text-sm mt-3 mb-0">
                    Aponte a câmera para o QR code do ingresso
                </p>
            </div>

            <!-- Área do scanner de câmera (browser) -->
            <div v-show="scannerAberto && !isNativo" class="scanner-wrapper">
                <div class="flex justify-content-between align-items-center mb-3">
                    <span class="font-semibold text-sm flex align-items-center gap-2">
                        <i class="pi pi-camera" style="color:#1976d2"></i>
                        Câmera ativa — aponte para o QR code
                    </span>
                    <Button icon="pi pi-times" label="Fechar" class="p-button-text p-button-sm p-button-danger"
                        @click="pararScanner" />
                </div>
                <!-- html5-qrcode renderiza o vídeo aqui -->
                <div id="qr-scanner-container" style="width:100%;max-width:480px;margin:0 auto;border-radius:10px;overflow:hidden"></div>
            </div>

            <!-- Input manual sempre disponível como fallback -->
            <div v-if="!scannerAberto" class="mt-4 pt-3 border-top-1 surface-border">
                <div class="p-fluid flex gap-2" style="max-width:420px;margin:0 auto">
                    <InputText
                        v-model="codigoManual"
                        placeholder="Ou digite o código manualmente..."
                        class="flex-1"
                        :disabled="validando"
                        @keyup.enter="validarCodigo(codigoManual)"
                    />
                    <Button
                        icon="pi pi-check"
                        class="p-button-success"
                        :loading="validando"
                        :disabled="!codigoManual.trim()"
                        @click="validarCodigo(codigoManual)"
                    />
                </div>
            </div>
        </div>

        <!-- Resultado da última validação -->
        <transition name="slide-fade">
            <div v-if="resultado" class="card mb-3 resultado-card" :style="estiloResultado">
                <div class="flex align-items-center gap-3">
                    <i :class="['pi', resultado.icone, 'text-5xl']" :style="{ color: resultado.cor }"></i>
                    <div class="flex-1">
                        <div class="font-bold text-xl mb-1" :style="{ color: resultado.cor }">
                            {{ resultado.titulo }}
                        </div>
                        <div class="text-color-secondary text-sm">{{ resultado.message }}</div>
                    </div>
                    <Button icon="pi pi-times" class="p-button-text p-button-sm" @click="resultado = null" />
                </div>

                <div v-if="resultado.ticket" class="grid mt-3 p-3 border-round" style="background:rgba(0,0,0,.04)">
                    <div class="col-6">
                        <div class="text-xs text-color-secondary mb-1 font-semibold">EVENTO</div>
                        <div>{{ resultado.ticket.evento ?? '—' }}</div>
                    </div>
                    <div class="col-6">
                        <div class="text-xs text-color-secondary mb-1 font-semibold">LOTE</div>
                        <div>{{ resultado.ticket.lote ?? '—' }}</div>
                    </div>
                    <div class="col-6 mt-2">
                        <div class="text-xs text-color-secondary mb-1 font-semibold">CÓDIGO</div>
                        <div class="font-mono text-sm">{{ resultado.ticket.code }}</div>
                    </div>
                    <div class="col-6 mt-2" v-if="resultado.ticket.used_at">
                        <div class="text-xs text-color-secondary mb-1 font-semibold">UTILIZADO EM</div>
                        <div class="text-sm">{{ formatarData(resultado.ticket.used_at) }}</div>
                    </div>
                </div>
            </div>
        </transition>

        <!-- Histórico da sessão -->
        <div v-if="historico.length > 0" class="card">
            <div class="flex justify-content-between align-items-center mb-3">
                <h6 class="m-0">Histórico da sessão</h6>
                <div class="flex gap-2 align-items-center">
                    <Tag :value="`${contadores.ok} liberados`" severity="success" />
                    <Tag :value="`${contadores.erro} erros`" severity="danger" />
                    <Button icon="pi pi-trash" class="p-button-text p-button-sm p-button-secondary"
                        v-tooltip.top="'Limpar histórico'"
                        @click="historico = []; contadores.ok = 0; contadores.erro = 0" />
                </div>
            </div>
            <DataTable :value="historico" size="small" stripedRows :rows="20" paginator>
                <Column field="code" header="Código">
                    <template #body="{ data }"><span class="font-mono text-sm">{{ data.code }}</span></template>
                </Column>
                <Column field="evento" header="Evento" />
                <Column field="statusLabel" header="Resultado" style="width:130px">
                    <template #body="{ data }"><Tag :value="data.statusLabel" :severity="data.severity" /></template>
                </Column>
                <Column field="hora" header="Hora" style="width:80px">
                    <template #body="{ data }"><span class="text-sm">{{ data.hora }}</span></template>
                </Column>
            </DataTable>
        </div>

        <Toast />
    </div>
</template>

<script setup>
import { computed, onUnmounted, reactive, ref } from 'vue';
import { useToast } from 'primevue/usetoast';
import api from '@/service/ApiService';

const toast = useToast();

// Detecta Capacitor nativo
const isNativo = computed(() =>
    typeof window !== 'undefined' && !!(window.Capacitor?.isNativePlatform?.())
);

const iniciando    = ref(false);
const validando    = ref(false);
const scannerAberto = ref(false);
const codigoManual = ref('');
const resultado    = ref(null);
const historico    = ref([]);
const contadores   = reactive({ ok: 0, erro: 0 });

let html5QrCode   = null;
let ultimoCodigo  = '';  // evita validar o mesmo QR code múltiplas vezes seguidas

// ── Scanner ────────────────────────────────────────────────────────────────────

async function abrirScanner() {
    iniciando.value = true;
    resultado.value = null;

    if (isNativo.value) {
        await escanearNativo();
        iniciando.value = false;
        return;
    }

    // Browser: usa html5-qrcode
    scannerAberto.value = true;
    await iniciarScannerBrowser();
    iniciando.value = false;
}

async function iniciarScannerBrowser() {
    await nextTick();
    try {
        const { Html5Qrcode } = await import('html5-qrcode');
        html5QrCode = new Html5Qrcode('qr-scanner-container');

        await html5QrCode.start(
            { facingMode: 'environment' },   // câmera traseira
            {
                fps: 10,
                qrbox: { width: 260, height: 260 },
                aspectRatio: 1.0,
            },
            async (texto) => {
                // Ignora leituras repetidas do mesmo código em menos de 2s
                if (texto === ultimoCodigo) return;
                ultimoCodigo = texto;
                setTimeout(() => { ultimoCodigo = ''; }, 2000);

                await pararScanner();
                await validarCodigo(texto);
            },
            () => { /* erros de frame são normais, ignora */ }
        );
    } catch (err) {
        scannerAberto.value = false;
        const msg = err?.message ?? '';
        if (msg.includes('permission') || msg.includes('NotAllowed')) {
            toast.add({ severity: 'warn', summary: 'Permissão negada', detail: 'Permita o acesso à câmera no navegador e tente novamente.', life: 5000 });
        } else {
            toast.add({ severity: 'error', summary: 'Erro ao abrir câmera', detail: msg || 'Verifique se a câmera está disponível.', life: 4000 });
        }
    }
}

async function escanearNativo() {
    try {
        const { BarcodeScanner } = await import('@capacitor-community/barcode-scanner');
        const status = await BarcodeScanner.checkPermission({ force: true });
        if (!status.granted) {
            toast.add({ severity: 'warn', summary: 'Permissão necessária', detail: 'Permita o acesso à câmera nas configurações do dispositivo.', life: 4000 });
            return;
        }
        document.body.classList.add('scanner-active');
        BarcodeScanner.hideBackground();
        const result = await BarcodeScanner.startScan();
        document.body.classList.remove('scanner-active');
        BarcodeScanner.showBackground();
        if (result.hasContent) await validarCodigo(result.content);
    } catch (err) {
        document.body.classList.remove('scanner-active');
        toast.add({ severity: 'error', summary: 'Erro na câmera', detail: err.message ?? 'Não foi possível acessar a câmera.', life: 3000 });
    }
}

async function pararScanner() {
    if (html5QrCode) {
        try { await html5QrCode.stop(); } catch {}
        try { html5QrCode.clear(); } catch {}
        html5QrCode = null;
    }
    scannerAberto.value = false;
    ultimoCodigo = '';
}

function pararEFechar() {
    pararScanner();
    history.back();
}

// Garante que o scanner é destruído ao sair da rota
onUnmounted(() => pararScanner());

// nextTick local para esperar o DOM antes de montar o scanner
function nextTick() {
    return new Promise(r => setTimeout(r, 50));
}

// ── Validação ──────────────────────────────────────────────────────────────────

async function validarCodigo(code) {
    const limpo = (code ?? '').trim().toUpperCase();
    if (!limpo) return;

    validando.value = true;
    resultado.value = null;

    try {
        const { data } = await api.post('/admin/tickets/validate', { code: limpo });
        resultado.value = {
            status:  'ok',
            titulo:  '✓ Entrada liberada!',
            message: data.message,
            icone:   'pi-check-circle',
            cor:     '#16a34a',
            ticket:  data.ticket,
        };
        registrarHistorico(data.ticket, 'ok', 'success', 'Liberado');
        contadores.ok++;
    } catch (err) {
        const status  = err.response?.data?.status ?? 'error';
        const message = err.response?.data?.message ?? 'Erro ao validar ingresso.';
        const ticket  = err.response?.data?.ticket ?? null;

        const configs = {
            already_used: { titulo: 'Já utilizado',       icone: 'pi-times-circle',         cor: '#ea580c', label: 'Já usado',       sev: 'warning' },
            not_found:    { titulo: 'Não encontrado',      icone: 'pi-ban',                  cor: '#dc2626', label: 'Não encontrado', sev: 'danger'  },
            cancelled:    { titulo: 'Ingresso cancelado',  icone: 'pi-ban',                  cor: '#dc2626', label: 'Cancelado',      sev: 'danger'  },
            not_paid:     { titulo: 'Ingresso não pago',   icone: 'pi-exclamation-triangle', cor: '#d97706', label: 'Não pago',       sev: 'warning' },
            error:        { titulo: 'Erro',                icone: 'pi-times-circle',         cor: '#dc2626', label: 'Erro',           sev: 'danger'  },
        };
        const cfg = configs[status] ?? configs.error;
        resultado.value = { status, message, ticket, ...cfg };
        registrarHistorico(ticket ?? { code: limpo }, status, cfg.sev, cfg.label);
        contadores.erro++;
    } finally {
        validando.value  = false;
        codigoManual.value = '';
    }
}

function registrarHistorico(ticket, status, severity, statusLabel) {
    historico.value.unshift({
        code:        ticket?.code ?? '—',
        evento:      ticket?.evento ?? '—',
        status,
        statusLabel,
        severity,
        hora: new Date().toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit', second: '2-digit' }),
    });
    if (historico.value.length > 100) historico.value.pop();
}

// ── Utilitários ────────────────────────────────────────────────────────────────

const estiloResultado = computed(() => {
    const map = {
        ok:           'background:#f0fdf4;border:2px solid #4ade80',
        already_used: 'background:#fff7ed;border:2px solid #fb923c',
        not_found:    'background:#fef2f2;border:2px solid #f87171',
        cancelled:    'background:#fef2f2;border:2px solid #f87171',
        not_paid:     'background:#fffbeb;border:2px solid #fbbf24',
        error:        'background:#fef2f2;border:2px solid #f87171',
    };
    return resultado.value ? map[resultado.value.status] ?? '' : '';
});

function formatarData(d) {
    return d ? new Date(d).toLocaleString('pt-BR') : '—';
}
</script>

<style scoped>
.scanner-wrapper { animation: fadeIn .2s ease; }

.resultado-card { transition: all .3s ease; }

.slide-fade-enter-active { transition: all .3s ease; }
.slide-fade-leave-active { transition: all .2s ease; }
.slide-fade-enter-from   { opacity: 0; transform: translateY(-12px); }
.slide-fade-leave-to     { opacity: 0; transform: translateY(8px); }

@keyframes fadeIn { from { opacity: 0 } to { opacity: 1 } }

/* Remove estilo padrão da lib que conflita com PrimeVue */
:deep(#qr-scanner-container video) {
    border-radius: 8px;
}
:deep(#qr-scanner-container img) {
    display: none !important;
}
</style>