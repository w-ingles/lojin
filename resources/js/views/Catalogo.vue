<template>
    <div>
        <!-- Hero -->
        <div class="catalogo-hero">
            <h1>Atléticas Universitárias</h1>
            <p>Encontre sua atlética, compre ingressos e produtos</p>
        </div>

        <!-- Filtros -->
        <div class="card mb-4">
            <div class="grid p-fluid align-items-end">
                <div class="col-12 md:col-6">
                    <div class="field mb-0">
                        <label class="font-medium text-sm">Buscar atlética</label>
                        <span class="p-input-icon-left w-full">
                            <i class="pi pi-search" />
                            <InputText
                                v-model="filtros.search"
                                placeholder="Nome da atlética..."
                                class="w-full"
                                @input="buscar"
                            />
                        </span>
                    </div>
                </div>
                <div class="col-12 md:col-4">
                    <div class="field mb-0">
                        <label class="font-medium text-sm">Universidade</label>
                        <Dropdown
                            v-model="filtros.university_id"
                            :options="universidades"
                            optionLabel="name"
                            optionValue="id"
                            placeholder="Todas as universidades"
                            showClear
                            filter
                            filterPlaceholder="Buscar..."
                            class="w-full"
                            @change="carregarAtleticas(1)"
                        />
                    </div>
                </div>
                <div class="col-12 md:col-2">
                    <Button
                        icon="pi pi-filter-slash"
                        label="Limpar"
                        class="p-button-outlined w-full"
                        @click="limparFiltros"
                    />
                </div>
            </div>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="grid">
            <div v-for="n in 8" :key="n" class="col-12 md:col-6 lg:col-4 xl:col-3">
                <div class="card p-0">
                    <Skeleton height="140px" class="border-round-top" />
                    <div class="p-3">
                        <Skeleton height="1.2rem" class="mb-2" />
                        <Skeleton height=".9rem" width="60%" class="mb-2" />
                        <Skeleton height=".8rem" width="80%" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Vazio -->
        <div v-else-if="atleticas.length === 0" class="card text-center py-6">
            <i class="pi pi-building text-7xl text-color-secondary mb-4 block"></i>
            <h3 class="text-color-secondary mb-2">Nenhuma atlética encontrada</h3>
            <p class="text-color-secondary">
                {{ filtros.search || filtros.university_id
                    ? 'Tente outros filtros de busca.'
                    : 'Ainda não há atléticas cadastradas na plataforma.' }}
            </p>
            <Button
                v-if="filtros.search || filtros.university_id"
                label="Limpar filtros"
                icon="pi pi-filter-slash"
                class="p-button-text mt-2"
                @click="limparFiltros"
            />
        </div>

        <!-- Grid de cards -->
        <div v-else class="grid">
            <div
                v-for="atletica in atleticas"
                :key="atletica.id"
                class="col-12 md:col-6 lg:col-4 xl:col-3"
            >
                <div
                    class="atletica-card card h-full flex flex-column p-0"
                    @click="abrirLoja(atletica.slug)"
                >
                    <!-- Banner (imagem larga) ou fallback com logo -->
                    <div class="atletica-card-header">
                        <img v-if="atletica.banner_url"
                            :src="atletica.banner_url"
                            :alt="atletica.name"
                            class="atletica-banner-img"
                        />
                        <div v-else class="atletica-logo-placeholder">
                            <img v-if="atletica.logo_url"
                                :src="atletica.logo_url"
                                :alt="atletica.name"
                                class="atletica-logo-small"
                            />
                            <i v-else class="pi pi-bolt"></i>
                        </div>
                    </div>

                    <!-- Conteúdo -->
                    <div class="p-3 flex flex-column flex-1">
                        <h4 class="m-0 mb-1 line-height-3">{{ atletica.name }}</h4>

                        <div v-if="atletica.university" class="flex align-items-center gap-1 mb-2">
                            <i class="pi pi-building text-sm text-color-secondary"></i>
                            <small class="text-color-secondary">
                                {{ atletica.university.acronym ?? atletica.university.name }}
                            </small>
                        </div>

                        <p
                            v-if="atletica.description"
                            class="text-color-secondary text-sm flex-1 m-0"
                            style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden"
                        >
                            {{ atletica.description }}
                        </p>
                        <div v-else class="flex-1"></div>

                        <!-- Stats + botão -->
                        <div class="flex align-items-center justify-content-between mt-3">
                            <div v-if="atletica.events_count" class="flex align-items-center gap-1">
                                <i class="pi pi-calendar text-sm" style="color:#1976d2"></i>
                                <small style="color:#1976d2">{{ atletica.events_count }} evento(s)</small>
                            </div>
                            <div v-else class="flex-1"></div>
                            <Button
                                label="Ver Loja"
                                icon="pi pi-external-link"
                                iconPos="right"
                                size="small"
                                class="p-button-outlined"
                                @click.stop="abrirLoja(atletica.slug)"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Paginação -->
        <div v-if="paginacao.last_page > 1" class="flex justify-content-center mt-4">
            <Paginator
                :first="(paginacao.current_page - 1) * paginacao.per_page"
                :rows="paginacao.per_page"
                :totalRecords="paginacao.total"
                @page="(e) => carregarAtleticas(e.page + 1)"
            />
        </div>
    </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import api from '@/service/ApiService';

const router = useRouter();

const atleticas     = ref([]);
const universidades = ref([]);
const loading       = ref(false);
const paginacao     = ref({ current_page: 1, last_page: 1, per_page: 24, total: 0 });

const filtros = reactive({ search: '', university_id: null });
let   searchTimer = null;

function abrirLoja(slug) {
    router.push(`/c/${slug}`);
}

function buscar() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => carregarAtleticas(1), 400);
}

function limparFiltros() {
    filtros.search      = '';
    filtros.university_id = null;
    carregarAtleticas(1);
}

async function carregarAtleticas(page = 1) {
    loading.value = true;
    try {
        const params = { page };
        if (filtros.search.trim())    params.search       = filtros.search.trim();
        if (filtros.university_id)    params.university_id = filtros.university_id;

        const { data } = await api.get('/catalogo', { params });
        atleticas.value = data.data;
        paginacao.value = {
            current_page: data.current_page,
            last_page:    data.last_page,
            per_page:     data.per_page,
            total:        data.total,
        };
    } finally {
        loading.value = false;
    }
}

async function carregarUniversidades() {
    try {
        const { data } = await api.get('/catalogo/universidades');
        universidades.value = data;
    } catch {}
}

onMounted(() => {
    carregarAtleticas();
    carregarUniversidades();
});
</script>

<style scoped>
.catalogo-hero {
    text-align: center;
    padding: 2.5rem 1rem 2rem;
    margin-bottom: 1.5rem;
    background: linear-gradient(135deg, #1976d2 0%, #1565c0 100%);
    border-radius: 12px;
    color: #fff;
}
.catalogo-hero h1 {
    font-size: 2rem;
    font-weight: 800;
    margin: 0 0 .4rem;
    letter-spacing: -.5px;
}
.catalogo-hero p {
    font-size: 1.05rem;
    opacity: .88;
    margin: 0;
}

.atletica-card {
    cursor: pointer;
    transition: transform .18s, box-shadow .18s;
    overflow: hidden;
}
.atletica-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0,0,0,.13);
}

.atletica-card-header {
    height: 130px;
    background: linear-gradient(135deg, #1976d2, #1565c0);
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}
.atletica-banner-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}
.atletica-logo-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
}
.atletica-logo-small {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid rgba(255,255,255,.4);
}
.atletica-logo-placeholder .pi {
    font-size: 2.5rem;
    color: rgba(255,255,255,.8);
}
</style>
