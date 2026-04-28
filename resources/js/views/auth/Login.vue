<template>
    <div class="flex align-items-center justify-content-center min-h-screen" style="background:#f5f5f5">
        <div class="card" style="width:100%;max-width:420px">
            <div class="text-center mb-4">
                <i class="pi pi-bolt text-5xl mb-2 block" style="color:#1976d2"></i>
                <h2 class="m-0">Lojin</h2>
                <p class="text-color-secondary mt-1">Faça login na sua conta</p>
            </div>

            <div class="p-fluid">
                <div class="field">
                    <label>E-mail</label>
                    <InputText v-model="form.email" type="email" @keyup.enter="login"
                        :class="{ 'p-invalid': erro }" />
                </div>
                <div class="field">
                    <label>Senha</label>
                    <Password v-model="form.password" :feedback="false" toggleMask @keyup.enter="login"
                        :class="{ 'p-invalid': erro }" />
                    <small class="p-error">{{ erro }}</small>
                </div>
                <Button label="Entrar" icon="pi pi-sign-in" class="w-full p-button-success mt-2"
                    :loading="loading" @click="login" />
            </div>

            <div class="text-center mt-3 text-sm text-color-secondary">
                Não tem conta?
                <router-link to="/register" class="font-semibold" style="color:#1976d2">Registre-se</router-link>
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
const { login: authLogin, isAdmin, isSuperAdmin } = useAuth();

const form    = reactive({ email:'', password:'' });
const loading = ref(false);
const erro    = ref('');

async function login() {
    erro.value = '';
    loading.value = true;
    try {
        await authLogin(form.email, form.password);
        const redirect = route.query.redirect;
        if (redirect) { router.push(redirect); return; }
        if (isSuperAdmin.value)      router.push('/super-admin');
        else if (isAdmin.value)      router.push('/admin');
        else                         router.push('/');
    } catch {
        erro.value = 'E-mail ou senha inválidos.';
    } finally { loading.value = false; }
}
</script>
