<template>
  <teleport to="body">
    <div
      v-if="shouldRender"
      class="fixed bottom-4 right-4 flex flex-row-reverse gap-4 flex-wrap max-w-full z-40 pointer-events-none"
    >
      <ChatWindow
        v-for="(windowId, index) in chatStore.openWindowIds"
        :key="windowId"
        class="pointer-events-auto"
        :window-id="windowId"
        :stack-index="index"
        :is-active="chatStore.activeWindowId === windowId"
        @close="chatStore.closeWindow(windowId)"
        @focus-window="chatStore.focusWindow(windowId)"
      />
    </div>
  </teleport>
</template>

<script setup>
import { computed } from 'vue';
import { useAuthStore } from '../../stores/auth';
import { useChatStore } from '../../stores/chat';
import ChatWindow from './ChatWindow.vue';

const authStore = useAuthStore();
const chatStore = useChatStore();

const shouldRender = computed(() => authStore.user && chatStore.openWindowIds.length > 0);
</script>
