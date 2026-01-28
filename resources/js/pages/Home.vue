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
          class="w-full border border-gray-300 dark:border-gray-600 rounded-lg p-4 mb-2 focus:ring-blue-500 focus:border-blue-500 transition bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400"
          rows="3"
          :placeholder="`What's on your mind, ${authStore.user?.name}?`"
        ></textarea>
        
        <!-- Image Preview Grid -->
        <div v-if="selectedImages.length > 0" class="mb-4">
          <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2">
            <div
              v-for="(image, index) in selectedImages"
              :key="index"
              class="relative aspect-square rounded-lg overflow-hidden group"
            >
              <img
                :src="image.preview"
                :alt="`Preview ${index + 1}`"
                class="w-full h-full object-cover"
              />
              <button
                type="button"
                @click="removeImage(index)"
                class="absolute top-1 right-1 p-1.5 rounded-full bg-black/70 text-white opacity-0 group-hover:opacity-100 transition-opacity hover:bg-red-600"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
          </div>
        </div>

        <!-- Actions Row -->
        <div class="flex items-center justify-between border-t border-gray-200 dark:border-gray-600 pt-3">
          <!-- Left: Attachment Button -->
          <div class="flex items-center gap-2">
            <input
              type="file"
              ref="fileInput"
              @change="handleFileSelect"
              accept="image/jpeg,image/png,image/webp,image/jpg"
              multiple
              class="hidden"
            />
            <button
              type="button"
              @click="$refs.fileInput.click()"
              class="flex items-center gap-2 px-3 py-2 rounded-lg text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition"
              title="Add photos"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
              <span class="text-sm font-medium">Photo</span>
            </button>
          </div>

          <!-- Right: Post Button -->
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
const isCreating = ref(false);
const selectedImages = ref([]);
const fileInput = ref(null);
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

// --- 3. BEHAVIOR: Handle Image Selection ---
const handleFileSelect = (event) => {
  const files = Array.from(event.target.files);
  
  files.forEach(file => {
    // Validate file type
    if (!file.type.match(/image\/(jpeg|jpg|png|webp)/)) {
      notificationStore.showNotification({
        message: `Invalid file type: ${file.name}. Only JPG, PNG, and WebP are allowed.`,
        type: 'error'
      });
      return;
    }
    
    // Validate file size (max 2MB)
    if (file.size > 2 * 1024 * 1024) {
      notificationStore.showNotification({
        message: `File too large: ${file.name}. Maximum size is 2MB.`,
        type: 'error'
      });
      return;
    }
    
    // Create preview URL and add to selected images
    const preview = URL.createObjectURL(file);
    selectedImages.value.push({
      file,
      preview
    });
  });
  
  // Reset file input
  event.target.value = '';
};

// --- 4. BEHAVIOR: Remove Selected Image ---
const removeImage = (index) => {
  // Revoke the object URL to free memory
  URL.revokeObjectURL(selectedImages.value[index].preview);
  selectedImages.value.splice(index, 1);
};

// --- 5. BEHAVIOR: Create a New Post ---
const handleCreatePost = async () => {
  postError.value = '';
  if (newPostContent.value.trim() === '') {
    postError.value = 'Post content cannot be empty.';
    return;
  }

  isCreating.value = true;
  
  try {
    // Create FormData for multipart upload
    const formData = new FormData();
    formData.append('content', newPostContent.value);
    
    // Append first selected image (backend currently supports single image)
    if (selectedImages.value.length > 0) {
      formData.append('image', selectedImages.value[0].file);
    }
    
    const response = await api.post('/posts', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    });
    
    // Clear form
    newPostContent.value = '';
    selectedImages.value.forEach(image => URL.revokeObjectURL(image.preview));
    selectedImages.value = [];
    
    notificationStore.showNotification({ message: response.data.message });
    await fetchPosts(); // Refetch posts to show the new one
  } catch (error) {
    if (error.response && error.response.data.errors) {
      const errors = error.response.data.errors;
      // Handle content errors
      if (errors.content) {
        postError.value = errors.content[0];
      } 
      // Handle image errors
      else if (errors.image) {
        postError.value = errors.image[0];
      } else {
        postError.value = 'An error occurred while creating the post.';
      }
    } else {
      postError.value = 'An error occurred while creating the post.';
      console.error(error);
    }
  } finally {
    isCreating.value = false;
  }
};

// --- 6. BEHAVIOR: Delete a Post ---
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
