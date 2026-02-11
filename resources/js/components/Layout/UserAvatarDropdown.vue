<template>
  <div class="relative" @mouseenter="handleMouseEnter" @mouseleave="handleMouseLeave">
    <!-- Avatar Button -->
    <button
      @click="navigateToProfile"
      class="flex items-center justify-center w-10 h-10 rounded-full overflow-hidden border-2 border-gray-300 hover:border-blue-500 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
      aria-label="User menu"
    >
      <img
        v-if="authStore.user?.avatar_url"
        :src="authStore.user.avatar_url"
        :alt="authStore.user.name"
        class="w-full h-full object-cover"
      >
      <div
        v-else
        class="w-full h-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-bold text-lg"
      >
        {{ authStore.user?.name?.substring(0, 1).toUpperCase() }}
      </div>
    </button>

    <!-- Dropdown Menu with gap bridge -->
    <Transition name="dropdown">
      <div
        v-if="isOpen"
        class="absolute right-0 mt-1 w-56 z-50"
        @mouseenter="handleMouseEnter"
      >
        <!-- Padding wrapper to bridge the gap -->
        <div class="pt-2">
          <!-- Actual dropdown content -->
          <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
          <!-- User Info Section -->
          <div class="px-4 py-3 bg-gradient-to-r from-blue-50 to-purple-50 dark:from-gray-700 dark:to-gray-800 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center space-x-3">
              <div class="flex-shrink-0">
                <img
                  v-if="authStore.user?.avatar_url"
                  :src="authStore.user.avatar_url"
                  :alt="authStore.user.name"
                  class="w-10 h-10 rounded-full object-cover border-2 border-white"
                >
                <div
                  v-else
                  class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-bold"
                >
                  {{ authStore.user?.name?.substring(0, 1).toUpperCase() }}
                </div>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate">
                  {{ authStore.user?.name }}
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                  {{ authStore.user?.email }}
                </p>
              </div>
            </div>
          </div>

          <!-- Menu Items -->
          <div class="py-2">
            <!-- View Profile -->
            <button
              @click="navigateToProfile"
              class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-blue-50 dark:hover:bg-gray-700 hover:text-blue-600 dark:hover:text-blue-400 transition-colors duration-150 flex items-center space-x-2"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
              <span>View Profile</span>
            </button>

            <!-- Divider -->
            <div class="border-t border-gray-200 my-1"></div>

            <!-- Dark Mode Toggle -->
            <div class="px-4 py-2 flex items-center justify-between">
                <span class="text-sm text-gray-700 dark:text-gray-300">Dark Mode</span>
                <button 
                    @click="themeStore.toggleTheme()" 
                    class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800"
                    :class="themeStore.isDark ? 'bg-blue-600' : 'bg-gray-200'"
                >
                    <span class="sr-only">Enable dark mode</span>
                    <span 
                        class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                        :class="themeStore.isDark ? 'translate-x-6' : 'translate-x-1'"
                    />
                </button>
            </div>

            <!-- Divider -->
            <div class="border-t border-gray-200 dark:border-gray-700 my-1"></div>

            <!-- Logout -->
            <button
              @click="handleLogout"
              class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors duration-150 flex items-center space-x-2"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
              </svg>
              <span>Logout</span>
            </button>
          </div>
        </div>
      </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth.js';
import { useThemeStore } from '../../stores/theme.js'; // Added import for theme store
import api from '../../services/api.js';

const router = useRouter();
const authStore = useAuthStore();
const themeStore = useThemeStore(); // Initialized theme store
const isOpen = ref(false);
let closeTimeout = null;

const handleMouseEnter = () => {
  if (closeTimeout) {
    clearTimeout(closeTimeout);
    closeTimeout = null;
  }
  isOpen.value = true;
};

const handleMouseLeave = () => {
  closeTimeout = setTimeout(() => {
    isOpen.value = false;
  }, 300); // Increased delay to 300ms
};

const navigateToProfile = () => {
  isOpen.value = false;
  if (authStore.user?.id) {
    router.push(`/users/${authStore.user.id}`);
  }
};

const handleLogout = async () => {
  isOpen.value = false;
  try {
    await api.post('/logout');
  } catch (error) {
    console.error('Logout failed on server, but logging out locally.', error);
  }
  authStore.logout();
  router.push('/auth/login');
};
</script>

<style scoped>
/* Dropdown transition animations */
.dropdown-enter-active,
.dropdown-leave-active {
  transition: all 0.2s ease;
}

.dropdown-enter-from {
  opacity: 0;
  transform: translateY(-10px);
}

.dropdown-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}
</style>
