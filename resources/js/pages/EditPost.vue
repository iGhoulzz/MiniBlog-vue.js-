<template>
  <BaseCard class="p-8 max-w-2xl mx-auto">
    <h1 class="text-2xl font-bold mb-6 text-gray-800 dark:text-white">Edit Post</h1>

    <div v-if="errorMessage" class="p-3 mb-4 text-sm text-red-700 bg-red-100 dark:bg-red-900 dark:text-red-200 rounded-lg">
      {{ errorMessage }}
    </div>

    <form v-if="post" @submit.prevent="handleUpdatePost">
      <textarea
        v-model="post.content"
        class="w-full border border-gray-300 dark:border-gray-600 rounded-lg p-4 mb-2 focus:ring-blue-500 focus:border-blue-500 transition bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
        rows="6"
      ></textarea>

      <!-- Existing Image -->
      <div v-if="post.image_url && !removeExistingImage" class="mb-4">
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Current image:</p>
        <div class="relative inline-block">
          <img
            :src="post.image_url"
            alt="Current post image"
            class="max-w-xs rounded-lg border border-gray-200 dark:border-gray-600"
          />
          <button
            type="button"
            @click="removeExistingImage = true"
            class="absolute top-2 right-2 p-1.5 rounded-full bg-black/70 text-white hover:bg-red-600 transition"
            title="Remove image"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>

      <!-- New Image Preview -->
      <div v-if="selectedImages.length > 0" class="mb-4">
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">New image:</p>
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
              @click="removeNewImage(index)"
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
      <div class="flex items-center justify-between border-t border-gray-200 dark:border-gray-600 pt-4 mt-4">
        <!-- Left: Add Photo Button -->
        <div class="flex items-center gap-2">
          <input
            type="file"
            ref="fileInput"
            @change="handleFileSelect"
            accept="image/jpeg,image/png,image/webp,image/jpg"
            class="hidden"
          />
          <button
            type="button"
            @click="$refs.fileInput.click()"
            class="flex items-center gap-2 px-3 py-2 rounded-lg text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition"
            title="Add photo"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <span class="text-sm font-medium">Change Photo</span>
          </button>
        </div>

        <!-- Right: Buttons -->
        <div class="flex items-center gap-3">
          <router-link to="/" class="text-gray-600 dark:text-gray-400 font-bold py-2 px-6 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition">
            Cancel
          </router-link>
          <BaseButton type="submit" :disabled="isUpdating">
            <span v-if="isUpdating">Updating...</span>
            <span v-else>Update Post</span>
          </BaseButton>
        </div>
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
const isUpdating = ref(false);
const removeExistingImage = ref(false);
const selectedImages = ref([]);
const fileInput = ref(null);
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

const handleFileSelect = (event) => {
  const file = event.target.files[0];
  if (!file) return;
  
  // Validate file type
  if (!file.type.match(/image\/(jpeg|jpg|png|webp)/)) {
    notificationStore.showNotification({
      message: 'Invalid file type. Only JPG, PNG, and WebP are allowed.',
      type: 'error'
    });
    return;
  }
  
  // Validate file size (max 2MB)
  if (file.size > 2 * 1024 * 1024) {
    notificationStore.showNotification({
      message: 'File too large. Maximum size is 2MB.',
      type: 'error'
    });
    return;
  }
  
  // Clear previous selections and add new
  selectedImages.value.forEach(img => URL.revokeObjectURL(img.preview));
  selectedImages.value = [{
    file,
    preview: URL.createObjectURL(file)
  }];
  
  // Mark existing image for removal since we're replacing it
  removeExistingImage.value = true;
  
  // Reset file input
  event.target.value = '';
};

const removeNewImage = (index) => {
  URL.revokeObjectURL(selectedImages.value[index].preview);
  selectedImages.value.splice(index, 1);
  
  // If we removed the new image and had an existing image, restore it
  if (selectedImages.value.length === 0 && post.value.image_url) {
    removeExistingImage.value = false;
  }
};

const handleUpdatePost = async () => {
  errorMessage.value = '';
  if (!post.value || post.value.content.trim() === '') {
    errorMessage.value = 'Post content cannot be empty.';
    return;
  }

  isUpdating.value = true;
  
  try {
    const postId = route.params.id;
    
    // Create FormData for multipart upload
    const formData = new FormData();
    formData.append('content', post.value.content);
    formData.append('_method', 'PATCH'); // Laravel method spoofing
    
    // Add new image if selected
    if (selectedImages.value.length > 0) {
      formData.append('image', selectedImages.value[0].file);
    }
    
    // Mark for removal if user explicitly removed the image
    if (removeExistingImage.value && selectedImages.value.length === 0) {
      formData.append('remove_image', '1');
    }
    
    const response = await api.post(`/posts/${postId}`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    });
    
    // Cleanup
    selectedImages.value.forEach(img => URL.revokeObjectURL(img.preview));
    
    notificationStore.showNotification({ message: response.data.message });
    router.push('/');
  } catch (error) {
    if (error.response && error.response.data.errors) {
      const errors = error.response.data.errors;
      if (errors.content) {
        errorMessage.value = errors.content[0];
      } else if (errors.image) {
        errorMessage.value = errors.image[0];
      } else {
        errorMessage.value = 'An error occurred while updating the post.';
      }
    } else {
      errorMessage.value = 'An error occurred while updating the post.';
      console.error(error);
    }
  } finally {
    isUpdating.value = false;
  }
};
</script>
