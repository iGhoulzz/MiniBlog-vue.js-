import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useNotificationStore = defineStore('notification', () => {
    const message = ref('');
    const type = ref('success'); // 'success' or 'error'
    const visible = ref(false);
    let timeoutId = null;

    function showNotification({ message: newMessage, type: newType = 'success', duration = 4000 }) {
        message.value = newMessage;
        type.value = newType;
        visible.value = true;

        if (timeoutId) {
            clearTimeout(timeoutId);
        }

        timeoutId = setTimeout(() => {
            hideNotification();
        }, duration);
    }

    function hideNotification() {
        visible.value = false;
    }

    return { message, type, visible, showNotification, hideNotification };
});
