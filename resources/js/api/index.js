import axios from 'axios';

// Determine base URL relative to wherever Laravel's public/ is served from
const scriptSrc = document.querySelector('script[src*="/build/assets/"]')?.src || '';
const basePath = scriptSrc.replace(/\/build\/assets\/.*$/, '') || window.location.origin;

const api = axios.create({
    baseURL: basePath + '/api',
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
    },
});

export default api;
