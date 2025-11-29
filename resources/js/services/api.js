import axios from 'axios';
import { useAuthStore } from '../stores/auth';

const appUrl = (import.meta.env.VITE_APP_URL ?? '').replace(/\/$/, '');
const baseURL = appUrl ? `${appUrl}/api` : '/api';

const api = axios.create({
    baseURL,
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    },
});

// Request interceptor to add the auth token to headers
api.interceptors.request.use(config => {
    const authStore = useAuthStore();
    const token = authStore.token;
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});

export default api;
