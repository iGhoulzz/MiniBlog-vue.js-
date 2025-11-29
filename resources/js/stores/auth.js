import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { useNotificationStore } from './notification';
import api from '../services/api'; // Make sure 'api' service is imported

export const useAuthStore = defineStore('auth', () => {
    const user = ref(JSON.parse(localStorage.getItem('user')) || null);
    const token = ref(localStorage.getItem('token') || null);
    const notificationStore = useNotificationStore();

    if (token.value) {
      api.defaults.headers.common['Authorization'] = `Bearer ${token.value}`;
    }

    function setUser(newToken, userData) {
        user.value = userData;
        token.value = newToken;
        localStorage.setItem('token', newToken);
        localStorage.setItem('user', JSON.stringify(userData));
        // Also update the api header whenever user is set
        if (newToken) {
            api.defaults.headers.common['Authorization'] = `Bearer ${newToken}`;
        }
    }

    function logout() {
        notificationStore.showNotification({ message: 'You have been logged out.' });
        user.value = null;
        token.value = null;
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        delete api.defaults.headers.common['Authorization'];
    }

    // START: ADD THIS ACTION
    async function fetchUser() {
        if (!token.value) {
            return;
        }
        try {
            const response = await api.get('/user');
            // Re-use the setUser function to update state and local storage
            setUser(token.value, response.data);
        } catch (error) {
            console.error('Failed to fetch user, logging out.', error);
            logout(); // If fetching user fails, the token is likely invalid
        }
    }
    // END: ADD THIS ACTION

    const isAuthenticated = computed(() => !!token.value && !!user.value);

    // Expose the new action
    return { user, token, isAuthenticated, setUser, logout, fetchUser };
});
