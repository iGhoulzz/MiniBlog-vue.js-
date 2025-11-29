<template>
  <div class="space-y-6">

    <!-- Create Post Form -->
    <BaseCard class="mb-8 p-6" v-if="authStore.user">
      <h2 class="text-xl font-bold mb-4 text-gray-800 dark:text-white">Create a New Post</h2>

      <div v-if="postError" class="p-3 mb-4 text-sm text-red-700 bg-red-100 dark:bg-red-900 dark:text-red-200 rounded-lg">
        {{ postError }}
      </div>

      <form @submit.prevent="handleCreatePost">
        <textarea
          v-model="newPostContent"
          class="w-full border border-gray-300 dark:border-gray-600 rounded-lg p-4 mb-4 focus:ring-blue-500 focus:border-blue-500 transition bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400"
          rows="3"
          :placeholder="`What's on your mind, ${authStore.user?.name}?`"
        ></textarea>
        <div class="flex justify-end">
          <BaseButton type="submit" :disabled="isCreating">
            <span v-if="isCreating">Posting...</span>
            <span v-else>Post</span>
          </BaseButton>
        </div>
      </form>
    </BaseCard>

    <div class="space-y-6">
      <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Post Feed</h2>

      <PostItem
        v-for="post in posts"
        :key="post.id"
        :post="post"
        @delete-post="handleDeletePost"
        @comment-added="fetchPosts"
        @comment-deleted="fetchPosts"
      />

      <div v-if="posts.length === 0" class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md text-center transition-colors duration-300">
        <p class="text-gray-500 dark:text-gray-400">No posts yet. Be the first to share something!</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth.js';
import { useNotificationStore } from '../stores/notification';
import api from '../services/api.js';
import PostItem from '../components/Posts/PostItem.vue';
import BaseCard from '../components/UI/BaseCard.vue';
import BaseButton from '../components/UI/BaseButton.vue';

// --- 1. STATE ---
const posts = ref([]);
const newPostContent = ref('');
const postError = ref('');
const isCreating = ref(false); // New state for create post button loading
const router = useRouter();
const authStore = useAuthStore();
const notificationStore = useNotificationStore();

// --- 2. BEHAVIOR: Fetch All Posts ---
const fetchPosts = async () => {
  if (authStore.token) {
    try {
      const response = await api.get('/posts');
      posts.value = response.data;
    } catch (error) {
      console.error('Error fetching posts:', error);
      if (error.response && (error.response.status === 401 || error.response.status === 403)) {
        authStore.logout();
        router.push('/auth/login');
      }
    }
  }
};

onMounted(fetchPosts);

// --- 3. BEHAVIOR: Create a New Post ---
const handleCreatePost = async () => {
  postError.value = '';
  if (newPostContent.value.trim() === '') {
    postError.value = 'Post content cannot be empty.';
    return;
  }

  try {
    const response = await api.post('/posts', {
      content: newPostContent.value
    });
    newPostContent.value = '';
    notificationStore.showNotification({ message: response.data.message });
    await fetchPosts(); // Refetch posts to show the new one
  } catch (error) {
    if (error.response && error.response.data.errors) {
      postError.value = error.response.data.errors.content[0];
    } else {
      postError.value = 'An error occurred while creating the post.';
      console.error(error);
    }
  }
};

// --- 4. BEHAVIOR: Delete a Post ---
const handleDeletePost = async (postId) => {
  if (!confirm('Are you sure you want to delete this post?')) {
    return;
  }

  try {
    const response = await api.delete(`/posts/${postId}`);
    posts.value = posts.value.filter(post => post.id !== postId);
    notificationStore.showNotification({ message: response.data.message });
  } catch (error) {
    console.error('Error deleting post:', error);
    notificationStore.showNotification({ message: 'Error deleting post.', type: 'error' });
  }
};
</script>

<style scoped>
/* Add any component-specific styles here */
</style>
