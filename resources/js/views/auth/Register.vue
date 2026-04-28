<template>
    <div class="flex align-items-center justify-content-center min-h-screen" style="background:#f5f5f5">
        <div class="card" style="width:100%;max-width:420px">
            <div class="text-center mb-4">
                <i class="pi pi-bolt text-5xl mb-2 block" style="color:#1976d2"></i>
                <h2 class="m-0">Crie sua conta</h2>
            </div>
            <div class="p-fluid">
                <div class="field">
                    <label>Nome</label>
                    <InputText v-model="form.name" :class="{ 'p-invalid': erros.name }" />
                    <small class="p-error">{{ erros.name }}</small>
                </div>
                <div class="field">
                    <label>E-mail</label>
                    <InputText v-model="form.email" type="email" :class="{ 'p-invalid': erros.email }" />
                    <small class="p-error">{{ erros.email }}</small>
                </div>
                <div class="field">
                    <label>Senha</label>
                    <Password v-model="form.password" :feedback="false" toggleMask :class="{ 'p-invalid': erros.password }" />
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

const form    = reactive({ name:'', email:'', password:'' });
const erros   = reactive({ name:'', email:'', password:'' });
const loading = ref(false);

async function registrar() {
    Object.assign(erros, { name:'', email:'', password:'' });
    loading.value = true;
    try {
        await register(form.name, form.email, form.password);
        router.push(route.query.redirect || '/');
    } catch (err) {
        const e = err.response?.data?.errors ?? {};
        Object.keys(e).forEach(k => { if (k in erros) erros[k] = e[k][0]; });
    } finally { loading.value = false; }
}
</script>
