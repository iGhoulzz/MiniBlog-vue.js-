<template>
  <div id="app-layout">
    <nav class="bg-white p-4 rounded-lg shadow-md flex justify-between items-center mb-8 max-w-3xl mx-auto mt-10">
      <router-link to="/" class="text-2xl font-bold text-blue-600">MiniBlog+ (Vue)</router-link>

      <div class="flex items-center">

        <div v-if="authStore.user" class="flex items-center">
          <UserAvatarDropdown />
        </div>

        <div v-else>
          <router-link to="/auth/login" class="text-blue-600 font-semibold mr-4 hover:underline">Login</router-link>
          <router-link to="/auth/register" class="bg-blue-600 text-white font-bold py-2 px-4 rounded-lg hover:bg-blue-700 transition">
            Register
          </router-link>
        </div>
      </div>
    </nav>

    <main class="container mx-auto mt-4 max-w-3xl">
      <router-view v-slot="{ Component }">
        <Transition name="fade" mode="out-in">
          <component :is="Component" />
        </Transition>
      </router-view>
    </main>

    <Notification />
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth.js';
import UserAvatarDropdown from '../components/Layout/UserAvatarDropdown.vue';
import Notification from '../components/Notification.vue';

const router = useRouter();
const authStore = useAuthStore();
</script>

<style>
/* Global body styles */
body {
  background-color: #f0f2f5;
  font-family: sans-serif;
}
</style>
