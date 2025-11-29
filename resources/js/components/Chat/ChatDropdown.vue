<template>
  <div ref="rootRef" class="relative" v-if="authStore.user">
    <button
      type="button"
      class="relative inline-flex items-center justify-center w-10 h-10 rounded-full bg-blue-50 text-blue-600 hover:bg-blue-100 transition-colors"
      aria-label="Open chat"
      @click="toggleDropdown"
    >
      <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
          d="M7 8h10M7 12h4m1 8a9 9 0 1 0-5.64-1.97L5 21l1.97-.36A8.96 8.96 0 0 0 12 20z" />
      </svg>
      <span
        v-if="totalUnread > 0"
        class="absolute -top-1 -right-1 bg-red-500 text-white text-[11px] font-semibold rounded-full min-w-[1.25rem] h-5 flex items-center justify-center px-1"
      >
        {{ totalUnread > 9 ? '9+' : totalUnread }}
      </span>
    </button>

    <transition name="dropdown">
      <div
        v-if="isOpen"
        class="absolute right-0 mt-3 w-80 bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-100 dark:border-gray-700 overflow-hidden z-50"
      >
        <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between bg-gray-50 dark:bg-gray-900">
          <div>
            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">Messages</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">{{ totalUnread }} unread</p>
          </div>
          <button
            class="text-xs font-semibold text-blue-600 dark:text-blue-400 hover:text-blue-500 transition"
            @click="refreshConversations"
          >
            Refresh
          </button>
        </div>

        <div v-if="chatStore.isLoading" class="p-4 text-sm text-gray-500 dark:text-gray-400">Loading conversations…</div>
        <div v-else-if="conversations.length === 0" class="p-4 text-sm text-gray-500 dark:text-gray-400">
          No conversations yet. Start one from any profile.
        </div>
        <ul v-else class="divide-y divide-gray-100 dark:divide-gray-700 max-h-96 overflow-y-auto">
          <li v-for="conversation in conversations" :key="conversation.id">
            <button
              type="button"
              class="w-full px-4 py-3 flex gap-3 hover:bg-gray-50 dark:hover:bg-gray-700 transition text-left"
              @click="handleSelectConversation(conversation.id)"
            >
              <div class="relative shrink-0">
                <img
                  v-if="participantAvatar(conversation)"
                  :src="participantAvatar(conversation)"
                  :alt="conversationTitle(conversation)"
                  class="w-11 h-11 rounded-full object-cover border border-gray-100 dark:border-gray-600"
                >
                <div
                  v-else
                  class="w-11 h-11 rounded-full bg-gradient-to-br from-purple-500 to-blue-500 text-white font-semibold flex items-center justify-center"
                >
                  {{ conversationTitle(conversation).charAt(0).toUpperCase() }}
                </div>
                <span
                  v-if="chatStore.unreadCount(conversation.id) > 0"
                  class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] px-1.5 rounded-full font-semibold"
                >
                  {{ chatStore.unreadCount(conversation.id) }}
                </span>
              </div>
              <div class="flex-1 min-w-0">
                <div class="flex items-start justify-between">
                  <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 truncate pr-2">{{ conversationTitle(conversation) }}</p>
                  <span class="text-[11px] text-gray-400 whitespace-nowrap">{{
                    formatTimestamp(conversation.updated_at)
                  }}</span>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 truncate mt-1">
                  <span v-if="lastMessage(conversation)?.user_id === authStore.user?.id" class="font-semibold">You:
                  </span>
                  {{ lastMessage(conversation)?.content ?? 'Start the conversation' }}
                </p>
              </div>
            </button>
          </li>
        </ul>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { useAuthStore } from '../../stores/auth';
import { useChatStore } from '../../stores/chat';

const authStore = useAuthStore();
const chatStore = useChatStore();
const isOpen = ref(false);
const rootRef = ref(null);

const conversations = computed(() => chatStore.sortedConversations);
const totalUnread = computed(() => chatStore.totalUnread);

const toggleDropdown = async () => {
  if (!chatStore.conversations.length) {
    await chatStore.fetchConversations();
  }
  isOpen.value = !isOpen.value;
};

const refreshConversations = () => {
  chatStore.fetchConversations();
};

const handleSelectConversation = async (conversationId) => {
  await chatStore.openConversation(conversationId);
  isOpen.value = false;
};

const handleClickOutside = (event) => {
  if (!rootRef.value) {
    return;
  }
  if (!rootRef.value.contains(event.target)) {
    isOpen.value = false;
  }
};

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
});

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside);
});

const conversationTitle = (conversation) => chatStore.conversationTitle(conversation);

const lastMessage = (conversation) => conversation.messages?.[conversation.messages.length - 1] ?? null;

const participantAvatar = (conversation) => {
  const participant = conversation.users?.find((user) => user.id !== authStore.user?.id);
  return participant?.avatar_url ?? null;
};

const formatTimestamp = (value) => {
  if (!value) {
    return '';
  }
  return new Intl.DateTimeFormat('en', {
    hour: 'numeric',
    minute: 'numeric',
  }).format(new Date(value));
};
</script>

<style scoped>
.dropdown-enter-active,
.dropdown-leave-active {
  transition: all 0.18s ease;
}

.dropdown-enter-from,
.dropdown-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}
</style>
