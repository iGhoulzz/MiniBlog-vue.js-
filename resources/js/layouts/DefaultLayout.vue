<template>
  <div id="app-layout">
    <nav class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-md flex justify-between items-center mb-8 max-w-3xl mx-auto mt-10 transition-colors duration-300">
      <router-link to="/" class="text-2xl font-bold text-blue-600 dark:text-blue-400">MiniBlog+ (Vue)</router-link>

      <div class="flex items-center">

        <div v-if="authStore.user" class="flex items-center space-x-4">
          <ChatDropdown />
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

  </div>
</template>

<script setup>
import { useAuthStore } from '../stores/auth.js';
import UserAvatarDropdown from '../components/Layout/UserAvatarDropdown.vue';
import ChatDropdown from '../components/Chat/ChatDropdown.vue';

const authStore = useAuthStore();
</script>

<style>
/* Global styles are now in app.css */
</style>
