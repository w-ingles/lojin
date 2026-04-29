<template>
    <div class="grid">

        <!-- Banner -->
        <div class="col-12">
            <div class="card p-0 overflow-hidden">
                <!-- Preview do banner -->
                <div class="banner-area" :style="bannerStyle">
                    <div v-if="!tenant?.banner_url && !bannerPreview" class="banner-placeholder">
                        <i class="pi pi-image text-5xl mb-2 block" style="opacity:.4"></i>
                        <span style="opacity:.6">Nenhum banner cadastrado</span>
                    </div>
                    <img v-else :src="bannerPreview || tenant.banner_url" alt="Banner" class="banner-img" />

                    <!-- Overlay de ações -->
                    <div class="banner-overlay">
                        <label class="banner-btn" for="input-banner">
                            <i class="pi pi-upload mr-2"></i>
                            {{ tenant?.banner_url ? 'Trocar banner' : 'Enviar banner' }}
                        </label>
                        <input id="input-banner" type="file" accept="image/jpeg,image/png,image/webp"
                            class="hidden" @change="selecionarBanner" />
                        <button v-if="tenant?.banner_url" class="banner-btn banner-btn-danger"
                            @click="confirmarRemoverBanner">
                            <i class="pi pi-trash mr-2"></i>Remover
                        </button>
                    </div>
                </div>

                <!-- Confirmação de upload pendente -->
                <div v-if="bannerPreview" class="flex align-items-center gap-3 p-3" style="background:#f0fdf4;border-top:2px solid #4ade80">
                    <i class="pi pi-check-circle" style="color:#16a34a;font-size:1.3rem"></i>
                    <span class="text-sm flex-1">Novo banner selecionado. Clique em <strong>Salvar Banner</strong> para confirmar.</span>
                    <Button label="Salvar Banner" icon="pi pi-cloud-upload" class="p-button-success p-button-sm"
                        :loading="salvandoBanner" @click="salvarBanner" />
                    <Button label="Cancelar" class="p-button-text p-button-sm"
                        :disabled="salvandoBanner" @click="cancelarBanner" />
                </div>

                <!-- Info básica sobre o banner -->
                <div class="p-3 pt-2 pb-2 flex align-items-center gap-2" style="background:var(--surface-ground)">
                    <i class="pi pi-info-circle text-sm text-color-secondary"></i>
                    <small class="text-color-secondary">
                        Banner recomendado: 1200 × 400 px · JPEG, PNG ou WebP · máx. 5 MB.
                        Aparece para todos no catálogo público.
                    </small>
                </div>
            </div>
        </div>

        <!-- Logo + Informações -->
        <div class="col-12 md:col-4">
            <div class="card h-full">
                <h5 class="mt-0">Logo da Atlética</h5>

                <!-- Preview do logo -->
                <div class="logo-area mb-3">
                    <div v-if="!tenant?.logo_url && !logoPreview" class="logo-placeholder">
                        <i class="pi pi-bolt text-4xl" style="color:rgba(255,255,255,.7)"></i>
                    </div>
                    <img v-else :src="logoPreview || tenant.logo_url" alt="Logo" class="logo-img" />
                </div>

                <div class="flex flex-column gap-2">
                    <label class="p-button p-button-outlined w-full text-center cursor-pointer" for="input-logo"
                        style="justify-content:center">
                        <i class="pi pi-upload mr-2"></i>
                        {{ tenant?.logo_url ? 'Trocar logo' : 'Enviar logo' }}
                    </label>
                    <input id="input-logo" type="file" accept="image/jpeg,image/png,image/webp"
                        class="hidden" @change="selecionarLogo" />

                    <Button v-if="logoPreview" label="Salvar Logo" icon="pi pi-cloud-upload"
                        class="p-button-success w-full" :loading="salvandoLogo" @click="salvarLogo" />
                    <Button v-if="logoPreview" label="Cancelar" class="p-button-text w-full p-button-sm"
                        :disabled="salvandoLogo" @click="cancelarLogo" />
                    <Button v-if="tenant?.logo_url && !logoPreview" label="Remover logo"
                        icon="pi pi-trash" class="p-button-text p-button-danger w-full p-button-sm"
                        @click="confirmarRemoverLogo" />
                </div>

                <small class="text-color-secondary block mt-2">Recomendado: 200 × 200 px · máx. 2 MB.</small>
            </div>
        </div>

        <!-- Informações da atlética -->
        <div class="col-12 md:col-8">
            <div class="card h-full">
                <h5 class="mt-0">Informações da Atlética</h5>
                <div class="grid p-fluid">
                    <div class="col-12">
                        <div class="field">
                            <label>Nome <span class="p-error">*</span></label>
                            <InputText v-model="form.name" :class="{ 'p-invalid': erros.name }" />
                            <small class="p-error">{{ erros.name }}</small>
                        </div>
                    </div>
                    <div class="col-12 md:col-6">
                        <div class="field">
                            <label>E-mail</label>
                            <InputText v-model="form.email" type="email" />
                        </div>
                    </div>
                    <div class="col-12 md:col-6">
                        <div class="field">
                            <label>Telefone</label>
                            <InputMask v-model="form.phone" mask="(99) 99999-9999" />
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="field mb-0">
                            <label>Descrição</label>
                            <Textarea v-model="form.description" rows="4" autoResize
                                placeholder="Apresente sua atlética para os clientes..." />
                        </div>
                    </div>
                </div>

                <div class="flex justify-content-end mt-4">
                    <Button label="Salvar Informações" icon="pi pi-check"
                        class="p-button-success" :loading="salvandoInfo" @click="salvarInfo" />
                </div>
            </div>
        </div>

        <ConfirmDialog />
        <Toast />
    </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import { useConfirm } from 'primevue/useconfirm';
import { useToast }   from 'primevue/usetoast';
import api from '@/service/ApiService';

const confirm = useConfirm();
const toast   = useToast();

const tenant        = ref(null);
const salvandoInfo  = ref(false);
const salvandoBanner = ref(false);
const salvandoLogo  = ref(false);

const bannerPreview = ref(null);
const bannerFile    = ref(null);
const logoPreview   = ref(null);
const logoFile      = ref(null);

const form  = reactive({ name: '', email: '', phone: '', description: '' });
const erros = reactive({ name: '' });

const bannerStyle = {
    minHeight: '220px',
    background: 'linear-gradient(135deg, #1976d2 0%, #1565c0 100%)',
    position: 'relative',
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'center',
};

// ── Carregar ──────────────────────────────────────────────────────────────────

async function carregar() {
    try {
        const { data } = await api.get('/admin/tenant/profile');
        tenant.value = data;
        Object.assign(form, {
            name:        data.name        ?? '',
            email:       data.email       ?? '',
            phone:       data.phone       ?? '',
            description: data.description ?? '',
        });
    } catch {
        toast.add({ severity: 'error', summary: 'Erro', detail: 'Não foi possível carregar o perfil.', life: 3000 });
    }
}

// ── Banner ────────────────────────────────────────────────────────────────────

function selecionarBanner(e) {
    const file = e.target.files[0];
    if (!file) return;
    bannerFile.value    = file;
    bannerPreview.value = URL.createObjectURL(file);
    e.target.value = '';
}

function cancelarBanner() {
    if (bannerPreview.value) URL.revokeObjectURL(bannerPreview.value);
    bannerPreview.value = null;
    bannerFile.value    = null;
}

async function salvarBanner() {
    if (!bannerFile.value) return;
    salvandoBanner.value = true;
    try {
        const fd = new FormData();
        fd.append('banner', bannerFile.value);
        const { data } = await api.post('/admin/tenant/profile/banner', fd, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        tenant.value = data;
        cancelarBanner();
        toast.add({ severity: 'success', summary: 'Banner atualizado!', detail: 'O banner já está visível no catálogo.', life: 3000 });
    } catch (err) {
        const msg = err.response?.data?.errors?.banner?.[0] ?? err.response?.data?.message ?? 'Erro ao salvar banner.';
        toast.add({ severity: 'error', summary: 'Erro', detail: msg, life: 4000 });
    } finally { salvandoBanner.value = false; }
}

function confirmarRemoverBanner() {
    confirm.require({
        message: 'Deseja remover o banner? Ele deixará de aparecer no catálogo.',
        header: 'Remover banner',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'Sim, remover',
        rejectLabel: 'Cancelar',
        acceptClass: 'p-button-danger',
        accept: removerBanner,
    });
}

async function removerBanner() {
    try {
        const { data } = await api.delete('/admin/tenant/profile/banner');
        tenant.value = data;
        toast.add({ severity: 'info', summary: 'Banner removido.', life: 2500 });
    } catch {
        toast.add({ severity: 'error', summary: 'Erro ao remover.', life: 3000 });
    }
}

// ── Logo ──────────────────────────────────────────────────────────────────────

function selecionarLogo(e) {
    const file = e.target.files[0];
    if (!file) return;
    logoFile.value    = file;
    logoPreview.value = URL.createObjectURL(file);
    e.target.value = '';
}

function cancelarLogo() {
    if (logoPreview.value) URL.revokeObjectURL(logoPreview.value);
    logoPreview.value = null;
    logoFile.value    = null;
}

async function salvarLogo() {
    if (!logoFile.value) return;
    salvandoLogo.value = true;
    try {
        const fd = new FormData();
        fd.append('logo', logoFile.value);
        const { data } = await api.post('/admin/tenant/profile/logo', fd, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        tenant.value = data;
        cancelarLogo();
        toast.add({ severity: 'success', summary: 'Logo atualizado!', life: 2500 });
    } catch (err) {
        const msg = err.response?.data?.errors?.logo?.[0] ?? 'Erro ao salvar logo.';
        toast.add({ severity: 'error', summary: 'Erro', detail: msg, life: 4000 });
    } finally { salvandoLogo.value = false; }
}

function confirmarRemoverLogo() {
    confirm.require({
        message: 'Deseja remover o logo da atlética?',
        header: 'Remover logo',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'Sim, remover',
        rejectLabel: 'Cancelar',
        acceptClass: 'p-button-danger',
        accept: removerLogo,
    });
}

async function removerLogo() {
    try {
        const { data } = await api.delete('/admin/tenant/profile/logo');
        tenant.value = data;
        toast.add({ severity: 'info', summary: 'Logo removido.', life: 2500 });
    } catch {
        toast.add({ severity: 'error', summary: 'Erro ao remover.', life: 3000 });
    }
}

// ── Informações ───────────────────────────────────────────────────────────────

async function salvarInfo() {
    erros.name = form.name.trim() ? '' : 'O nome é obrigatório.';
    if (erros.name) return;

    salvandoInfo.value = true;
    try {
        const { data } = await api.post('/admin/tenant/profile', form);
        tenant.value = data;
        toast.add({ severity: 'success', summary: 'Informações salvas!', life: 2500 });
    } catch (err) {
        const e = err.response?.data?.errors ?? {};
        if (e.name) erros.name = e.name[0];
        else toast.add({ severity: 'error', summary: 'Erro', detail: err.response?.data?.message ?? 'Erro.', life: 3000 });
    } finally { salvandoInfo.value = false; }
}

onMounted(() => carregar());
</script>

<style scoped>
.hidden { display: none; }

/* Banner */
.banner-area {
    min-height: 220px;
    background: linear-gradient(135deg, #1976d2 0%, #1565c0 100%);
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
}
.banner-placeholder {
    text-align: center;
    color: #fff;
}
.banner-img {
    width: 100%;
    height: 220px;
    object-fit: cover;
    display: block;
}
.banner-overlay {
    position: absolute;
    inset: 0;
    background: rgba(0,0,0,.35);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    opacity: 0;
    transition: opacity .2s;
}
.banner-area:hover .banner-overlay { opacity: 1; }
.banner-btn {
    background: rgba(255,255,255,.92);
    color: #1a1a1a;
    border: none;
    border-radius: 6px;
    padding: 10px 20px;
    font-weight: 600;
    font-size: .9rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    transition: background .15s;
}
.banner-btn:hover { background: #fff; }
.banner-btn-danger { color: #dc2626; }

/* Logo */
.logo-area {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    overflow: hidden;
    background: linear-gradient(135deg, #1976d2, #1565c0);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    border: 3px solid var(--surface-border);
}
.logo-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
}
.logo-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
</style>