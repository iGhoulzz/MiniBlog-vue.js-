<template>
  <div class="space-y-6">
    <!-- Page Header -->
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Messages</h1>
      <span v-if="chatStore.totalUnread > 0" class="text-sm font-medium text-blue-600 dark:text-blue-400">
        {{ chatStore.totalUnread }} unread
      </span>
    </div>

    <!-- Message Search Bar -->
    <SearchBar mode="messages" />

    <!-- Conversations List -->
    <BaseCard class="overflow-hidden">
      <!-- Loading State -->
      <div v-if="chatStore.isLoading" class="p-8 text-center">
        <div class="w-8 h-8 border-2 border-blue-500 border-t-transparent rounded-full animate-spin mx-auto mb-3"></div>
        <p class="text-sm text-gray-500 dark:text-gray-400">Loading conversations…</p>
      </div>

      <!-- Empty State -->
      <div v-else-if="conversations.length === 0" class="p-10 text-center">
        <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
              d="M7 8h10M7 12h4m1 8a9 9 0 1 0-5.64-1.97L5 21l1.97-.36A8.96 8.96 0 0 0 12 20z" />
          </svg>
        </div>
        <p class="text-gray-500 dark:text-gray-400 font-medium">No conversations yet</p>
        <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">Start one from any user's profile page.</p>
      </div>

      <!-- Conversations -->
      <ul v-else class="divide-y divide-gray-100 dark:divide-gray-700">
        <li v-for="conversation in conversations" :key="conversation.id">
          <button
            type="button"
            class="w-full px-5 py-4 flex gap-4 items-center hover:bg-gray-50 dark:hover:bg-gray-700/50 transition text-left group"
            @click="handleSelectConversation(conversation.id)"
          >
            <!-- Avatar -->
            <div class="relative shrink-0">
              <img
                v-if="participantAvatar(conversation)"
                :src="participantAvatar(conversation)"
                :alt="chatStore.conversationTitle(conversation)"
                class="w-12 h-12 rounded-full object-cover border-2 border-white dark:border-gray-700 shadow-sm"
              />
              <div
                v-else
                class="w-12 h-12 rounded-full bg-gradient-to-br from-purple-500 to-blue-500 text-white font-semibold flex items-center justify-center text-base shadow-sm"
              >
                {{ chatStore.conversationTitle(conversation).charAt(0).toUpperCase() }}
              </div>
              <!-- Unread badge -->
              <span
                v-if="chatStore.unreadCount(conversation.id) > 0"
                class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] px-1.5 min-w-[1.1rem] h-[1.1rem] rounded-full font-semibold flex items-center justify-center"
              >
                {{ chatStore.unreadCount(conversation.id) > 9 ? '9+' : chatStore.unreadCount(conversation.id) }}
              </span>
            </div>

            <!-- Content -->
            <div class="flex-1 min-w-0">
              <div class="flex items-start justify-between gap-2">
                <p
                  class="text-sm font-semibold truncate"
                  :class="chatStore.unreadCount(conversation.id) > 0 ? 'text-gray-900 dark:text-white' : 'text-gray-700 dark:text-gray-200'"
                >
                  {{ chatStore.conversationTitle(conversation) }}
                </p>
                <span class="text-[11px] text-gray-400 whitespace-nowrap shrink-0 mt-0.5">
                  {{ formatTimestamp(conversation.updated_at) }}
                </span>
              </div>
              <p
                class="text-xs truncate mt-1"
                :class="chatStore.unreadCount(conversation.id) > 0 ? 'text-gray-700 dark:text-gray-300 font-medium' : 'text-gray-500 dark:text-gray-400'"
              >
                <span v-if="lastMessage(conversation)?.user_id === authStore.user?.id" class="font-semibold">You: </span>
                {{ lastMessage(conversation)?.content ?? 'Start the conversation' }}
              </p>
            </div>

            <!-- Hover chevron -->
            <svg
              xmlns="http://www.w3.org/2000/svg"
              class="w-4 h-4 text-gray-300 dark:text-gray-600 group-hover:text-gray-400 dark:group-hover:text-gray-500 transition shrink-0"
              fill="none" viewBox="0 0 24 24" stroke="currentColor"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </button>
        </li>
      </ul>
    </BaseCard>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { useAuthStore } from '../stores/auth';
import { useChatStore } from '../stores/chat';
import SearchBar from '../components/UI/SearchBar.vue';
import BaseCard from '../components/UI/BaseCard.vue';

const authStore = useAuthStore();
const chatStore = useChatStore();

const conversations = computed(() => chatStore.sortedConversations);

onMounted(async () => {
  if (!chatStore.conversations.length) {
    await chatStore.fetchConversations();
  }
});

const handleSelectConversation = async (conversationId) => {
  await chatStore.openConversation(conversationId);
};

const participantAvatar = (conversation) => {
  const participant = conversation.users?.find((user) => user.id !== authStore.user?.id);
  return participant?.avatar_url ?? null;
};

const lastMessage = (conversation) =>
  conversation.messages?.[conversation.messages.length - 1] ?? null;

const formatTimestamp = (value) => {
  if (!value) return '';
  const date = new Date(value);
  const now = new Date();
  const diffMs = now - date;
  const diffMins = Math.floor(diffMs / 60000);
  const diffHours = Math.floor(diffMs / 3600000);
  const diffDays = Math.floor(diffMs / 86400000);

  if (diffMins < 1) return 'Just now';
  if (diffMins < 60) return `${diffMins}m ago`;
  if (diffHours < 24) return `${diffHours}h ago`;
  if (diffDays < 7) return `${diffDays}d ago`;
  return date.toLocaleDateString();
};
</script>
