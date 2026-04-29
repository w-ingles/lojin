<template>
    <div class="flex align-items-center justify-content-center min-h-screen py-4" style="background:#f5f5f5">
        <div class="card" style="width:100%;max-width:480px">

            <!-- Cabeçalho -->
            <div class="text-center mb-4">
                <i class="pi pi-bolt text-5xl mb-2 block" style="color:#1976d2"></i>
                <h2 class="m-0">{{ passo === 1 ? 'Crie sua conta' : 'Confirme seu e-mail' }}</h2>
                <p class="text-color-secondary mt-1 mb-0">
                    {{ passo === 1
                        ? 'Preencha os dados abaixo para continuar'
                        : `Insira o código enviado para ${form.email}` }}
                </p>
            </div>

            <!-- Barra de progresso -->
            <div class="flex gap-2 mb-4">
                <div class="flex-1 border-round" style="height:5px"
                    :style="{ background: passo >= 1 ? '#1976d2' : '#e0e0e0' }"></div>
                <div class="flex-1 border-round" style="height:5px"
                    :style="{ background: passo >= 2 ? '#1976d2' : '#e0e0e0' }"></div>
            </div>

            <!-- ── PASSO 1: dados ─────────────────────────────────────────── -->
            <div v-if="passo === 1" class="p-fluid">
                <div class="field">
                    <label>Nome completo <span class="p-error">*</span></label>
                    <InputText v-model="form.name" :class="{ 'p-invalid': erros.name }"
                        placeholder="Seu nome completo" />
                    <small class="p-error">{{ erros.name }}</small>
                </div>
                <div class="grid">
                    <div class="col-12 md:col-6">
                        <div class="field">
                            <label>CPF <span class="p-error">*</span></label>
                            <InputMask v-model="form.cpf" mask="999.999.999-99"
                                placeholder="000.000.000-00" :class="{ 'p-invalid': erros.cpf }" />
                            <small class="p-error">{{ erros.cpf }}</small>
                        </div>
                    </div>
                    <div class="col-12 md:col-6">
                        <div class="field">
                            <label>Data de nascimento <span class="p-error">*</span></label>
                            <InputMask v-model="form.birth_date_display" mask="99/99/9999"
                                placeholder="DD/MM/AAAA" :class="{ 'p-invalid': erros.birth_date }"
                                @blur="parseBirthDate" />
                            <small class="p-error">{{ erros.birth_date }}</small>
                        </div>
                    </div>
                </div>
                <div class="field">
                    <label>E-mail <span class="p-error">*</span></label>
                    <InputText v-model="form.email" type="email"
                        :class="{ 'p-invalid': erros.email }" placeholder="seu@email.com" />
                    <small class="p-error">{{ erros.email }}</small>
                </div>
                <div class="field">
                    <label>Telefone <span class="p-error">*</span></label>
                    <InputMask v-model="form.phone" mask="(99) 99999-9999"
                        placeholder="(00) 00000-0000" :class="{ 'p-invalid': erros.phone }" />
                    <small class="p-error">{{ erros.phone }}</small>
                </div>
                <div class="field">
                    <label>Senha <span class="p-error">*</span></label>
                    <Password v-model="form.password" :feedback="true" toggleMask
                        :class="{ 'p-invalid': erros.password }" placeholder="Mínimo 8 caracteres" />
                    <small class="p-error">{{ erros.password }}</small>
                </div>
                <small class="text-color-secondary block mb-3">
                    <i class="pi pi-lock mr-1"></i>
                    CPF e dados pessoais coletados apenas para identificação, conforme a LGPD.
                </small>
                <Button label="Continuar" icon="pi pi-arrow-right" iconPos="right"
                    class="w-full p-button-success" :loading="loading" @click="enviarCodigo" />
            </div>

            <!-- ── PASSO 2: código ────────────────────────────────────────── -->
            <div v-else>
                <div class="flex align-items-center gap-2 p-3 border-round mb-4"
                    style="background:#e3f2fd;border:1px solid #90caf9">
                    <i class="pi pi-envelope" style="color:#1565c0;font-size:1.3rem"></i>
                    <div class="text-sm" style="color:#0d47a1">
                        Um código de 6 dígitos foi enviado para
                        <strong>{{ form.email }}</strong>.<br>
                        Verifique sua caixa de entrada e a pasta de spam.
                    </div>
                </div>

                <!-- Input OTP customizado — compatível com PrimeVue 3 -->
                <div class="field">
                    <label class="block mb-2">Código de verificação <span class="p-error">*</span></label>
                    <div class="otp-wrapper">
                        <input
                            v-for="(_, i) in 6"
                            :key="i"
                            :ref="el => { if (el) otpRefs[i] = el }"
                            v-model="otpDigits[i]"
                            class="otp-box"
                            :class="{ 'otp-box--error': erros.code, 'otp-box--filled': otpDigits[i] }"
                            type="text"
                            inputmode="numeric"
                            maxlength="1"
                            autocomplete="one-time-code"
                            @input="onOtpInput(i, $event)"
                            @keydown="onOtpKeydown(i, $event)"
                            @paste="onOtpPaste($event)"
                            @focus="$event.target.select()"
                        />
                    </div>
                    <small class="p-error block mt-2">{{ erros.code }}</small>
                </div>

                <Button label="Confirmar e criar conta" icon="pi pi-check"
                    class="w-full p-button-success mb-3" :loading="loading" @click="confirmar" />

                <div class="flex align-items-center justify-content-between">
                    <Button label="Voltar" icon="pi pi-arrow-left" class="p-button-text p-button-sm"
                        :disabled="loading" @click="voltarPasso1" />
                    <Button
                        :label="cooldown > 0 ? `Reenviar (${cooldown}s)` : 'Reenviar código'"
                        icon="pi pi-refresh" class="p-button-text p-button-sm"
                        :disabled="cooldown > 0 || loading" @click="reenviar" />
                </div>
            </div>

            <div class="text-center mt-3 text-sm text-color-secondary">
                Já tem conta?
                <router-link to="/login" class="font-semibold" style="color:#1976d2">Entrar</router-link>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, onUnmounted, reactive, ref } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuth } from '@/composables/useAuth';
import api from '@/service/ApiService';

const router = useRouter();
const route  = useRoute();
const { setSession } = useAuth();

const passo        = ref(1);
const loading      = ref(false);
const cooldown     = ref(0);
const pendingToken = ref('');
let cooldownTimer  = null;

// ── OTP ───────────────────────────────────────────────────────────────────────
const otpDigits = ref(['', '', '', '', '', '']);
const otpRefs   = ref([]);
const codigo    = computed(() => otpDigits.value.join(''));

function resetOtp() {
    otpDigits.value = ['', '', '', '', '', ''];
}

function onOtpInput(index, e) {
    const raw = e.target.value.replace(/\D/g, '');
    otpDigits.value[index] = raw.slice(-1); // garante 1 dígito
    if (raw && index < 5) otpRefs.value[index + 1]?.focus();
}

function onOtpKeydown(index, e) {
    if (e.key === 'Backspace') {
        if (otpDigits.value[index]) {
            otpDigits.value[index] = '';
        } else if (index > 0) {
            otpDigits.value[index - 1] = '';
            otpRefs.value[index - 1]?.focus();
        }
        e.preventDefault();
    } else if (e.key === 'ArrowLeft' && index > 0) {
        otpRefs.value[index - 1]?.focus();
    } else if (e.key === 'ArrowRight' && index < 5) {
        otpRefs.value[index + 1]?.focus();
    } else if (e.key === 'Enter') {
        confirmar();
    }
}

function onOtpPaste(e) {
    const text = (e.clipboardData || window.clipboardData)
        .getData('text').replace(/\D/g, '').slice(0, 6);
    if (!text) return;
    e.preventDefault();
    text.split('').forEach((char, i) => { otpDigits.value[i] = char; });
    otpRefs.value[Math.min(text.length, 5)]?.focus();
}

// ── Formulário ────────────────────────────────────────────────────────────────
const form = reactive({
    name: '', email: '', cpf: '', phone: '',
    birth_date: '',
    birth_date_display: '',
    password: '',
});
const erros = reactive({
    name: '', email: '', cpf: '', phone: '', birth_date: '', password: '', code: '',
});

function parseBirthDate() {
    const parts = form.birth_date_display.split('/');
    if (parts.length === 3 && parts[2].length === 4) {
        form.birth_date = `${parts[2]}-${parts[1]}-${parts[0]}`;
    } else {
        form.birth_date = '';
    }
}

function voltarPasso1() {
    passo.value = 1;
    resetOtp();
    Object.assign(erros, { name: '', email: '', cpf: '', phone: '', birth_date: '', password: '', code: '' });
}

function iniciarCooldown() {
    cooldown.value = 60;
    cooldownTimer = setInterval(() => {
        if (--cooldown.value <= 0) clearInterval(cooldownTimer);
    }, 1000);
}

async function enviarCodigo() {
    Object.assign(erros, { name: '', email: '', cpf: '', phone: '', birth_date: '', password: '' });
    loading.value = true;
    try {
        parseBirthDate();
        const { data } = await api.post('/auth/pre-register', {
            name:       form.name,
            email:      form.email,
            cpf:        form.cpf,
            phone:      form.phone,
            birth_date: form.birth_date,
            password:   form.password,
        });
        pendingToken.value = data.pending_token;
        passo.value = 2;
        iniciarCooldown();
    } catch (err) {
        const e = err.response?.data?.errors ?? {};
        Object.keys(e).forEach(k => { if (k in erros) erros[k] = e[k][0]; });
    } finally { loading.value = false; }
}

async function confirmar() {
    erros.code = '';
    if (codigo.value.length < 6) {
        erros.code = 'Digite os 6 dígitos do código.';
        return;
    }
    loading.value = true;
    try {
        const { data } = await api.post('/auth/confirm-register', {
            pending_token: pendingToken.value,
            code:          codigo.value,
        });
        setSession(data);
        router.push(route.query.redirect || '/');
    } catch (err) {
        const msg = err.response?.data?.errors?.code?.[0]
            ?? err.response?.data?.message
            ?? 'Erro ao verificar.';
        erros.code = msg;
        if (msg.includes('Reinicie')) setTimeout(() => voltarPasso1(), 2500);
    } finally { loading.value = false; }
}

async function reenviar() {
    loading.value = true;
    try {
        const { data } = await api.post('/auth/resend-code', { pending_token: pendingToken.value });
        pendingToken.value = data.pending_token;
        resetOtp();
        erros.code = '';
        iniciarCooldown();
    } catch (err) {
        erros.code = err.response?.data?.message ?? 'Erro ao reenviar.';
    } finally { loading.value = false; }
}

onUnmounted(() => clearInterval(cooldownTimer));
</script>

<style scoped>
.otp-wrapper {
    display: flex;
    gap: 10px;
    justify-content: center;
}

.otp-box {
    width: 52px;
    height: 56px;
    text-align: center;
    font-size: 1.5rem;
    font-weight: 700;
    border: 2px solid var(--surface-border, #dee2e6);
    border-radius: 8px;
    background: var(--surface-ground, #f8f9fa);
    color: var(--text-color, #1a1a1a);
    outline: none;
    transition: border-color .15s, box-shadow .15s;
    caret-color: #1976d2;
}

.otp-box:focus {
    border-color: #1976d2;
    box-shadow: 0 0 0 3px rgba(25, 118, 210, .18);
    background: #fff;
}

.otp-box--filled {
    border-color: #1976d2;
    background: #f0f7ff;
}

.otp-box--error {
    border-color: #e24c4c !important;
}
</style>