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
                {{ posts.length }} {{ posts.length === 1 ? 'Post' : 'Posts' }}
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
        @comment-added="fetchUserProfile"
        @comment-deleted="fetchUserProfile"
      />
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
import { ref, onMounted, watch, computed } from 'vue';
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
    posts.value = response.data.posts;
    error.value = '';
  } catch (err) {
    console.error('Error fetching user profile:', err);
    error.value = 'Could not load user profile. The user may not exist.';
    user.value = null;
    posts.value = [];
  }
};

const handleDeletePost = async (postId) => {
  if (!confirm('Are you sure you want to delete this post?')) {
    return;
  }
  try {
    const response = await api.delete(`/posts/${postId}`);
    posts.value = posts.value.filter(post => post.id !== postId);
    notificationStore.showNotification({ message: response.data.message });
  } catch (err) {
    console.error('Error deleting post:', err);
    notificationStore.showNotification({ message: 'Error deleting post.', type: 'error' });
  }
};

const startDirectMessage = () => {
  if (!user.value || isCurrentUser.value) {
    return;
  }
  chatStore.openDmWith(user.value);
};

onMounted(fetchUserProfile);

// Watch for route changes to refetch data if navigating between user profiles
watch(() => route.params.id, (newId, oldId) => {
  if (newId !== oldId) {
    fetchUserProfile();
  }
});
</script>
