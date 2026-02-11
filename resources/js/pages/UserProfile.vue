<template>
  <div v-if="user" class="space-y-6">
    <!-- User Profile Header -->
    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md transition-colors duration-300">
      <div class="flex items-center justify-between">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <img v-if="user.avatar_url" :src="user.avatar_url" alt="User Avatar" class="w-24 h-24 rounded-full object-cover border-4 border-gray-200">
            <div v-else class="w-24 h-24 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center border-4 border-gray-200">
              <span class="text-white text-3xl font-bold">{{ user.name ? user.name.charAt(0).toUpperCase() : '' }}</span>
            </div>
          </div>
          <div class="ml-6">
            <h1 class="text-3xl font-bold text-gray-800 dark:text-white">{{ user.name }}</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">{{ user.email }}</p>
            <div class="mt-2 flex items-center space-x-4 text-sm text-gray-500 dark:text-gray-400">
              <span class="flex items-center">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                {{ postsCount }} {{ postsCount === 1 ? 'Post' : 'Posts' }}
              </span>
            </div>
          </div>
        </div>

        <!-- Profile Actions -->
        <div class="flex items-center space-x-3">
          <button
            v-if="!isCurrentUser && authStore.user"
            @click="startDirectMessage"
            class="flex items-center justify-center w-10 h-10 rounded-full bg-purple-500 hover:bg-purple-600 text-white transition-all duration-200 shadow-md hover:shadow-lg"
            :title="`Message ${user.name}`"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8a9 9 0 11-5.64-1.97L5 21l1.97-.36A8.96 8.96 0 0112 20z" />
            </svg>
          </button>
          <router-link
            v-if="isCurrentUser"
            :to="{ name: 'EditProfile' }"
            class="flex items-center justify-center w-10 h-10 rounded-full bg-blue-500 hover:bg-blue-600 text-white transition-all duration-200 shadow-md hover:shadow-lg"
            title="Edit Profile"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
          </router-link>
        </div>
      </div>
    </div>

    <!-- User's Posts -->
    <div class="space-y-6">
      <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Posts by {{ user.name }}</h2>
      <PostItem
        v-for="post in posts"
        :key="post.id"
        :post="post"
        @delete-post="handleDeletePost"
        @comment-added="handleCommentAdded"
        @comment-deleted="handleCommentDeleted"
      />

      <!-- Infinite Scroll Sentinel -->
      <div ref="scrollSentinel" class="h-4"></div>

      <!-- Loading Spinner -->
      <div v-if="isLoadingMore" class="text-center py-4">
        <div class="inline-flex items-center gap-2 text-gray-500 dark:text-gray-400">
          <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
          </svg>
          <span class="text-sm">Loading more posts...</span>
        </div>
      </div>

      <div v-if="!nextCursor && posts.length > 0 && !isLoadingMore" class="text-center py-4">
        <p class="text-gray-400 dark:text-gray-500 text-sm">No more posts to show.</p>
      </div>

      <div v-if="posts.length === 0" class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md text-center transition-colors duration-300">
        <p class="text-gray-500 dark:text-gray-400">{{ user.name }} has not posted anything yet.</p>
      </div>
    </div>
  </div>
  <div v-else-if="error" class="text-center text-red-500 bg-red-100 p-4 rounded-lg">
    <p>{{ error }}</p>
  </div>
  <div v-else class="text-center">
    <p class="text-gray-500 dark:text-gray-400">Loading user profile...</p>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth.js';
import api from '../services/api.js';
import PostItem from '../components/Posts/PostItem.vue';
import { useNotificationStore } from '../stores/notification';
import { useChatStore } from '../stores/chat';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const chatStore = useChatStore();
const user = ref(null);
const posts = ref([]);
const postsCount = ref(0);
const nextCursor = ref(null);
const isLoadingMore = ref(false);
const scrollSentinel = ref(null);
let observer = null;
const error = ref('');
const notificationStore = useNotificationStore();

const isCurrentUser = computed(() => {
    return authStore.user && user.value && authStore.user.id === user.value.id;
});

const fetchUserProfile = async () => {
  try {
    const userId = route.params.id;
    const response = await api.get(`/users/${userId}`);
    user.value = response.data.user;
    posts.value = response.data.posts.data || [];
    postsCount.value = response.data.posts_count || 0;
    nextCursor.value = response.data.posts.next_cursor || null;
    error.value = '';
  } catch (err) {
    console.error('Error fetching user profile:', err);
    error.value = 'Could not load user profile. The user may not exist.';
    user.value = null;
    posts.value = [];
  }
};

const loadMorePosts = async () => {
  if (!nextCursor.value) return;
  try {
    isLoadingMore.value = true;
    const userId = route.params.id;
    const response = await api.get(`/users/${userId}`, { params: { cursor: nextCursor.value } });
    const newPosts = response.data.posts.data || [];
    posts.value = [...posts.value, ...newPosts];
    nextCursor.value = response.data.posts.next_cursor || null;
  } catch (err) {
    console.error('Error loading more posts:', err);
  } finally {
    isLoadingMore.value = false;
  }
};

const handleDeletePost = async (postId) => {
  if (!confirm('Are you sure you want to delete this post?')) {
    return;
  }
  try {
    const response = await api.delete(`/posts/${postId}`);
    posts.value = posts.value.filter(post => post.id !== postId);
    postsCount.value = Math.max(0, postsCount.value - 1);
    notificationStore.showNotification({ message: response.data.message });
  } catch (err) {
    console.error('Error deleting post:', err);
    notificationStore.showNotification({ message: 'Error deleting post.', type: 'error' });
  }
};

const handleCommentAdded = (postId) => {
  const post = posts.value.find(p => p.id === postId);
  if (post) {
    post.comments_count = (post.comments_count || 0) + 1;
  }
};

const handleCommentDeleted = (postId) => {
  const post = posts.value.find(p => p.id === postId);
  if (post && post.comments_count > 0) {
    post.comments_count -= 1;
  }
};

const startDirectMessage = () => {
  if (!user.value || isCurrentUser.value) {
    return;
  }
  chatStore.openDmWith(user.value);
};

const setupObserver = () => {
  if (observer) observer.disconnect();
  observer = new IntersectionObserver((entries) => {
    if (entries[0].isIntersecting && nextCursor.value && !isLoadingMore.value) {
      loadMorePosts();
    }
  }, { rootMargin: '200px' });
  const checkSentinel = setInterval(() => {
    if (scrollSentinel.value) {
      observer.observe(scrollSentinel.value);
      clearInterval(checkSentinel);
    }
  }, 100);
};

onMounted(() => {
  fetchUserProfile().then(setupObserver);
});

onUnmounted(() => {
  if (observer) observer.disconnect();
});

// Watch for route changes to refetch data if navigating between user profiles
watch(() => route.params.id, (newId, oldId) => {
  if (newId !== oldId) {
    fetchUserProfile().then(setupObserver);
  }
});
</script>
