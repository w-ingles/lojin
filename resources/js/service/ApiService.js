import axios from 'axios';

const api = axios.create({
    baseURL: '/api',
    headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
    withCredentials: true,
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
            window.location.href = '/login';
        }
        return Promise.reject(err);
    }
);

export default api;
