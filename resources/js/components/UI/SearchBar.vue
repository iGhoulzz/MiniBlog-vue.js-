<template>
  <div ref="rootRef" class="relative w-full">
    <!-- Search Input -->
    <div
      class="relative flex items-center rounded-2xl border transition-all duration-200"
      :class="[
        isFocused
          ? 'border-blue-500 ring-2 ring-blue-500/20 bg-white dark:bg-gray-700 shadow-lg'
          : 'border-gray-200 dark:border-gray-600 bg-gray-100 dark:bg-gray-700/60 hover:bg-white dark:hover:bg-gray-700 hover:border-gray-300 dark:hover:border-gray-500',
      ]"
    >
      <!-- Search Icon -->
      <div class="pl-4 pr-2 flex items-center pointer-events-none">
        <svg
          xmlns="http://www.w3.org/2000/svg"
          class="w-5 h-5 transition-colors duration-200"
          :class="isFocused ? 'text-blue-500' : 'text-gray-400 dark:text-gray-500'"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
          />
        </svg>
      </div>

      <input
        ref="inputRef"
        v-model="query"
        type="text"
        class="flex-1 py-3 pr-2 bg-transparent text-sm text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none"
        :placeholder="placeholderText"
        @focus="handleFocus"
        @keydown.escape="handleEscape"
      />

      <!-- Loading spinner -->
      <div v-if="isLoading" class="pr-3 flex items-center">
        <div class="w-4 h-4 border-2 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
      </div>

      <!-- Clear button -->
      <button
        v-else-if="query.length > 0"
        type="button"
        class="pr-3 pl-1 flex items-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition"
        @click="clearSearch"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>

    <!-- Results Dropdown -->
    <transition name="search-dropdown">
      <div
        v-if="showResults"
        class="absolute left-0 right-0 mt-2 bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-100 dark:border-gray-700 overflow-hidden z-50"
      >
        <!-- General Mode Results -->
        <template v-if="mode === 'general'">
          <!-- Users Section -->
          <div v-if="results.users?.length > 0">
            <div class="px-4 py-2.5 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-100 dark:border-gray-700">
              <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">People</p>
            </div>
            <ul>
              <li v-for="user in results.users.slice(0, 5)" :key="'user-' + user.id">
                <button
                  type="button"
                  class="w-full px-4 py-3 flex items-center gap-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition text-left"
                  @click="handleUserClick(user)"
                >
                  <img
                    v-if="user.avatar_url"
                    :src="user.avatar_url"
                    :alt="user.name"
                    class="w-10 h-10 rounded-full object-cover border border-gray-100 dark:border-gray-600"
                  />
                  <div
                    v-else
                    class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-purple-500 text-white font-semibold flex items-center justify-center text-sm"
                  >
                    {{ user.name?.charAt(0).toUpperCase() }}
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 truncate">{{ user.name }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ user.email }}</p>
                  </div>
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                  </svg>
                </button>
              </li>
            </ul>
          </div>

          <!-- Posts Section -->
          <div v-if="results.posts?.length > 0">
            <div class="px-4 py-2.5 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-100 dark:border-gray-700" :class="{ 'border-t': results.users?.length > 0 }">
              <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Posts</p>
            </div>
            <ul>
              <li v-for="post in results.posts.slice(0, 5)" :key="'post-' + post.id">
                <button
                  type="button"
                  class="w-full px-4 py-3 flex items-center gap-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition text-left"
                  @click="handlePostClick(post)"
                >
                  <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-900/30 text-blue-500 flex items-center justify-center shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2" />
                    </svg>
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="text-sm text-gray-800 dark:text-gray-100 truncate">{{ post.content }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                      by {{ post.user?.name ?? 'Unknown' }} · {{ post.comments_count ?? 0 }} comments
                    </p>
                  </div>
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-300 dark:text-gray-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                  </svg>
                </button>
              </li>
            </ul>
          </div>

          <!-- View All Results Button -->
          <div
            v-if="(results.users_total > 5 || results.posts_total > 5)"
            class="border-t border-gray-100 dark:border-gray-700"
          >
            <button
              type="button"
              class="w-full px-4 py-3 flex items-center justify-center gap-2 text-sm font-medium text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition"
              @click="handleShowMore()"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
              View All Results
            </button>
          </div>

          <!-- No Results -->
          <div v-if="hasSearched && !results.users?.length && !results.posts?.length" class="px-4 py-8 text-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 mx-auto text-gray-300 dark:text-gray-600 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <p class="text-sm text-gray-500 dark:text-gray-400">No results found for "<span class="font-medium">{{ query }}</span>"</p>
          </div>
        </template>

        <!-- Messages Mode Results -->
        <template v-if="mode === 'messages'">
          <div v-if="results.messages?.length > 0">
            <div class="px-4 py-2.5 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-100 dark:border-gray-700">
              <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Messages</p>
            </div>
            <ul class="max-h-80 overflow-y-auto">
              <li v-for="message in results.messages.slice(0, 5)" :key="'msg-' + message.id">
                <button
                  type="button"
                  class="w-full px-4 py-3 flex items-center gap-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition text-left"
                  @click="handleMessageClick(message)"
                >
                  <img
                    v-if="message.user?.avatar_url"
                    :src="message.user.avatar_url"
                    :alt="message.user.name"
                    class="w-10 h-10 rounded-full object-cover border border-gray-100 dark:border-gray-600 shrink-0"
                  />
                  <div
                    v-else
                    class="w-10 h-10 rounded-full bg-gradient-to-br from-purple-500 to-blue-500 text-white font-semibold flex items-center justify-center text-sm shrink-0"
                  >
                    {{ message.user?.name?.charAt(0)?.toUpperCase() ?? '?' }}
                  </div>
                  <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between">
                      <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 truncate pr-2">{{ message.user?.name ?? 'Unknown' }}</p>
                      <span class="text-[11px] text-gray-400 whitespace-nowrap">{{ formatTimestamp(message.created_at) }}</span>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate mt-0.5">{{ message.content }}</p>
                  </div>
                </button>
              </li>
            </ul>
          </div>

          <!-- View All Messages Button -->
          <div
            v-if="results.messages_total > 5"
            class="border-t border-gray-100 dark:border-gray-700"
          >
            <button
              type="button"
              class="w-full px-4 py-3 flex items-center justify-center gap-2 text-sm font-medium text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition"
              @click="handleShowMore('messages')"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
              View All Messages
            </button>
          </div>

          <!-- No Results -->
          <div v-if="hasSearched && !results.messages?.length" class="px-4 py-8 text-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 mx-auto text-gray-300 dark:text-gray-600 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <p class="text-sm text-gray-500 dark:text-gray-400">No messages found for "<span class="font-medium">{{ query }}</span>"</p>
          </div>
        </template>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, watch, onMounted, onBeforeUnmount, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useChatStore } from '../../stores/chat';
import api from '../../services/api';

const props = defineProps({
  mode: {
    type: String,
    default: 'general',
    validator: (value) => ['general', 'messages'].includes(value),
  },
  placeholder: {
    type: String,
    default: '',
  },
});

const router = useRouter();
const chatStore = useChatStore();

const rootRef = ref(null);
const inputRef = ref(null);
const query = ref('');
const results = ref({});
const isLoading = ref(false);
const isFocused = ref(false);
const hasSearched = ref(false);

let debounceTimer = null;

const placeholderText = computed(() => {
  if (props.placeholder) return props.placeholder;
  return props.mode === 'messages'
    ? 'Search messages...'
    : 'Search people, posts...';
});

const showResults = computed(() => {
  return isFocused.value && (hasSearched.value || isLoading.value);
});

// Debounced search
watch(query, (newVal) => {
  clearTimeout(debounceTimer);
  if (!newVal.trim()) {
    results.value = {};
    hasSearched.value = false;
    isLoading.value = false;
    return;
  }
  isLoading.value = true;
  debounceTimer = setTimeout(() => {
    performSearch(newVal.trim());
  }, 300);
});

async function performSearch(q) {
  try {
    const endpoint = props.mode === 'messages' ? '/search/messages' : '/search';
    const { data } = await api.get(endpoint, { params: { q } });
    results.value = data;
    hasSearched.value = true;
  } catch (error) {
    console.error('[SearchBar] search failed', error);
    results.value = {};
  } finally {
    isLoading.value = false;
  }
}

function handleFocus() {
  isFocused.value = true;
}

function handleEscape() {
  isFocused.value = false;
  inputRef.value?.blur();
}

function clearSearch() {
  query.value = '';
  results.value = {};
  hasSearched.value = false;
  inputRef.value?.focus();
}

function handleUserClick(user) {
  isFocused.value = false;
  query.value = '';
  results.value = {};
  hasSearched.value = false;
  router.push({ name: 'UserProfile', params: { id: user.id } });
}

function handlePostClick(post) {
  isFocused.value = false;
  query.value = '';
  results.value = {};
  hasSearched.value = false;
  router.push({ name: 'Post', params: { id: post.id } });
}

function handleMessageClick(message) {
  isFocused.value = false;
  query.value = '';
  results.value = {};
  hasSearched.value = false;
  if (message.conversation_id) {
    chatStore.openConversation(message.conversation_id);
  }
}

function handleShowMore(type) {
  const routeQuery = { q: query.value };
  if (type) routeQuery.type = type;
  isFocused.value = false;
  query.value = '';
  results.value = {};
  hasSearched.value = false;
  router.push({ name: 'SearchResults', query: routeQuery });
}

function formatTimestamp(value) {
  if (!value) return '';
  const date = new Date(value);
  const now = new Date();
  const diffMs = now - date;
  const diffMins = Math.floor(diffMs / 60000);
  const diffHours = Math.floor(diffMs / 3600000);
  const diffDays = Math.floor(diffMs / 86400000);
  if (diffMins < 1) return 'Just now';
  if (diffMins < 60) return `${diffMins}m`;
  if (diffHours < 24) return `${diffHours}h`;
  if (diffDays < 7) return `${diffDays}d`;
  return date.toLocaleDateString();
}

function handleClickOutside(event) {
  if (rootRef.value && !rootRef.value.contains(event.target)) {
    isFocused.value = false;
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
});

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside);
  clearTimeout(debounceTimer);
});
</script>

<style scoped>
.search-dropdown-enter-active,
.search-dropdown-leave-active {
  transition: all 0.2s ease;
}

.search-dropdown-enter-from,
.search-dropdown-leave-to {
  opacity: 0;
  transform: translateY(-6px);
}
</style>
