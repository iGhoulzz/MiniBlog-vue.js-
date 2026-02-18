<template>
  <div class="flex items-center justify-center min-h-screen bg-gray-100 dark:bg-gray-900 transition-colors duration-300">
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
// import { useAuthStore } from '../stores/auth'; // Not strictly needed unless checking auth status
import { useNotificationStore } from '../stores/notification';
import api from '../services/api.js';
import BaseCard from '../components/UI/BaseCard.vue';
import BaseInput from '../components/UI/BaseInput.vue';
import BaseButton from '../components/UI/BaseButton.vue';

// --- 1. STATE ---
const form = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: ''
});

const errors = ref({});
const errorMessage = ref('');
const isLoading = ref(false);

const router = useRouter();
const notificationStore = useNotificationStore();

// --- 2. BEHAVIOR ---
const handleRegister = async () => {
  errors.value = {};
  errorMessage.value = '';
  isLoading.value = true;

  try {
    // get csrf cookie
    await api.get('/sanctum/csrf-cookie');

    // register
    await api.post('/register', form);

    notificationStore.showNotification({
      message: 'Registration successful! Please log in.',
      type: 'success'
    });
    router.push({ name: 'Login' }); // Use named route if possible, or '/auth/login'

  } catch (error) {
    if (error.response && error.response.status === 422) {
      errors.value = error.response.data.errors;
      // Also set a general error message if needed
      errorMessage.value = "Please correct the errors below.";
    } else {
      errorMessage.value = 'An unexpected error occurred. Please try again.';
      console.error(error);
    }
  } finally {
    isLoading.value = false;
  }
};
</script>
