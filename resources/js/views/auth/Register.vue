<template>
    <div class="flex align-items-center justify-content-center min-h-screen" style="background:#f5f5f5">
        <div class="card" style="width:100%;max-width:440px">
            <div class="text-center mb-4">
                <i class="pi pi-bolt text-5xl mb-2 block" style="color:#1976d2"></i>
                <h2 class="m-0">Crie sua conta</h2>
            </div>
            <div class="p-fluid">
                <div class="field">
                    <label>Nome completo <span class="p-error">*</span></label>
                    <InputText v-model="form.name" :class="{ 'p-invalid': erros.name }" placeholder="Seu nome completo" />
                    <small class="p-error">{{ erros.name }}</small>
                </div>
                <div class="field">
                    <label>CPF <span class="p-error">*</span></label>
                    <InputMask v-model="form.cpf" mask="999.999.999-99" placeholder="000.000.000-00"
                        :class="{ 'p-invalid': erros.cpf }" />
                    <small class="p-error" v-if="erros.cpf">{{ erros.cpf }}</small>
                    <small class="text-color-secondary" v-else>
                        <i class="pi pi-lock mr-1"></i>Usado apenas para identificação, conforme a LGPD.
                    </small>
                </div>
                <div class="field">
                    <label>E-mail <span class="p-error">*</span></label>
                    <InputText v-model="form.email" type="email" :class="{ 'p-invalid': erros.email }" placeholder="seu@email.com" />
                    <small class="p-error">{{ erros.email }}</small>
                </div>
                <div class="field">
                    <label>Senha <span class="p-error">*</span></label>
                    <Password v-model="form.password" :feedback="false" toggleMask :class="{ 'p-invalid': erros.password }" placeholder="Mínimo 8 caracteres" />
                    <small class="p-error">{{ erros.password }}</small>
                </div>
                <Button label="Criar Conta" icon="pi pi-user-plus" class="w-full p-button-success mt-2"
                    :loading="loading" @click="registrar" />
            </div>
            <div class="text-center mt-3 text-sm text-color-secondary">
                Já tem conta? <router-link to="/login" class="font-semibold" style="color:#1976d2">Entrar</router-link>
            </div>
        </div>
    </div>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuth } from '@/composables/useAuth';

const router = useRouter();
const route  = useRoute();
const { register } = useAuth();

const form    = reactive({ name: '', cpf: '', email: '', password: '' });
const erros   = reactive({ name: '', cpf: '', email: '', password: '' });
const loading = ref(false);

async function registrar() {
    Object.assign(erros, { name: '', cpf: '', email: '', password: '' });
    loading.value = true;
    try {
        await register(form.name, form.email, form.cpf, form.password);
        router.push(route.query.redirect || '/');
    } catch (err) {
        const e = err.response?.data?.errors ?? {};
        Object.keys(e).forEach(k => { if (k in erros) erros[k] = e[k][0]; });
    } finally { loading.value = false; }
}
</script>