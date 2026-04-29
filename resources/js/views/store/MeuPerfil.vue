<template>
    <div style="max-width:540px;margin:0 auto">
        <div class="flex align-items-center gap-2 mb-4">
            <Button icon="pi pi-arrow-left" class="p-button-text" @click="$router.back()" />
            <h3 class="m-0">Meu Perfil</h3>
        </div>

        <!-- Alerta de perfil incompleto -->
        <div v-if="camposFaltando.length > 0" class="flex align-items-start gap-3 p-3 border-round mb-4"
            style="background:#fff8e1;border:1px solid #ffe082">
            <i class="pi pi-exclamation-triangle mt-1" style="color:#f57f17"></i>
            <div class="text-sm" style="color:#6d4c00">
                <strong>Complete seu cadastro</strong> para realizar compras:
                <strong>{{ camposFaltando.join(', ') }}</strong>.
            </div>
        </div>

        <div class="card p-fluid">
            <h5 class="mt-0">Dados pessoais</h5>

            <div class="field">
                <label>Nome completo <span class="p-error">*</span></label>
                <InputText v-model="form.name" :class="{ 'p-invalid': erros.name }" />
                <small class="p-error">{{ erros.name }}</small>
            </div>

            <div class="field">
                <label>E-mail</label>
                <InputText :value="user?.email" disabled class="p-disabled" />
                <small class="text-color-secondary">O e-mail não pode ser alterado aqui.</small>
            </div>

            <div class="field">
                <label>Telefone <span class="p-error">*</span></label>
                <InputMask v-model="form.phone" mask="(99) 99999-9999"
                    placeholder="(00) 00000-0000" :class="{ 'p-invalid': erros.phone }" />
                <small class="p-error">{{ erros.phone }}</small>
            </div>

            <div class="field">
                <label>CPF <span class="text-color-secondary text-sm">(opcional)</span></label>
                <InputMask v-model="form.cpf" mask="999.999.999-99"
                    placeholder="000.000.000-00" :class="{ 'p-invalid': erros.cpf }" />
                <small class="p-error" v-if="erros.cpf">{{ erros.cpf }}</small>
                <small class="text-color-secondary" v-else>
                    <i class="pi pi-lock mr-1"></i>
                    Usado apenas para identificação, conforme a LGPD.
                </small>
            </div>

            <div class="field mb-0">
                <label>Data de nascimento <span class="text-color-secondary text-sm">(opcional)</span></label>
                <InputMask v-model="form.birth_date_display" mask="99/99/9999"
                    placeholder="DD/MM/AAAA" @blur="parseBirthDate" />
            </div>

            <div class="flex justify-content-end mt-4">
                <Button label="Salvar dados" icon="pi pi-check"
                    class="p-button-success" :loading="salvando" @click="salvar" />
            </div>
        </div>
        <Toast />
    </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import { useAuth } from '@/composables/useAuth';
import api from '@/service/ApiService';

const router  = useRouter();
const toast   = useToast();
const { user } = useAuth();

const salvando       = ref(false);
const camposFaltando = ref([]);

const form = reactive({
    name: '', phone: '', cpf: '',
    birth_date: '', birth_date_display: '',
});
const erros = reactive({ name: '', phone: '', cpf: '' });

function parseBirthDate() {
    const parts = form.birth_date_display.split('/');
    form.birth_date = (parts.length === 3 && parts[2].length === 4)
        ? `${parts[2]}-${parts[1]}-${parts[0]}`
        : '';
}

async function carregar() {
    try {
        const { data } = await api.get('/user/profile');
        camposFaltando.value = data.campos_faltando ?? [];
        form.name  = user.value?.name  ?? '';
        form.phone = user.value?.phone ?? '';
        // CPF mascarado do perfil — deixa vazio para redigitar se quiser alterar
    } catch {}
}

async function salvar() {
    Object.assign(erros, { name: '', phone: '', cpf: '' });
    salvando.value = true;
    try {
        parseBirthDate();
        const payload = {
            name:       form.name,
            phone:      form.phone,
            cpf:        form.cpf  || undefined,
            birth_date: form.birth_date || undefined,
        };
        await api.put('/user/profile', payload);

        // Atualiza localStorage do usuário
        const updated = { ...user.value, name: form.name, phone: form.phone };
        localStorage.setItem('auth_user', JSON.stringify(updated));
        user.value = updated;

        toast.add({ severity: 'success', summary: 'Dados salvos!', life: 2500 });
        camposFaltando.value = [];
        setTimeout(() => router.back(), 800);
    } catch (err) {
        const e = err.response?.data?.errors ?? {};
        Object.keys(e).forEach(k => { if (k in erros) erros[k] = e[k][0]; });
        if (!Object.values(erros).some(Boolean))
            toast.add({ severity: 'error', summary: 'Erro', detail: err.response?.data?.message ?? 'Erro ao salvar.', life: 3000 });
    } finally { salvando.value = false; }
}

onMounted(() => carregar());
</script>