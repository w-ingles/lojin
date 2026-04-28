# Integração Laravel + Vue 3 SPA — Babylon Vue 4.1.0

## Visão Geral

Este documento descreve tudo que foi configurado para integrar o template **Babylon Vue 4.1.0** (PrimeVue) ao projeto **IdealFood**, utilizando Laravel como backend e Vue 3 como SPA no frontend.

---

## Stack Utilizada

| Camada | Tecnologia |
|---|---|
| Backend | Laravel 11 (PHP) |
| Frontend | Vue 3 + Composition API (código novo) / Options API (layout do template) |
| UI | PrimeVue 3.49.1 + PrimeFlex 3 + PrimeIcons 6 |
| Roteamento | Vue Router 4 (`createWebHistory`) |
| Build | Vite 5 + laravel-vite-plugin + @vitejs/plugin-vue |
| Estilos | SCSS (assets do template), CSS de tema em `/public/theme/` |
| Extras | Chart.js, FullCalendar, Quill (editor), PrismJS |

---

## O que Foi Feito

### 1. Cópia dos Assets Públicos do Template

Os arquivos estáticos do Babylon (`babylon-vue-4.1.0/public/`) foram copiados para `public/` do Laravel:

```
public/
  theme/        ← CSS dos temas (blue, green, amber, indigo, etc.)
  layout/       ← CSS de layout e imagens (logo, avatares, etc.)
  demo/         ← assets de demonstração
  pages/        ← páginas HTML standalone (landing, etc.)
  favicon.ico
```

Esses arquivos são servidos diretamente pelo servidor web, sem passar pelo Vite.

---

### 2. Cópia do Código-Fonte Vue para `resources/js/`

Os arquivos de `babylon-vue-4.1.0/src/` foram copiados para `resources/js/`:

```
resources/js/
  app.js              ← entry point (adaptado do main.js do Babylon)
  App.vue             ← componente raiz da SPA
  router/
    index.js          ← rotas da aplicação (Vue Router)
  layout/
    AppLayout.vue     ← layout principal (sidebar + topbar + conteúdo)
    AppTopbar.vue     ← barra superior
    AppMenu.vue       ← menu lateral
    AppSubmenu.vue    ← submenus
    AppBreadcrumb.vue ← trilha de navegação
    AppFooter.vue     ← rodapé
    AppConfig.vue     ← painel de configuração de tema
    AppInlineProfile.vue
    event-bus.js      ← barramento de eventos Vue
  views/
    Dashboard.vue
    pages/            ← Login, Error, NotFound, Access, Crud, Calendar...
    uikit/            ← demos de componentes PrimeVue
    utilities/        ← Icons, Documentation, Blocks
  components/
    AppCodeHighlight.js
    BlockViewer.vue
  assets/
    styles.scss       ← importa PrimeVue CSS, PrimeFlex, PrimeIcons, SCSS do tema
    sass/             ← SCSS completo do layout e tema
    demo/             ← flags e estilos de demo
  service/
    ProductService.js
    CustomerService.js
    EventService.js
    NodeService.js
    PhotoService.js
    CountryService.js
```

---

### 3. `package.json` — Dependências Atualizadas

Tailwind CSS foi removido (não é usado pelo template). Adicionadas todas as dependências do Babylon:

```json
{
    "private": true,
    "type": "module",
    "scripts": {
        "dev": "vite",
        "build": "vite build"
    },
    "dependencies": {
        "@fullcalendar/core": "^5.7.2",
        "@fullcalendar/daygrid": "^5.7.2",
        "@fullcalendar/interaction": "^5.7.2",
        "@fullcalendar/timegrid": "^5.7.2",
        "@fullcalendar/vue3": "^5.7.2",
        "chart.js": "3.3.2",
        "primeflex": "^3.3.0",
        "primeicons": "^6.0.1",
        "primevue": "3.49.1",
        "prismjs": "^1.9.0",
        "quill": "^1.3.7",
        "vue": "^3.2.45",
        "vue-router": "^4.1.6"
    },
    "devDependencies": {
        "@vitejs/plugin-vue": "^4.6.2",
        "concurrently": "^9.0.1",
        "laravel-vite-plugin": "^3.0.0",
        "sass": "^1.54.4",
        "vite": "^5.4.0"
    }
}
```

---

### 4. `vite.config.js` — Configuração do Build

```js
import { fileURLToPath, URL } from 'node:url';
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/app.js'],
            refresh: true,
        }),
        vue(),
    ],
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
        },
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
```

**Por que o alias `@`?** O template usa `@/` como atalho para `src/`. Aqui redirecionamos para `resources/js/`, mantendo todos os imports do template funcionando sem alterações.

---

### 5. `resources/views/app.blade.php` — Shell da SPA

Equivalente ao `index.html` do Babylon, mas renderizado pelo Laravel:

```html
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'IdealFood') }}</title>

    <link rel="icon" href="/favicon.ico">

    <link id="theme-css" rel="stylesheet" type="text/css" href="/theme/theme-blue.css">
    <link id="layout-css" rel="stylesheet" type="text/css" href="/layout/css/layout-blue.css">

    @vite(['resources/js/app.js'])
</head>
<body>
    <div id="app">
        <div class="loader-screen">
            <div class="loader">
                <div class="bar1"></div>
                <div class="bar2"></div>
                <div class="bar3"></div>
                <div class="bar4"></div>
                <div class="bar5"></div>
                <div class="bar6"></div>
            </div>
        </div>
    </div>
</body>
</html>
```

**Detalhes importantes:**
- `id="theme-css"` e `id="layout-css"` — o `AppConfig.vue` usa esses IDs para trocar o tema dinamicamente via JavaScript
- `{{ csrf_token() }}` — disponível para requisições à API Laravel
- `@vite([...])` — injeta o JS/CSS compilado com hot reload em desenvolvimento
- O loader HTML é exibido enquanto o Vue não monta

---

### 6. `routes/web.php` — Catch-all para a SPA

```php
<?php

use Illuminate\Support\Facades\Route;

Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');
```

**Por que catch-all?** O Vue Router usa `createWebHistory()`, que gera URLs limpas como `/dashboard`, `/login`. Ao dar refresh ou acessar uma URL diretamente, o Laravel precisa retornar sempre o `app.blade.php` para o Vue Router assumir o controle.

---

### 7. `resources/js/app.js` — Entry Point Vue

Registra todos os componentes PrimeVue globalmente, plugins e diretivas. Baseado no `main.js` do Babylon, adaptado para o contexto Laravel:

```js
import { createApp } from 'vue';
import router from './router';
import App from './App.vue';
import PrimeVue from 'primevue/config';
// ... todos os imports de componentes PrimeVue ...
import '@/assets/styles.scss';

const app = createApp(App);

app.use(PrimeVue, { ripple: true });
app.use(ConfirmationService);
app.use(ToastService);
app.use(DialogService);
app.use(router);

// diretivas e componentes globais...

app.mount('#app');
```

---

## Como o Roteamento Funciona

```
Usuário acessa http://localhost:8000/dashboard
  ↓
Laravel: nenhuma rota específica encontrada
  ↓
Catch-all em routes/web.php retorna app.blade.php
  ↓
Vue é montado no #app
  ↓
Vue Router lê /dashboard e renderiza Dashboard.vue
  ↓
AppLayout.vue envolve o conteúdo (menu + topbar + breadcrumb)
```

Isso garante que:
- **Refresh de página** funciona em qualquer rota
- **Links diretos** (ex: e-mail com link para `/invoice/123`) funcionam
- **Back/Forward** do browser funciona corretamente

---

## Como Subir o Projeto

### Pré-requisitos
- PHP 8.2+
- Composer
- Node.js LTS (18+)

### Comandos

**1. Instalar dependências PHP (se ainda não feito):**
```bash
composer install
```

**2. Configurar o ambiente:**
```bash
cp .env.example .env
php artisan key:generate
```

**3. Instalar dependências JS:**
```bash
npm install
```

**4. Em dois terminais separados:**

Terminal 1:
```bash
php artisan serve
```

Terminal 2:
```bash
npm run dev
```

**5. Acesse:** `http://localhost:8000`

---

## Estrutura de Pastas Completa

```
IdealFood/
├── app/                        ← PHP: Models, Controllers, etc.
├── babylon-vue-4.1.0/          ← Template original (referência)
├── config/                     ← Configurações Laravel
├── database/                   ← Migrations, Seeders
├── public/
│   ├── theme/                  ← CSS dos temas PrimeVue
│   ├── layout/                 ← CSS + imagens do layout
│   ├── demo/                   ← assets de demonstração
│   ├── build/                  ← gerado pelo `npm run build`
│   └── index.php               ← entrada do Laravel
├── resources/
│   ├── js/                     ← SPA Vue 3
│   │   ├── app.js              ← entry point
│   │   ├── App.vue             ← componente raiz
│   │   ├── router/             ← Vue Router
│   │   ├── layout/             ← componentes do layout
│   │   ├── views/              ← páginas da aplicação
│   │   ├── components/         ← componentes reutilizáveis
│   │   ├── assets/             ← SCSS + imagens de demo
│   │   └── service/            ← serviços de dados
│   ├── css/
│   │   └── app.css             ← vazio (estilos estão no SCSS)
│   └── views/
│       └── app.blade.php       ← shell HTML da SPA
├── routes/
│   ├── web.php                 ← catch-all → SPA
│   └── api.php                 ← rotas da API Laravel
├── package.json
├── vite.config.js
└── composer.json
```

---

## Preparação para API (Próximas Etapas)

### Criar um serviço HTTP base

```js
// resources/js/service/ApiService.js
import axios from 'axios';

const api = axios.create({
    baseURL: '/api',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
    },
});

export default api;
```

### Exemplo de uso em um componente Vue

```vue
<script setup>
import { ref, onMounted } from 'vue';
import ApiService from '@/service/ApiService';

const produtos = ref([]);

onMounted(async () => {
    const { data } = await ApiService.get('/produtos');
    produtos.value = data;
});
</script>
```

### Rotas de API no Laravel

```php
// routes/api.php
use App\Http\Controllers\ProdutoController;

Route::apiResource('produtos', ProdutoController::class);
```

---

## Problemas Comuns e Soluções

| Problema | Causa | Solução |
|---|---|---|
| Tela branca ao abrir | `npm install` não foi executado | `npm install && npm run dev` |
| Tema não carrega | Arquivos em `public/theme/` ausentes | Já copiados na integração ✓ |
| Logo do menu não aparece | `public/layout/images/` ausente | Já copiado na integração ✓ |
| 404 ao dar refresh em rota Vue | Falta da rota catch-all | Já configurado em `routes/web.php` ✓ |
| Erro no import `@/` em SCSS | Alias não configurado no Vite | Já no `vite.config.js` ✓ |
| `npm` não encontrado | Node.js não instalado | Instale Node.js LTS em nodejs.org |
| Erro `Class not found` PHP | Vendor não instalado | `composer install` |
| Hot reload não funciona | Vite não está rodando | `npm run dev` no segundo terminal |
| Troca de tema não persiste | Comportamento esperado do template | Implementar localStorage futuramente |

---

## Boas Práticas Adotadas

- **Separação clara** entre frontend (`resources/js/`) e backend (`app/`, `routes/api.php`)
- **Alias `@`** para imports absolutos — sem `../../` relativo
- **Catch-all** no Laravel garante que o Vue Router controla a navegação
- **CSRF token** disponível no meta tag para requisições seguras à API
- **Assets estáticos** do tema servidos diretamente de `/public` (sem processamento Vite)
- **Vite** processa apenas o JS/SCSS da aplicação — rápido e eficiente

---

*Documento gerado em 25/04/2026*
