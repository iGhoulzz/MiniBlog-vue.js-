import axios from 'axios';
import { useAuthStore } from '../store';

const api = axios.create({
    baseURL: 'http://miniblog.test/api',
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
