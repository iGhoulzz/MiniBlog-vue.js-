<template>
  <div class="flex items-center justify-center min-h-screen bg-gray-100">
    <div class="w-full max-w-md p-8 space-y-6 bg-white rounded-lg shadow-md">

      <h1 class="text-3xl font-bold text-center text-gray-800">
        Create Your Account
      </h1>

      <form @submit.prevent="handleRegister" class="space-y-6">

        <div>
          <label for="name" class="block text-sm font-medium text-gray-700">
            Name
          </label>
          <input
            type="text"
            id="name"
            v-model="form.name"
            required
            class="w-full px-3 py-2 mt-1 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
            placeholder="Your Name"
          >
        </div>

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
          <label for="password_confirmation" class="block text-sm font-medium text-gray-700">
            Confirm Password
          </label>
          <input
            type="password"
            id="password_confirmation"
            v-model="form.password_confirmation"
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
            Create Account
          </button>
        </div>
      </form>

      <p class="text-sm text-center text-gray-600">
        Already have an account?
        <router-link to="/auth/login" class="font-medium text-blue-600 hover:underline">
          Login here
        </router-link>
      </p>
    </div>
  </div>
</template>

<script setup>
import { reactive } from 'vue';
import { useRouter } from 'vue-router';
import { useNotificationStore } from '../stores/notification.js';
import api from '../services/api.js';

// --- 1. STATE ---
// Create a reactive object for our form.
// v-model links the inputs directly to these properties.
const form = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: '' // Must match the backend validation rule
});

// This gives us access to the Vue router instance.
const router = useRouter();
const notificationStore = useNotificationStore();


// --- 2. BEHAVIOR ---
// This is the function that runs when the form is submitted.
const handleRegister = async () => {
  try {
    // --- 3. BACKEND CONNECTION (Step A) ---
    // Just like login, we must get the Sanctum CSRF cookie first.
    await api.get('/sanctum/csrf-cookie');

    // --- 4. BACKEND CONNECTION (Step B) ---
    // Send a POST request to the API route we built.
    const response = await api.post('/register', form);

    // --- 5. SUCCESS ---
    // If registration is successful, the backend sends a 201 response.
    // Show success notification and redirect the user to the login page.
    notificationStore.showNotification({
      message: 'Registration successful! Please log in.',
      type: 'success'
    });
    router.push('/auth/login');

  } catch (error) {
    // --- 6. FAILURE ---
    // If the backend returns a 422 (Validation Error)
    if (error.response && error.response.status === 422) {
      // Laravel sends a JSON object of all validation errors.
      const errors = error.response.data.errors;
      const errorMessages = Object.values(errors).flat().join(' ');
      notificationStore.showNotification({
        message: errorMessages,
        type: 'error'
      });
    } else {
      // Handle other unexpected errors
      notificationStore.showNotification({
        message: 'An unexpected error occurred. Please try again.',
        type: 'error'
      });
      console.error(error);
    }
  }
};
</script>
