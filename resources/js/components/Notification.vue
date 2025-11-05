<template>
  <transition
    enter-active-class="transition ease-out duration-300"
    enter-from-class="transform opacity-0 translate-y-4"
    enter-to-class="transform opacity-100 translate-y-0"
    leave-active-class="transition ease-in duration-200"
    leave-from-class="transform opacity-100 translate-y-0"
    leave-to-class="transform opacity-0 translate-y-4"
  >
    <div
      v-if="notificationStore.visible"
      :class="[
        'fixed bottom-5 right-5 text-white px-6 py-3 rounded-lg shadow-lg flex items-center',
        notificationStore.type === 'success' ? 'bg-green-500' : 'bg-red-500'
      ]"
    >
      <svg v-if="notificationStore.type === 'success'" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
      <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
      <span>{{ notificationStore.message }}</span>
    </div>
  </transition>
</template>

<script setup>
import { useNotificationStore } from '../stores/notification';

const notificationStore = useNotificationStore();
</script>
