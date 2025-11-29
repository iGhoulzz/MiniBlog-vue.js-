<template>
  <BaseCard class="p-8 max-w-2xl mx-auto">
    <h1 class="text-2xl font-bold mb-6 text-gray-800 dark:text-white">Edit Post</h1>

    <div v-if="errorMessage" class="p-3 mb-4 text-sm text-red-700 bg-red-100 rounded-lg">
      {{ errorMessage }}
    </div>

    <form v-if="post" @submit.prevent="handleUpdatePost">
      <textarea
        v-model="post.content"
        class="w-full border border-gray-300 dark:border-gray-600 rounded-lg p-4 focus:ring-blue-500 focus:border-blue-500 transition bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
        rows="8"
      ></textarea>

      <div class="flex justify-end mt-6">
        <router-link to="/" class="text-gray-600 dark:text-gray-400 font-bold py-2 px-6 mr-4 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition flex items-center">
          Cancel
        </router-link>
        <BaseButton type="submit">
          Update Post
        </BaseButton>
      </div>
    </form>
    <div v-else class="text-center">
      <p class="text-gray-500 dark:text-gray-400">Loading post for editing...</p>
    </div>
  </BaseCard>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../services/api.js';
import { useNotificationStore } from '../stores/notification';
import BaseCard from '../components/UI/BaseCard.vue';
import BaseButton from '../components/UI/BaseButton.vue';

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
