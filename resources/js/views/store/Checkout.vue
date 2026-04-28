<template>
    <div>
        <div class="flex align-items-center gap-2 mb-4">
            <Button icon="pi pi-arrow-left" class="p-button-text" @click="$router.back()" />
            <h3 class="m-0">Finalizar Compra</h3>
        </div>

        <div class="grid">
            <div class="col-12 lg:col-7">
                <div class="card">
                    <h5 class="mt-0"><i class="pi pi-user mr-2" style="color:#1976d2"></i>Seus Dados</h5>
                    <!-- Banner comissário -->
                    <div v-if="isComissario" class="flex align-items-center gap-2 p-3 border-round mb-4" style="background:var(--blue-50);border:1px solid var(--blue-200)">
                        <i class="pi pi-id-card" style="color:var(--blue-600);font-size:1.2rem"></i>
                        <div>
                            <div class="font-semibold text-sm" style="color:var(--blue-800)">Venda como Comissário</div>
                            <div class="text-sm" style="color:var(--blue-700)">Informe o CPF do cliente para registrar esta venda.</div>
                        </div>
                    </div>

                    <div class="grid p-fluid">
                        <div class="col-12">
                            <div class="field">
                                <label>Nome completo <span class="p-error">*</span></label>
                                <InputText v-model="form.customer_name" :class="{ 'p-invalid': erros.customer_name }" />
                                <small class="p-error">{{ erros.customer_name }}</small>
                            </div>
                        </div>
                        <div class="col-12 md:col-6">
                            <div class="field">
                                <label>E-mail</label>
                                <InputText v-model="form.customer_email" type="email" />
                            </div>
                        </div>
                        <div class="col-12 md:col-6">
                            <div class="field">
                                <label>Telefone</label>
                                <InputMask v-model="form.customer_phone" mask="(99) 99999-9999" />
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="field mb-0">
                                <label>
                                    CPF do cliente
                                    <span v-if="isComissario" class="p-error">*</span>
                                    <span v-else class="text-color-secondary text-sm ml-1">(opcional)</span>
                                </label>
                                <InputMask v-model="form.customer_cpf" mask="999.999.999-99"
                                    placeholder="000.000.000-00" :class="{ 'p-invalid': erros.customer_cpf }" />
                                <small class="p-error" v-if="erros.customer_cpf">{{ erros.customer_cpf }}</small>
                                <small class="text-color-secondary" v-else>
                                    <i class="pi pi-lock mr-1"></i>
                                    CPF coletado somente para identificação do comprador, conforme a LGPD.
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 lg:col-5">
                <div class="card" style="position:sticky;top:80px">
                    <h5 class="mt-0">Resumo do Pedido</h5>
                    <div v-for="item in items" :key="`${item.type}-${item.id}`"
                        class="flex justify-content-between mb-2 text-sm">
                        <span>{{ item.qty }}× {{ item.name }}</span>
                        <span>R$ {{ (item.price * item.qty).toFixed(2) }}</span>
                    </div>
                    <Divider />
                    <div class="flex justify-content-between font-bold text-lg mb-4">
                        <span>Total</span>
                        <span style="color:#2e7d32">R$ {{ total.toFixed(2) }}</span>
                    </div>
                    <Button label="Confirmar Pedido" icon="pi pi-check" class="p-button-success w-full"
                        :loading="enviando" @click="confirmar" />
                </div>
            </div>
        </div>
        <Toast />
    </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import api from '@/service/ApiService';
import { useCart } from '@/composables/useCart';
import { useAuth } from '@/composables/useAuth';

const route  = useRoute();
const router = useRouter();
const toast  = useToast();
const { items, total, clear } = useCart();
const { user, isAuthenticated } = useAuth();

const enviando     = ref(false);
const isComissario = ref(false);

const form  = reactive({ customer_name: user.value?.name || '', customer_email: user.value?.email || '', customer_phone: '', customer_cpf: '' });
const erros = reactive({ customer_name: '', customer_cpf: '' });

async function verificarComissario() {
    if (!isAuthenticated.value) return;
    try {
        const { data } = await api.get('/commissioner/status');
        isComissario.value = data.is_commissioner;
    } catch {}
}

async function confirmar() {
    erros.customer_name = form.customer_name.trim() ? '' : 'Informe seu nome.';
    erros.customer_cpf  = '';

    if (isComissario.value && !form.customer_cpf.replace(/\D/g, '')) {
        erros.customer_cpf = 'O CPF do cliente é obrigatório para comissários.';
    }

    if (erros.customer_name || erros.customer_cpf || items.value.length === 0) return;

    enviando.value = true;
    try {
        const { data } = await api.post('/orders', {
            ...form,
            items: items.value.map(i => ({ type: i.type, id: i.id, qty: i.qty })),
        });
        clear();
        router.push(`/c/${route.params.slug}/pedido/${data.id}`);
    } catch (err) {
        const errosApi = err.response?.data?.errors ?? {};
        if (errosApi.customer_cpf) erros.customer_cpf = errosApi.customer_cpf[0];
        else toast.add({ severity: 'error', summary: 'Erro', detail: err.response?.data?.message || 'Erro ao criar pedido.', life: 4000 });
    } finally {
        enviando.value = false;
    }
}

onMounted(() => verificarComissario());
</script>
