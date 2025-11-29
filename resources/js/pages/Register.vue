<template>
  <div class="flex items-center justify-center min-h-screen bg-gray-100">
    <BaseCard class="w-full max-w-md p-8 space-y-6">
      <h1 class="text-3xl font-bold text-center text-gray-800 dark:text-white">
        Create Your Account
      </h1>

      <div v-if="errorMessage" class="p-4 text-sm text-red-700 bg-red-100 rounded-lg dark:bg-red-900 dark:text-red-200">
        {{ errorMessage }}
      </div>

      <form @submit.prevent="handleRegister" class="space-y-6">
        <BaseInput
          id="name"
          label="Name"
          type="text"
          v-model="form.name"
          placeholder="Your Name"
          required
          :error="errors.name ? errors.name[0] : ''"
        />

        <BaseInput
          id="email"
          label="Email Address"
          type="email"
          v-model="form.email"
          placeholder="you@example.com"
          required
          :error="errors.email ? errors.email[0] : ''"
        />

        <BaseInput
          id="password"
          label="Password"
          type="password"
          v-model="form.password"
          placeholder="••••••••"
          required
          :error="errors.password ? errors.password[0] : ''"
        />

        <BaseInput
          id="password_confirmation"
          label="Confirm Password"
          type="password"
          v-model="form.password_confirmation"
          placeholder="••••••••"
          required
        />

        <BaseButton type="submit" class="w-full" :disabled="isLoading">
          <span v-if="isLoading">Creating account...</span>
          <span v-else>Register</span>
        </BaseButton>
      </form>

      <p class="text-sm text-center text-gray-600 dark:text-gray-400">
        Already have an account?
        <router-link to="/auth/login" class="font-medium text-blue-600 dark:text-blue-400 hover:underline">
          Login here
        </router-link>
      </p>
    </BaseCard>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import api from '../services/api.js';
import BaseCard from '../components/UI/BaseCard.vue';
import BaseInput from '../components/UI/BaseInput.vue';
import BaseButton from '../components/UI/BaseButton.vue';

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
      const errorMessages = Object.values(errors).flat().join('. ');
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
