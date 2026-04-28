<template>
    <div>
        <div class="flex align-items-center gap-2 mb-4">
            <Button icon="pi pi-arrow-left" class="p-button-text" @click="$router.back()" />
            <h3 class="m-0">Validar Ingressos</h3>
            <Tag v-if="isNativo" value="Camera ativa" severity="success" class="ml-2" />
            <Tag v-else value="Modo web" severity="info" class="ml-2" />
        </div>

        <!-- Painel de acao -->
        <div class="card mb-3">
            <!-- App nativo: botao que abre a camera -->
            <div v-if="isNativo" class="text-center py-3">
                <Button
                    label="Escanear QR Code do Ingresso"
                    icon="pi pi-qrcode"
                    class="p-button-success p-button-lg"
                    style="min-width:260px"
                    :loading="escaneando"
                    :disabled="validando"
                    @click="escanear"
                />
                <p class="text-color-secondary text-sm mt-3 mb-0">
                    Aponte a camera para o QR code impresso no ingresso
                </p>
            </div>

            <!-- Web: input manual do codigo -->
            <div v-else class="p-fluid" style="max-width:380px;margin:0 auto">
                <h5 class="mt-0 mb-3 text-center">Inserir codigo manualmente</h5>
                <div class="field">
                    <label>Codigo do ingresso</label>
                    <div class="flex gap-2">
                        <InputText
                            v-model="codigoManual"
                            placeholder="Ex: A1B2C3D4E5F6G7H8"
                            class="flex-1"
                            :disabled="validando"
                            @keyup.enter="validarCodigo(codigoManual)"
                        />
                        <Button
                            icon="pi pi-check"
                            class="p-button-success"
                            :loading="validando"
                            @click="validarCodigo(codigoManual)"
                        />
                    </div>
                </div>
                <small class="text-color-secondary">
                    <i class="pi pi-info-circle mr-1"></i>
                    No app mobile a camera sera usada automaticamente para ler o QR code.
                </small>
            </div>
        </div>

        <!-- Resultado da ultima validacao -->
        <transition name="fade">
            <div v-if="resultado" class="card mb-3" :style="cardStyle">
                <div class="flex align-items-center gap-3 mb-3">
                    <i :class="['text-4xl', resultado.icone]" :style="{ color: resultado.cor }"></i>
                    <div>
                        <div class="font-bold text-xl" :style="{ color: resultado.cor }">{{ resultado.titulo }}</div>
                        <div class="text-color-secondary text-sm">{{ resultado.message }}</div>
                    </div>
                </div>
                <div v-if="resultado.ticket" class="p-3 border-round" style="background:var(--surface-ground)">
                    <div class="grid">
                        <div class="col-6">
                            <div class="text-xs text-color-secondary mb-1">EVENTO</div>
                            <div class="font-semibold">{{ resultado.ticket.evento ?? '—' }}</div>
                        </div>
                        <div class="col-6">
                            <div class="text-xs text-color-secondary mb-1">LOTE</div>
                            <div class="font-semibold">{{ resultado.ticket.lote ?? '—' }}</div>
                        </div>
                        <div class="col-6 mt-2">
                            <div class="text-xs text-color-secondary mb-1">CODIGO</div>
                            <div class="font-mono text-sm">{{ resultado.ticket.code }}</div>
                        </div>
                        <div class="col-6 mt-2" v-if="resultado.ticket.used_at">
                            <div class="text-xs text-color-secondary mb-1">UTILIZADO EM</div>
                            <div class="text-sm">{{ formatarData(resultado.ticket.used_at) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </transition>

        <!-- Historico da sessao -->
        <div v-if="historico.length > 0" class="card">
            <div class="flex justify-content-between align-items-center mb-3">
                <h6 class="m-0">Historico da sessao</h6>
                <div class="flex gap-2 align-items-center">
                    <Tag :value="`${contadores.ok} ok`" severity="success" />
                    <Tag :value="`${contadores.erro} erros`" severity="danger" />
                    <Button icon="pi pi-trash" class="p-button-text p-button-sm p-button-secondary"
                        v-tooltip.top="'Limpar historico'" @click="historico = []; contadores = { ok: 0, erro: 0 }" />
                </div>
            </div>
            <DataTable :value="historico" size="small" stripedRows>
                <Column field="code" header="Codigo">
                    <template #body="{ data }">
                        <span class="font-mono text-sm">{{ data.code }}</span>
                    </template>
                </Column>
                <Column field="evento" header="Evento" />
                <Column field="status" header="Status" style="width:120px">
                    <template #body="{ data }">
                        <Tag :value="data.statusLabel" :severity="data.severity" />
                    </template>
                </Column>
                <Column field="hora" header="Hora" style="width:80px">
                    <template #body="{ data }">
                        <span class="text-sm">{{ data.hora }}</span>
                    </template>
                </Column>
            </DataTable>
        </div>

        <Toast />
    </div>
</template>

<script setup>
import { computed, reactive, ref } from 'vue';
import { useToast } from 'primevue/usetoast';
import api from '@/service/ApiService';

const toast = useToast();

// Detecta se esta rodando como app nativo (Capacitor) ou no browser
const isNativo = computed(() => {
    return typeof window !== 'undefined' && !!(window.Capacitor?.isNativePlatform?.());
});

const escaneando  = ref(false);
const validando   = ref(false);
const codigoManual = ref('');
const resultado   = ref(null);
const historico   = ref([]);
const contadores  = reactive({ ok: 0, erro: 0 });

const cardStyle = computed(() => {
    if (!resultado.value) return {};
    const cores = {
        ok:           'background: #f0fdf4; border: 2px solid #4ade80;',
        already_used: 'background: #fff7ed; border: 2px solid #fb923c;',
        not_found:    'background: #fef2f2; border: 2px solid #f87171;',
        cancelled:    'background: #fef2f2; border: 2px solid #f87171;',
        not_paid:     'background: #fffbeb; border: 2px solid #fbbf24;',
        error:        'background: #fef2f2; border: 2px solid #f87171;',
    };
    return { cssText: cores[resultado.value.status] || '' };
});

async function escanear() {
    if (!isNativo.value) return;
    escaneando.value = true;
    try {
        const { BarcodeScanner } = await import('@capacitor-community/barcode-scanner');

        // Solicita permissao de camera
        const status = await BarcodeScanner.checkPermission({ force: true });
        if (!status.granted) {
            toast.add({ severity: 'warn', summary: 'Permissao necessaria', detail: 'Permita o acesso a camera nas configuracoes do dispositivo.', life: 4000 });
            return;
        }

        // Prepara e escaneia
        document.body.classList.add('scanner-active');
        BarcodeScanner.hideBackground();

        const result = await BarcodeScanner.startScan();

        document.body.classList.remove('scanner-active');
        BarcodeScanner.showBackground();

        if (result.hasContent) {
            await validarCodigo(result.content);
        }
    } catch (err) {
        document.body.classList.remove('scanner-active');
        toast.add({ severity: 'error', summary: 'Erro na camera', detail: err.message ?? 'Nao foi possivel acessar a camera.', life: 3000 });
    } finally {
        escaneando.value = false;
    }
}

async function validarCodigo(code) {
    const codigoLimpo = (code ?? '').trim().toUpperCase();
    if (!codigoLimpo) {
        toast.add({ severity: 'warn', summary: 'Codigo vazio', detail: 'Informe ou escaneie um codigo de ingresso.', life: 2500 });
        return;
    }

    validando.value = true;
    resultado.value = null;

    try {
        const { data } = await api.post('/admin/tickets/validate', { code: codigoLimpo });
        resultado.value = {
            status:  'ok',
            titulo:  'Entrada liberada!',
            message: data.message,
            icone:   'pi pi-check-circle',
            cor:     '#16a34a',
            ticket:  data.ticket,
        };
        adicionarHistorico(data.ticket, 'ok', 'success');
        contadores.ok++;
    } catch (err) {
        const status  = err.response?.data?.status ?? 'error';
        const message = err.response?.data?.message ?? 'Erro ao validar ingresso.';
        const ticket  = err.response?.data?.ticket ?? null;

        const config = {
            already_used: { titulo: 'Ja utilizado',      icone: 'pi pi-times-circle', cor: '#ea580c' },
            not_found:    { titulo: 'Nao encontrado',    icone: 'pi pi-ban',           cor: '#dc2626' },
            cancelled:    { titulo: 'Ingresso cancelado', icone: 'pi pi-ban',          cor: '#dc2626' },
            not_paid:     { titulo: 'Nao pago',          icone: 'pi pi-exclamation-triangle', cor: '#d97706' },
            error:        { titulo: 'Erro',              icone: 'pi pi-times-circle', cor: '#dc2626' },
        };

        const cfg = config[status] ?? config.error;
        resultado.value = { status, message, ticket, ...cfg };
        adicionarHistorico(ticket ?? { code: codigoLimpo }, status, 'danger');
        contadores.erro++;
    } finally {
        validando.value  = false;
        codigoManual.value = '';
    }
}

function adicionarHistorico(ticket, status, severity) {
    const labels = {
        ok:           'Liberado',
        already_used: 'Ja usado',
        not_found:    'Nao encontrado',
        cancelled:    'Cancelado',
        not_paid:     'Nao pago',
        error:        'Erro',
    };
    historico.value.unshift({
        code:        ticket?.code ?? '—',
        evento:      ticket?.evento ?? '—',
        status,
        statusLabel: labels[status] ?? status,
        severity,
        hora:        new Date().toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit', second: '2-digit' }),
    });
    // Manter no maximo 50 registros na sessao
    if (historico.value.length > 50) historico.value.pop();
}

function formatarData(d) {
    if (!d) return '—';
    return new Date(d).toLocaleString('pt-BR');
}
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity .3s, transform .3s; }
.fade-enter-from, .fade-leave-to { opacity: 0; transform: translateY(-8px); }
</style>