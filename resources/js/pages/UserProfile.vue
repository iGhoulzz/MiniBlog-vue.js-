<template>
  <div v-if="user" class="space-y-6">
    <!-- User Profile Header -->
    <div class="bg-white p-6 rounded-lg shadow-md flex items-center">
      <div class="flex-shrink-0">
        <img v-if="user.avatar" :src="`/storage/${user.avatar}`" alt="User Avatar" class="w-24 h-24 rounded-full object-cover">
        <div v-else class="w-24 h-24 rounded-full bg-gray-300 flex items-center justify-center">
          <span class="text-gray-500 text-2xl">{{ user.name ? user.name.charAt(0) : '' }}</span>
        </div>
      </div>
      <div class="ml-6">
        <h1 class="text-3xl font-bold">{{ user.name }}</h1>
        <p class="text-gray-600">{{ user.email }}</p>
      </div>
    </div>

    <div v-if="isCurrentUser" class="mt-4">
      <router-link :to="{ name: 'EditProfile' }" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
        Edit Profile
      </router-link>
    </div>

    <!-- User's Posts -->
    <div class="space-y-6">
      <h2 class="text-2xl font-bold text-gray-800">Posts by {{ user.name }}</h2>
      <PostItem
        v-for="post in posts"
        :key="post.id"
        :post="post"
        @delete-post="handleDeletePost"
        @comment-added="fetchUserProfile"
        @comment-deleted="fetchUserProfile"
      />
      <div v-if="posts.length === 0" class="bg-white p-6 rounded-lg shadow-md text-center">
        <p class="text-gray-500">{{ user.name }} has not posted anything yet.</p>
      </div>
    </div>
  </div>
  <div v-else-if="error" class="text-center text-red-500 bg-red-100 p-4 rounded-lg">
    <p>{{ error }}</p>
  </div>
  <div v-else class="text-center">
    <p class="text-gray-500">Loading user profile...</p>
  </div>
</template>

<script setup>
import { ref, onMounted, watch, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth.js';
import api from '../services/api.js';
import PostItem from '../components/Posts/PostItem.vue';
import { useNotificationStore } from '../stores/notification';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
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

onMounted(fetchUserProfile);

// Watch for route changes to refetch data if navigating between user profiles
watch(() => route.params.id, (newId, oldId) => {
  if (newId !== oldId) {
    fetchUserProfile();
  }
});
</script>
