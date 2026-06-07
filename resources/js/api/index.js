import axios from 'axios';

// Determine base URL from the script's own src attribute
const scriptSrc = document.querySelector('script[src*="/build/assets/"]')?.src || '';
const basePath = scriptSrc.replace(/\/build\/assets\/.*$/, '') || window.location.origin;

const api = axios.create({
    baseURL: basePath + '/api',
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
    },
});

// On 404 for core resources (season/weekend no longer exist) → go home
// Skip endpoints that are allowed to return 404 or are checked manually
const SKIP_REDIRECT = [
    '/qualifying/results',
    '/seasons/current',
    '/race/state',
    '/race/radio',
    '/race/pit',
    '/race/lap',
    '/race/init',
    '/next-round',
    '/hint',
    '/standings',
];

api.interceptors.response.use(
    res => res,
    err => {
        if (err.response?.status === 404) {
            const url = err.config?.url || '';
            const skip = SKIP_REDIRECT.some(p => url.includes(p));
            if (!skip) {
                // Resource no longer exists (stale ID after DB reset) – go home
                window.location.hash = '#/';
            }
        }
        return Promise.reject(err);
    }
);

export default api;
