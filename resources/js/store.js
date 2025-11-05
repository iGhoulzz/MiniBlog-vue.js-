import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { useNotificationStore } from './stores/notification';

export const useAuthStore = defineStore('auth', () => {
    // --- 1. STATE ---
    // Get user data from localStorage to persist login state
    const user = ref(JSON.parse(localStorage.getItem('user')) || null);
    const token = ref(localStorage.getItem('token') || null);
    const notificationStore = useNotificationStore();

    /**
     * Sets the user's token and data, and stores it in localStorage.
     * @param {string} newToken - The authentication token.
     * @param {object} userData - The user's data.
     */
    function setUser(newToken, userData) {
        user.value = userData;
        token.value = newToken;
        localStorage.setItem('token', newToken);
        localStorage.setItem('user', JSON.stringify(userData));
    }

    /**
     * Clears the user's session data and redirects to the login page.
     */
    function logout(router) {
        const message = 'You have been logged out.';

        user.value = null;
        token.value = null;
        localStorage.removeItem('token');
        localStorage.removeItem('user');

        if (router) {
            router.push('/auth/login').then(() => {
                notificationStore.showNotification({ message });
            });
        } else {
            notificationStore.showNotification({ message });
        }
    }

    const isAuthenticated = computed(() => !!token.value);

    return { user, token, isAuthenticated, setUser, logout };
});
