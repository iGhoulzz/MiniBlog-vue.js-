<template>
  <div class="space-y-6">
    <!-- Header / Search Input -->
    <div class="flex items-center gap-4 bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm">
      <div class="relative flex-1">
        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
          </svg>
        </div>
        <input
          v-model="searchQuery"
          type="text"
          class="block w-full pl-10 pr-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md leading-5 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
          placeholder="Search..."
          @keydown.enter="updateSearch"
        />
      </div>
      <button
        @click="updateSearch"
        class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
      >
        Search
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading && !isLoadingMore" class="flex justify-center py-12">
      <svg class="animate-spin h-8 w-8 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
      </svg>
    </div>

    <!-- Results -->
    <div v-else class="space-y-8">

      <!-- Users Section -->
      <section v-if="mode !== 'messages' && (users.length > 0 || (mode === 'users' && users.length === 0))">
        <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100 mb-4 flex items-center gap-2">
            <span>People</span>
            <span v-if="users.length" class="text-sm font-normal text-gray-500">
                ({{ users.length }}{{ nextUserCursor ? '+' : '' }})
            </span>
        </h2>

        <div v-if="users.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div
            v-for="user in users"
            :key="user.id"
            class="flex items-center p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-md transition cursor-pointer"
            @click="navigateToUser(user.id)"
          >
            <img
              v-if="user.avatar_url"
              :src="user.avatar_url"
              :alt="user.name"
              class="w-12 h-12 rounded-full object-cover border border-gray-100 dark:border-gray-600"
            />
            <div
              v-else
              class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-500 to-purple-500 text-white font-bold flex items-center justify-center text-lg"
            >
              {{ user.name?.charAt(0).toUpperCase() }}
            </div>
            <div class="ml-4 flex-1 min-w-0">
              <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ user.name }}</p>
              <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ user.email }}</p>
            </div>
          </div>
        </div>
        <div v-else class="text-gray-500 dark:text-gray-400 italic">No people found.</div>

        <div v-if="nextUserCursor" class="mt-4 text-center">
            <button
                @click="loadMore('users')"
                class="text-sm text-blue-600 dark:text-blue-400 hover:underline"
                :disabled="isLoadingMore"
            >
                Load more people
            </button>
        </div>
      </section>

      <!-- Posts Section -->
      <section v-if="mode !== 'messages' && (posts.length > 0 || (mode === 'posts' && posts.length === 0))">
         <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100 mb-4 flex items-center gap-2">
            <span>Posts</span>
            <span v-if="posts.length" class="text-sm font-normal text-gray-500">
                ({{ posts.length }}{{ nextPostCursor ? '+' : '' }})
            </span>
        </h2>

        <div v-if="posts.length > 0" class="space-y-6">
          <PostItem
            v-for="post in posts"
            :key="post.id"
            :post="post"
            @delete-post="handleDeletePost"
            @comment-added="handleCommentAdded"
            @comment-deleted="handleCommentDeleted"
          />
        </div>
        <div v-else class="text-gray-500 dark:text-gray-400 italic">No posts found.</div>

        <div v-if="nextPostCursor" class="mt-6 text-center">
             <button
                @click="loadMore('posts')"
                class="px-4 py-2 border border-blue-600 text-blue-600 dark:text-blue-400 dark:border-blue-400 rounded hover:bg-blue-50 dark:hover:bg-gray-800 transition"
                :disabled="isLoadingMore"
            >
                Load more posts
            </button>
        </div>
      </section>

      <!-- Messages Section -->
      <section v-if="mode === 'messages'">
        <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100 mb-4">Messages</h2>

        <div v-if="messages.length > 0" class="space-y-2">
            <div
                v-for="message in messages"
                :key="message.id"
                class="p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50 transition"
                @click="navigateToConversation(message.conversation_id)"
            >
                <div class="flex items-center justify-between mb-1">
                    <span class="font-semibold text-gray-900 dark:text-white">{{ message.user?.name }}</span>
                    <span class="text-xs text-gray-500">{{ new Date(message.created_at).toLocaleDateString() }}</span>
                </div>
                <p class="text-gray-600 dark:text-gray-300 text-sm line-clamp-2">{{ message.content }}</p>
            </div>
        </div>
         <div v-else class="text-center py-8 text-gray-500 dark:text-gray-400">
            No messages found matching "{{ searchQuery }}"
        </div>

        <div v-if="nextMessageCursor" class="mt-6 text-center">
             <button
                @click="loadMore('messages')"
                class="px-4 py-2 border border-blue-600 text-blue-600 dark:text-blue-400 dark:border-blue-400 rounded hover:bg-blue-50 dark:hover:bg-gray-800 transition"
                :disabled="isLoadingMore"
            >
                Load more messages
            </button>
        </div>
      </section>

       <!-- No Results Empty State -->
       <div v-if="isLoaded && !hasResults" class="text-center py-12">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">No results found</h3>
            <p class="text-gray-500 dark:text-gray-400 mt-1">We couldn't find anything for "<span class="font-semibold">{{ searchQuery }}</span>".</p>
       </div>

    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../services/api';
import PostItem from '../components/Posts/PostItem.vue';
import { useChatStore } from '../stores/chat';
import { useNotificationStore } from '../stores/notification';

const route = useRoute();
const router = useRouter();
const chatStore = useChatStore();
const notificationStore = useNotificationStore();

const searchQuery = ref('');
const mode = ref('all'); // 'all', 'users', 'posts', 'messages'

const users = ref([]);
const posts = ref([]);
const messages = ref([]);

const nextUserCursor = ref(null);
const nextPostCursor = ref(null);
const nextMessageCursor = ref(null);

const isLoading = ref(false);
const isLoadingMore = ref(false);
const isLoaded = ref(false); // To show empty state only after first load

const hasResults = computed(() => {
    if (mode.value === 'messages') return messages.value.length > 0;
    return users.value.length > 0 || posts.value.length > 0;
});

// Initialize from URL
onMounted(() => {
    handleRouteUpdate();
});

watch(() => route.query, () => {
    handleRouteUpdate();
});

function handleRouteUpdate() {
    searchQuery.value = route.query.q || '';
    mode.value = route.query.type || 'all'; // Default to show everything except messages unless specified

    if (searchQuery.value) {
        performSearch(true);
    } else {
        // Clear results if query is empty
        users.value = [];
        posts.value = [];
        messages.value = [];
        isLoaded.value = false;
    }
}

async function performSearch(reset = true) {
    if (!searchQuery.value.trim()) return;

    isLoading.value = true;
    isLoaded.value = false;

    try {
        const params = {
            q: searchQuery.value,
            type: mode.value === 'all' ? undefined : mode.value
        };

        const { data } = await api.get('/search/all', { params });

        if (reset) {
            users.value = [];
            posts.value = [];
            messages.value = [];
        }

        // Handle Users
        if (data.users) {
            users.value = data.users.data;
            nextUserCursor.value = data.users.next_cursor;
        }

        // Handle Posts
        if (data.posts) {
            posts.value = data.posts.data;
            nextPostCursor.value = data.posts.next_cursor;
        }

        // Handle Messages
        if (data.messages) {
             messages.value = data.messages.data;
             nextMessageCursor.value = data.messages.next_cursor;
        }

    } catch (error) {
        console.error('Search failed:', error);
    } finally {
        isLoading.value = false;
        isLoaded.value = true;
    }
}

function updateSearch() {
    const query = { q: searchQuery.value };
    if (mode.value !== 'all') {
        query.type = mode.value;
    }
    router.push({ name: 'SearchResults', query });
}

async function loadMore(type) {
    isLoadingMore.value = true;
    let cursor = null;
    let endpointType = type;

    if (type === 'users') cursor = nextUserCursor.value;
    if (type === 'posts') cursor = nextPostCursor.value;
    if (type === 'messages') cursor = nextMessageCursor.value;

    if (!cursor) return;

    try {
        const params = {
            q: searchQuery.value,
            type: endpointType,
            cursor: cursor
        };

        const { data } = await api.get('/search/all', { params });

        if (type === 'users' && data.users) {
            users.value = [...users.value, ...data.users.data];
            nextUserCursor.value = data.users.next_cursor;
        }
         if (type === 'posts' && data.posts) {
            posts.value = [...posts.value, ...data.posts.data];
            nextPostCursor.value = data.posts.next_cursor;
        }
         if (type === 'messages' && data.messages) {
            messages.value = [...messages.value, ...data.messages.data];
            nextMessageCursor.value = data.messages.next_cursor;
        }

    } catch (error) {
        console.error('Load more failed:', error);
    } finally {
        isLoadingMore.value = false;
    }
}

function navigateToUser(id) {
    router.push({ name: 'UserProfile', params: { id } });
}

function navigateToConversation(id) {
    chatStore.openConversation(id);
// --- Event Handlers copied from Home.vue ---

const handleDeletePost = async (postId) => {
  if (confirm('Are you sure you want to delete this post?')) {
    try {
      await api.delete(`/posts/${postId}`);
      posts.value = posts.value.filter(p => p.id !== postId);
      notificationStore.showNotification({ message: 'Post deleted successfully', type: 'success' });
    } catch (error) {
      console.error('Failed to delete post:', error);
      notificationStore.showNotification({ message: 'Failed to delete post', type: 'error' });
    }
  }
};

const handleCommentAdded = (postId) => {
  const post = posts.value.find(p => p.id === postId);
  if (post) {
      // Ensure comments_count is treated as a number
    post.comments_count = (parseInt(post.comments_count) || 0) + 1;
  }
};

const handleCommentDeleted = (postId) => {
  const post = posts.value.find(p => p.id === postId);
  if (post && post.comments_count > 0) {
    post.comments_count = (parseInt(post.comments_count) || 0) - 1;
  }
};

    router.push({ name: 'Messages' });
}

</script>
