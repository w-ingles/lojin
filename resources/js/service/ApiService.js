import axios from 'axios';

// Em build mobile (VITE_API_URL definido), usa URL absoluta do servidor.
// Em build web, usa path relativo (o Laravel serve /api).
const baseURL = import.meta.env.VITE_API_URL
    ? `${import.meta.env.VITE_API_URL}/api`
    : '/api';

const api = axios.create({
    baseURL,
    headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
    // withCredentials só é necessário para autenticação por cookie (Sanctum stateful).
    // Este projeto usa tokens Bearer, mas mantemos true para compatibilidade web.
    withCredentials: !import.meta.env.VITE_API_URL,
});

api.interceptors.request.use((config) => {
    const token = localStorage.getItem('auth_token');
    if (token) config.headers.Authorization = `Bearer ${token}`;

    // Loja pública: slug vem da URL /c/:slug/...
    const match = window.location.pathname.match(/^\/c\/([^/]+)/);
    if (match) config.headers['X-Tenant-Slug'] = match[1];

    return config;
});

api.interceptors.response.use(
    (res) => res,
    (err) => {
        if (err.response?.status === 401) {
            localStorage.removeItem('auth_token');
            localStorage.removeItem('auth_user');
            // Funciona tanto em web quanto em Capacitor (androidScheme: https
            // faz capacitor://localhost se comportar como servidor real)
            window.location.href = '/login';
        }
        return Promise.reject(err);
    }
);

export default api;
