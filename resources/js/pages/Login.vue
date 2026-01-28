<template>
  <div class="flex items-center justify-center min-h-screen bg-gray-100 dark:bg-gray-900 transition-colors duration-300">
    <BaseCard class="w-full max-w-md p-8 space-y-6">
      <h1 class="text-3xl font-bold text-center text-gray-800 dark:text-white">
        Login to MiniBlog+
      </h1>

      <div v-if="errorMessage" class="p-4 text-sm text-red-700 bg-red-100 rounded-lg dark:bg-red-900 dark:text-red-200">
        {{ errorMessage }}
      </div>

      <form @submit.prevent="handleLogin" class="space-y-6">
        <BaseInput
          id="email"
          label="Email Address"
          type="email"
          v-model="form.email"
          placeholder="you@example.com"
          required
        />

        <BaseInput
          id="password"
          label="Password"
          type="password"
          v-model="form.password"
          placeholder="••••••••"
          required
        />

        <BaseButton type="submit" class="w-full" :disabled="isLoading">
          <span v-if="isLoading">Logging in...</span>
          <span v-else>Login</span>
        </BaseButton>
      </form>

      <p class="text-sm text-center text-gray-600 dark:text-gray-400">
        Don't have an account?
        <router-link to="/auth/register" class="font-medium text-blue-600 dark:text-blue-400 hover:underline">
          Register here
        </router-link>
      </p>
    </BaseCard>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth.js'; // Import the Pinia store function
import { useNotificationStore } from '../stores/notification.js';
import api from '../services/api.js'; // Import the api service
import BaseCard from '../components/UI/BaseCard.vue';
import BaseInput from '../components/UI/BaseInput.vue';
import BaseButton from '../components/UI/BaseButton.vue';

// --- 1. STATE ---
// This creates a "reactive" object for our form.
// v-model links the inputs directly to these properties.
const form = reactive({
  email: '',
  password: ''
});

// This gives us access to the Vue router instance.
const router = useRouter();
const authStore = useAuthStore(); // Get the store instance
const notificationStore = useNotificationStore();


// --- 2. BEHAVIOR ---
// This is the function that runs when the form is submitted.
const handleLogin = async () => {
  try {
        const response = await api.post('/login', form);

    // Use the store action to set the user
    authStore.setUser(response.data.token, response.data.user);

    // Show success notification
    notificationStore.showNotification({
      message: 'Login successful!',
      type: 'success'
    });

    // Redirect to the home page
    router.push('/');

  } catch (error) {
    // --- 6. FAILURE ---
    // If the backend returns an error (like 401 Invalid Credentials)
    // we catch it here and show the error message.
    if (error.response && error.response.status === 401) {
      notificationStore.showNotification({
        message: error.response.data.message,
        type: 'error'
      });
    } else {
      notificationStore.showNotification({
        message: 'An unexpected error occurred. Please try again.',
        type: 'error'
      });
      console.error(error); // Log the full error for debugging
    }
  }
};
</script>
