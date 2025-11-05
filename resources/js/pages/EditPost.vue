<template>
  <div class="bg-white p-8 rounded-lg shadow-md max-w-2xl mx-auto">
    <h1 class="text-2xl font-bold mb-6">Edit Post</h1>

    <div v-if="errorMessage" class="p-3 mb-4 text-sm text-red-700 bg-red-100 rounded-lg">
      {{ errorMessage }}
    </div>

    <form v-if="post" @submit.prevent="handleUpdatePost">
      <textarea
        v-model="post.content"
        class="w-full border border-gray-300 rounded-lg p-4 focus:ring-blue-500 focus:border-blue-500 transition"
        rows="8"
      ></textarea>

      <div class="flex justify-end mt-6">
        <router-link to="/" class="text-gray-600 font-bold py-2 px-6 mr-4 rounded-lg hover:bg-gray-100 transition">
          Cancel
        </router-link>
        <button
          type="submit"
          class="bg-blue-600 text-white font-bold py-2 px-6 rounded-lg hover:bg-blue-700 transition"
        >
          Update Post
        </button>
      </div>
    </form>
    <div v-else class="text-center">
      <p class="text-gray-500">Loading post for editing...</p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../services/api.js';
import { useNotificationStore } from '../stores/notification';

const route = useRoute();
const router = useRouter();
const post = ref(null);
const errorMessage = ref('');
const notificationStore = useNotificationStore();

onMounted(async () => {
  try {
    const postId = route.params.id;
    const response = await api.get(`/posts/${postId}/edit`);
    post.value = response.data;
  } catch (error) {
    console.error('Error fetching post for editing:', error);
    errorMessage.value = 'You are not authorized to edit this post or it does not exist.';
  }
});

const handleUpdatePost = async () => {
  errorMessage.value = '';
  if (!post.value || post.value.content.trim() === '') {
    errorMessage.value = 'Post content cannot be empty.';
    return;
  }

  try {
    const postId = route.params.id;
    const response = await api.patch(`/posts/${postId}`, {
      content: post.value.content,
    });
    notificationStore.showNotification({ message: response.data.message });
    router.push('/'); // Redirect to home page on success
  } catch (error) {
    if (error.response && error.response.data.errors) {
      errorMessage.value = error.response.data.errors.content[0];
    } else {
      errorMessage.value = 'An error occurred while updating the post.';
      console.error(error);
    }
  }
};
</script>
