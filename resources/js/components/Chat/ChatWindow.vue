<template>
  <div
    class="w-80 bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-100 dark:border-gray-700 flex flex-col overflow-hidden transition-colors duration-300"
    :class="[{ 'ring-2 ring-blue-500': isActive && !isMinimized }]"
  >
    <header
      class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between bg-gray-50 dark:bg-gray-900 cursor-pointer"
      @click="handleFocus"
    >
      <div class="flex items-center gap-3">
        <img
          v-if="headerAvatar"
          :src="headerAvatar"
          :alt="title"
          class="w-10 h-10 rounded-full object-cover border border-white shadow"
        >
        <div
          v-else
          class="w-10 h-10 rounded-full bg-gradient-to-br from-purple-500 to-blue-500 text-white font-semibold flex items-center justify-center"
        >
          {{ title.charAt(0).toUpperCase() }}
        </div>
        <div>
          <p class="text-sm font-semibold text-gray-900 dark:text-gray-100 leading-tight">{{ title }}</p>
          <p class="text-xs text-gray-500 dark:text-gray-400">{{ subtitle }}</p>
        </div>
      </div>
      <div class="flex items-center gap-2">
        <div class="relative">
          <button
            class="text-gray-400 hover:text-gray-600 transition"
            type="button"
            aria-label="Toggle chat window"
            @click.stop="toggleMinimized"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="1.5"
                :d="isMinimized ? 'm19 9-7 7-7-7' : 'm5 15 7-7 7 7'"
              />
            </svg>
          </button>
          <span
            v-if="showMinimizedBadge"
            class="absolute -top-1 -right-1 inline-flex min-w-[1.1rem] items-center justify-center rounded-full bg-rose-500 px-1 py-0.5 text-[10px] font-semibold leading-none text-white shadow"
          >
            {{ minimizedBadgeLabel }}
          </span>
        </div>
        <button
          v-if="!isPending"
          class="text-gray-400 hover:text-rose-500 transition disabled:opacity-40"
          type="button"
          :disabled="isClearing"
          aria-label="Clear conversation"
          @click.stop="handleClearConversation"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 3.75h6m4.5 3h-15m12 0-.8 11.2a2 2 0 0 1-2 1.8H9.3a2 2 0 0 1-2-1.8L6.5 6.75m3 4.5v6m4-6v6" />
          </svg>
        </button>
        <button
          class="text-gray-400 hover:text-gray-600 transition"
          type="button"
          aria-label="Close chat window"
          @click.stop="$emit('close')"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m6 18 12-12M6 6l12 12" />
          </svg>
        </button>
      </div>
    </header>

    <div v-show="!isMinimized" class="flex flex-col h-96 bg-white dark:bg-gray-800">
      <div ref="messagesContainer" class="flex-1 overflow-y-auto px-4 py-3 space-y-3 bg-white dark:bg-gray-800">
        <div v-if="!messages.length" class="h-full flex items-center justify-center text-sm text-gray-400">
          <p v-if="isPending">Say hi to {{ pendingUser?.name ?? 'your friend' }} to start the conversation.</p>
          <p v-else>No messages yet. Start the conversation below.</p>
        </div>
        <template v-else>
          <div
            v-for="message in messages"
            :key="message.id ?? message.created_at"
            class="flex group relative"
            :class="[message.user_id === authStore.user?.id ? 'justify-end' : 'justify-start']"
          >
            <div
              class="max-w-[90%] rounded-2xl px-4 py-2 text-sm shadow-sm cursor-pointer relative"
              :class="message.user_id === authStore.user?.id ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-100'"
              @click="toggleMessageMenu(message.id)"
            >
              <!-- Delete button popup -->
              <div
                v-if="selectedMessageId === message.id"
                class="absolute z-10 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-600 py-1 px-1"
                :class="message.user_id === authStore.user?.id ? 'right-full mr-2 top-0' : 'left-full ml-2 top-0'"
              >
                <button
                  type="button"
                  class="flex items-center gap-2 px-3 py-1.5 text-xs text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded-md transition whitespace-nowrap"
                  :disabled="isDeleting"
                  @click.stop="handleDeleteMessage(message.id)"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                  {{ isDeleting ? 'Deleting...' : 'Delete for me' }}
                </button>
              </div>

              <p class="whitespace-pre-line break-words">{{ message.content }}</p>
              <div class="text-[11px] mt-2 flex justify-between gap-2 opacity-80">
                <span>{{ message.user_id === authStore.user?.id ? 'You' : message.user?.name ?? 'User' }}</span>
                <span>{{ formatMessageTimestamp(message.created_at) }}</span>
              </div>
              <div
                v-if="message.user_id === authStore.user?.id"
                class="mt-1 flex justify-end items-center gap-1 min-h-[18px]"
              >
                <template v-if="readAvatarsByMessageId[message.id]?.length">
                  <div
                    v-for="reader in readAvatarsByMessageId[message.id]"
                    :key="`read-${message.id}-${reader.id}`"
                    class="-ml-2 first:ml-0 w-5 h-5 rounded-full border border-white shadow-sm overflow-hidden"
                    :title="reader.name ? `Read by ${reader.name}` : 'Read'"
                  >
                    <img
                      v-if="reader.avatar_url"
                      :src="reader.avatar_url"
                      :alt="reader.name ?? 'Participant avatar'"
                      class="w-full h-full object-cover"
                    >
                    <div
                      v-else
                      class="w-full h-full bg-gray-200 text-[10px] font-semibold flex items-center justify-center text-gray-600"
                    >
                      {{ reader.name?.charAt(0)?.toUpperCase() ?? '?' }}
                    </div>
                  </div>
                </template>
                <span
                  v-else
                  class="text-[10px] uppercase tracking-wide opacity-80"
                >
                  {{ chatStore.messageStatus(conversation?.id, message) }}
                </span>
              </div>
            </div>
          </div>
        </template>
      </div>
      <div class="border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 px-4 py-3">
        <div class="relative">
          <textarea
            v-model="draftMessage"
            rows="2"
            class="w-full resize-none rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 p-3 pr-12 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400"
            placeholder="Write a message..."
            @keydown.enter.exact.prevent="handleSend"
          />
          <button
            class="absolute right-2 bottom-2 bg-blue-600 text-white rounded-lg px-3 py-1 text-xs font-semibold hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
            type="button"
            :disabled="!draftMessage.trim() || isSending"
            @click="handleSend"
          >
            Send
          </button>
        </div>
        <p class="text-[11px] text-gray-400 mt-2">{{ sendHint }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, nextTick, ref, watch } from 'vue';
import { useAuthStore } from '../../stores/auth';
import { useChatStore } from '../../stores/chat';
import { useNotificationStore } from '../../stores/notification';

const props = defineProps({
  windowId: {
    type: String,
    required: true,
  },
  stackIndex: {
    type: Number,
    default: 0,
  },
  isActive: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['close', 'focus-window']);

const authStore = useAuthStore();
const chatStore = useChatStore();
const notificationStore = useNotificationStore();

const isMinimized = ref(false);
const messagesContainer = ref(null);
const isSending = ref(false);
const isClearing = ref(false);
const selectedMessageId = ref(null);
const isDeleting = ref(false);

const isPending = computed(() => props.windowId.startsWith('pending-'));
const conversation = computed(() =>
  isPending.value ? null : chatStore.getConversationById(Number(props.windowId))
);

const pendingUser = computed(() => chatStore.pendingTargets[props.windowId]);

const draftMessage = computed({
  get: () => chatStore.getDraft(props.windowId),
  set: (value) => chatStore.setDraft(props.windowId, value),
});

const messages = computed(() => conversation.value?.messages ?? []);

const readAvatarsByMessageId = computed(() => {
  if (!conversation.value?.id) {
    return Object.create(null);
  }

  // Depend explicitly on snapshot updates to refresh avatars immediately.
  const snapshotVersion = chatStore.readerSnapshotsVersion;
  void snapshotVersion;

  const map = Object.create(null);
  const convId = conversation.value.id;
  (conversation.value.messages ?? []).forEach((message) => {
    if (!message?.id) return;
    const readers = chatStore.readersForMessage(convId, message.id);
    if (readers.length) {
      map[message.id] = readers;
    }
  });

  return map;
});

const unreadCount = computed(() =>
  conversation.value?.id ? chatStore.unreadCount(conversation.value.id) : 0
);
const minimizedUnreadCount = computed(() => (isMinimized.value ? unreadCount.value : 0));
const showMinimizedBadge = computed(() => minimizedUnreadCount.value > 0);
const minimizedBadgeLabel = computed(() =>
  minimizedUnreadCount.value > 99 ? '99+' : String(minimizedUnreadCount.value)
);

const title = computed(() => {
  if (conversation.value) {
    return chatStore.conversationTitle(conversation.value) || 'Conversation';
  }
  return pendingUser.value?.name ?? 'New Message';
});

const subtitle = computed(() => {
  if (pendingUser.value?.email) {
    return pendingUser.value.email;
  }
  if (conversation.value?.users?.length === 2) {
    return 'Direct message';
  }
  if (conversation.value?.users?.length > 2) {
    return `${conversation.value.users.length} participants`;
  }
  return 'Chat';
});

const headerAvatar = computed(() => {
  if (pendingUser.value?.avatar_url) {
    return pendingUser.value.avatar_url;
  }
  const otherUser = conversation.value?.users?.find((user) => user.id !== authStore.user?.id);
  return otherUser?.avatar_url ?? null;
});

const sendHint = computed(() =>
  isPending.value
    ? 'Start the conversation with your first message.'
    : 'Press Enter to send, Shift+Enter for a new line'
);

const formatMessageTimestamp = (value) => {
  if (!value) {
    return '';
  }
  return new Intl.DateTimeFormat('en', {
    hour: 'numeric',
    minute: 'numeric',
  }).format(new Date(value));
};

const scrollToBottom = () => {
  nextTick(() => {
    if (messagesContainer.value) {
      messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
    }
  });
};

watch(
  () => messages.value.length,
  () => {
    scrollToBottom();
    if (!isMinimized.value && conversation.value?.id) {
      chatStore.markConversationRead(conversation.value.id);
    }
  },
  { immediate: true }
);

watch(
  () => [props.isActive, isMinimized.value],
  ([isFocused, minimized]) => {
    if (isFocused && !minimized) {
      scrollToBottom();
      if (conversation.value?.id) {
        chatStore.markConversationRead(conversation.value.id);
      }
    }
  },
  { immediate: true }
);

const handleSend = async () => {
  if (!draftMessage.value.trim() || isSending.value) {
    return;
  }
  isSending.value = true;
  try {
    await chatStore.sendMessage(props.windowId, draftMessage.value);
  } catch (error) {
    console.error('Failed to send message', error);
    notificationStore.showNotification({
      message: error.response?.data?.message ?? 'Unable to send message.',
      type: 'error',
    });
  } finally {
    isSending.value = false;
  }
};

const handleClearConversation = async () => {
  if (isPending.value || !conversation.value?.id || isClearing.value) {
    return;
  }

  const confirmed = window.confirm(
    'Clear this conversation? This removes the chat history for you and cannot be undone.'
  );
  if (!confirmed) {
    return;
  }

  isClearing.value = true;
  try {
    await chatStore.clearConversation(conversation.value.id);
    notificationStore.showNotification({
      message: 'Conversation cleared.',
      type: 'success',
    });
  } catch (error) {
    console.error('Failed to clear conversation', error);
    notificationStore.showNotification({
      message: error.response?.data?.message ?? 'Unable to clear conversation.',
      type: 'error',
    });
  } finally {
    isClearing.value = false;
  }
};

const handleFocus = () => {
  emit('focus-window');
  toggleMinimized(false);
};

function toggleMinimized(value) {
  if (typeof value === 'boolean') {
    isMinimized.value = value;
  } else {
    isMinimized.value = !isMinimized.value;
  }
  if (!isMinimized.value) {
    scrollToBottom();
    if (conversation.value?.id) {
      chatStore.markConversationRead(conversation.value.id);
    }
  }
}

function toggleMessageMenu(messageId) {
  if (selectedMessageId.value === messageId) {
    selectedMessageId.value = null;
  } else {
    selectedMessageId.value = messageId;
  }
}

async function handleDeleteMessage(messageId) {
  if (!conversation.value?.id || isDeleting.value) return;

  isDeleting.value = true;
  try {
    await chatStore.deleteMessage(conversation.value.id, messageId);
    selectedMessageId.value = null;
    notificationStore.showNotification({
      message: 'Message deleted for you.',
      type: 'success',
    });
  } catch (error) {
    console.error('Failed to delete message', error);
    notificationStore.showNotification({
      message: error.response?.data?.message ?? 'Unable to delete message.',
      type: 'error',
    });
  } finally {
    isDeleting.value = false;
  }
}
</script>
