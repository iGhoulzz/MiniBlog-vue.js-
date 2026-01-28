import axios from 'axios';
import { useAuthStore } from '../stores/auth';

const appUrl = (import.meta.env.VITE_APP_URL ?? '').replace(/\/$/, '');
const baseURL = appUrl ? `${appUrl}/api` : '/api';

const api = axios.create({
    baseURL,
    withCredentials: true, // Enable sending cookies for Sanctum SPA auth
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

// Response interceptor to handle 401 Unauthorized errors
api.interceptors.response.use(
  response => response,
  error => {
    if (error.response && error.response.status === 401) {
      const authStore = useAuthStore();
      authStore.logout();
    }
    return Promise.reject(error);
  }
);

export default api;
