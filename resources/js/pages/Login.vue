<template>
  <div class="flex items-center justify-center min-h-screen bg-gray-100">
    <div class="w-full max-w-md p-8 space-y-6 bg-white rounded-lg shadow-md">

      <h1 class="text-3xl font-bold text-center text-gray-800">
        Login to MiniBlog+
      </h1>

      <form @submit.prevent="handleLogin" class="space-y-6">

        <div>
          <label for="email" class="block text-sm font-medium text-gray-700">
            Email Address
          </label>
          <input
            type="email"
            id="email"
            v-model="form.email"
            required
            class="w-full px-3 py-2 mt-1 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
            placeholder="you@example.com"
          >
        </div>

        <div>
          <label for="password" class="block text-sm font-medium text-gray-700">
            Password
          </label>
          <input
            type="password"
            id="password"
            v-model="form.password"
            required
            class="w-full px-3 py-2 mt-1 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
            placeholder="••••••••"
          >
        </div>

        <div>
          <button
            type="submit"
            class="w-full py-2 px-4 font-bold text-white bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
          >
            Login
          </button>
        </div>
      </form>

      <p class="text-sm text-center text-gray-600">
        Don't have an account?
        <router-link to="/auth/register" class="font-medium text-blue-600 hover:underline">
          Register here
        </router-link>
      </p>
    </div>
  </div>
</template>

<script setup>
import { reactive } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth.js'; // Import the Pinia store function
import { useNotificationStore } from '../stores/notification.js';
import api from '../services/api.js'; // Import the api service

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
