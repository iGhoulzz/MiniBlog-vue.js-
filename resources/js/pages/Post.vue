<template>
  <div class="space-y-6">
    <div v-if="post" class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md transition-colors duration-300">
      <!-- Post Details -->
      <div class="flex items-start">
        <!-- Clickable Avatar -->
        <router-link :to="`/users/${post.user.id}`" class="flex-shrink-0 cursor-pointer hover:opacity-80 transition-opacity">
          <img v-if="post.user.avatar_url" :src="post.user.avatar_url" alt="User Avatar" class="w-12 h-12 rounded-full object-cover border-2 border-gray-200 dark:border-gray-600">
          <div v-else class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center font-bold text-white border-2 border-gray-200 dark:border-gray-600">
            {{ post.user.name.substring(0, 1).toUpperCase() }}
          </div>
        </router-link>
        <div class="ml-4 flex-grow">
          <div>
            <!-- Clickable Username -->
            <router-link :to="`/users/${post.user.id}`" class="font-bold text-gray-800 dark:text-gray-100 hover:text-blue-600 dark:hover:text-blue-400 hover:underline transition-colors">
              {{ post.user.name }}
            </router-link>
            <span class="text-gray-500 dark:text-gray-400 text-sm"> · {{ formatTimestamp(post.created_at) }}</span>
          </div>
          <p class="mt-2 text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ post.content }}</p>
          
          <!-- Post Images -->
          <div v-if="postImages.length > 0" class="mt-4">
            <ImageGallery :images="postImages" />
          </div>
        </div>
      </div>

      <!-- Comments List -->
      <div class="mt-6 border-t border-gray-200 dark:border-gray-700 pt-6">
        <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-gray-100">Comments ({{ post.comments?.length || 0 }})</h3>
        <div v-if="post.comments && post.comments.length > 0" class="space-y-3 ml-4 pl-4 border-l-2 border-gray-300 dark:border-gray-600">
          <CommentItem
            v-for="comment in post.comments"
            :key="comment.id"
            :comment="comment"
            @delete-comment="handleDeleteComment"
            @update-comment="handleUpdateComment"
          />
        </div>
        <div v-else class="ml-4 pl-4 border-l-2 border-gray-200 dark:border-gray-700">
          <p class="text-gray-500 dark:text-gray-400 text-sm italic">No comments yet. Be the first to comment!</p>
        </div>
      </div>

      <!-- Create Comment Form - At Bottom -->
      <div class="mt-4 pt-4">
        <div v-if="commentError" class="p-3 mb-3 text-sm text-red-700 bg-red-100 dark:bg-red-900 dark:text-red-200 rounded-lg">
          {{ commentError }}
        </div>
        <form @submit.prevent="handleCreateComment" class="flex flex-col gap-2">
          <div class="flex gap-2">
            <textarea
              v-model="newCommentContent"
              class="flex-1 border border-gray-300 dark:border-gray-600 rounded-lg p-2 focus:ring-blue-500 focus:border-blue-500 transition text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 resize-none"
              rows="2"
              placeholder="Write a comment..."
            ></textarea>
          </div>
          
          <!-- Comment Image Preview -->
          <div v-if="selectedCommentImage" class="flex items-start gap-2">
            <div class="relative inline-block">
              <img
                :src="selectedCommentImage.preview"
                alt="Preview"
                class="max-w-[120px] max-h-20 rounded border border-gray-200 dark:border-gray-600"
              />
              <button
                type="button"
                @click="removeCommentImage"
                class="absolute -top-1 -right-1 p-0.5 rounded-full bg-red-500 text-white hover:bg-red-600 transition"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
          </div>
          
          <!-- Actions Row -->
          <div class="flex items-center justify-between">
            <!-- Left: Photo Button -->
            <div>
              <input
                type="file"
                ref="commentFileInput"
                @change="handleCommentFileSelect"
                accept="image/jpeg,image/png,image/webp,image/jpg"
                class="hidden"
              />
              <button
                type="button"
                @click="$refs.commentFileInput.click()"
                class="flex items-center gap-1 p-1.5 rounded-lg text-gray-400 hover:text-green-600 hover:bg-gray-100 dark:hover:bg-gray-600 transition"
                title="Add photo"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
              </button>
            </div>
            
            <!-- Right: Submit Button -->
            <button
              type="submit"
              class="bg-blue-600 text-white font-medium py-1.5 px-4 rounded-lg hover:bg-blue-700 transition text-sm"
            >
              Comment
            </button>
          </div>
        </form>
      </div>
    </div>
    <div v-else class="text-center">
      <p class="text-gray-500 dark:text-gray-400">Loading post...</p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { useNotificationStore } from '../stores/notification.js';
import api from '../services/api.js';
import CommentItem from '../components/Comments/CommentItem.vue';
import ImageGallery from '../components/UI/ImageGallery.vue';

const route = useRoute();
const notificationStore = useNotificationStore();
const post = ref(null);
const newCommentContent = ref('');
const commentError = ref('');
const selectedCommentImage = ref(null);
const commentFileInput = ref(null);

// Computed property to get all post images
const postImages = computed(() => {
  if (!post.value) return [];
  // Handle multiple images if media_urls array exists
  if (post.value.media_urls && post.value.media_urls.length > 0) {
    return post.value.media_urls;
  }
  // Fallback to single image_url
  if (post.value.image_url) {
    return [post.value.image_url];
  }
  return [];
});

const fetchPost = async () => {
  try {
    const postId = route.params.id;
    const response = await api.get(`/posts/${postId}`);
    post.value = response.data;
  } catch (error) {
    notificationStore.showNotification({
      message: 'Error loading post. Please try again.',
      type: 'error'
    });
    console.error('Error fetching post:', error);
  }
};

// Comment Image Handling
const handleCommentFileSelect = (event) => {
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
  
  // Clear previous selection
  if (selectedCommentImage.value) {
    URL.revokeObjectURL(selectedCommentImage.value.preview);
  }
  
  selectedCommentImage.value = {
    file,
    preview: URL.createObjectURL(file)
  };
  
  event.target.value = '';
};

const removeCommentImage = () => {
  if (selectedCommentImage.value) {
    URL.revokeObjectURL(selectedCommentImage.value.preview);
    selectedCommentImage.value = null;
  }
};

const handleCreateComment = async () => {
  commentError.value = '';
  if (newCommentContent.value.trim() === '') {
    commentError.value = 'Comment cannot be empty.';
    return;
  }

  try {
    const postId = route.params.id;
    
    // Create FormData for multipart upload
    const formData = new FormData();
    formData.append('content', newCommentContent.value);
    
    if (selectedCommentImage.value) {
      formData.append('image', selectedCommentImage.value.file);
    }
    
    await api.post(`/posts/${postId}/comments`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    });
    
    // Clear form
    newCommentContent.value = '';
    removeCommentImage();
    
    notificationStore.showNotification({
      message: 'Comment posted successfully!',
      type: 'success'
    });
    await fetchPost();
  } catch (error) {
    if (error.response && error.response.data.errors) {
      const errors = error.response.data.errors;
      if (errors.content) {
        commentError.value = errors.content[0];
      } else if (errors.image) {
        commentError.value = errors.image[0];
      } else {
        commentError.value = 'An error occurred while posting the comment.';
      }
    } else {
      commentError.value = 'An error occurred while posting the comment.';
      console.error(error);
    }
  }
};

const handleDeleteComment = async (commentId) => {
  if (!confirm('Are you sure you want to delete this comment?')) {
    return;
  }

  try {
    await api.delete(`/comments/${commentId}`);
    notificationStore.showNotification({
      message: 'Comment deleted successfully!',
      type: 'success'
    });
    await fetchPost();
  } catch (error) {
    notificationStore.showNotification({
      message: 'Error deleting comment. Please try again.',
      type: 'error'
    });
    console.error('Error deleting comment:', error);
  }
};

const handleUpdateComment = async (commentData) => {
  try {
    // Create FormData for multipart upload
    const formData = new FormData();
    formData.append('content', commentData.content);
    formData.append('_method', 'PATCH');
    
    if (commentData.image) {
      formData.append('image', commentData.image);
    } else if (commentData.remove_image) {
      formData.append('remove_image', '1');
    }
    
    await api.post(`/comments/${commentData.id}`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    });
    notificationStore.showNotification({
      message: 'Comment updated successfully!',
      type: 'success'
    });
    await fetchPost();
  } catch (error) {
    notificationStore.showNotification({
      message: 'Error updating comment. Please try again.',
      type: 'error'
    });
    console.error('Error updating comment:', error);
  }
};

const formatTimestamp = (timestamp) => {
  return new Date(timestamp).toLocaleDateString("en-US", {
    year: 'numeric', month: 'short', day: 'numeric'
  });
};

onMounted(fetchPost);
</script>
