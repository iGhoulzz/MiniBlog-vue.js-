<template>
  <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md transition-colors duration-300" @dblclick="navigateToPost">
    <div class="flex items-start">
      <router-link :to="`/users/${post.user.id}`" class="shrink-0">
        <div class="flex-shrink-0">
          <img
            v-if="post.user?.avatar_url"
            :src="post.user.avatar_url"
            alt="Avatar"
            class="w-10 h-10 rounded-full object-cover"
          >
          <div v-else class="w-10 h-10 rounded-full bg-gray-300 flex items-center justify-center font-bold text-gray-600">
            {{ post.user?.name?.substring(0, 1) }}
          </div>
        </div>
      </router-link>
      <div class="ml-4 flex-grow">
        <div class="flex justify-between items-center">
          <div>
            <router-link :to="`/users/${post.user.id}`" class="font-bold text-gray-800 dark:text-gray-100 hover:underline">{{ post.user.name }}</router-link>
            <span class="text-gray-500 dark:text-gray-400 text-sm"> · {{ formatTimestamp(post.created_at) }}</span>
          </div>
          <div v-if="authStore.user && authStore.user.id === post.user_id" class="flex items-center gap-1">
            <!-- Edit Button - Icon only -->
            <router-link
              :to="`/posts/${post.id}/edit`"
              class="p-2 rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 transition-all"
              @click.stop
              title="Edit post"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
            </router-link>
            <!-- Delete Button - Icon only -->
            <button
              type="button"
              class="p-2 rounded-lg text-gray-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/30 transition-all"
              @click.stop="$emit('delete-post', post.id)"
              title="Delete post"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
            </button>
          </div>
        </div>
        <p 
          @click="navigateToPost" 
          class="mt-2 text-gray-700 dark:text-gray-300 whitespace-pre-wrap cursor-pointer hover:text-blue-600 dark:hover:text-blue-400 transition"
        >{{ post.content }}</p>
        
        <!-- Post Images -->
        <div v-if="postImages.length > 0" class="mt-3" @click.stop>
          <ImageGallery :images="postImages" />
        </div>
        
        <div class="mt-4 flex items-center gap-4">
          <!-- Reactions -->
          <ReactionButton
            reactionable-type="post"
            :reactionable-id="post.id"
            :user-reaction="post.user_reaction"
            :reactions-count="post.reactions_count || 0"
            :reactions-summary="post.reactions_summary || {}"
          />

          <!-- Comments Button -->
          <button @click.stop="toggleComments" class="flex items-center text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>
            <span class="text-sm font-semibold">{{ post.comments_count }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Inline Comments Section -->
    <div v-if="showComments" class="mt-6 border-t pt-6">
      <!-- Comments List -->
      <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-gray-100">Comments ({{ comments.length }})</h3>
      <div v-if="comments.length > 0" class="space-y-3 ml-4 pl-4 border-l-2 border-gray-300 dark:border-gray-600">
        <CommentItem
          v-for="comment in comments"
          :key="comment.id"
          :comment="comment"
          @delete-comment="handleDeleteComment"
          @update-comment="handleUpdateComment"
        />
      </div>
      <div v-else class="ml-4 pl-4 border-l-2 border-gray-200 dark:border-gray-700">
        <p class="text-gray-500 dark:text-gray-400 text-sm italic">No comments yet. Be the first to comment!</p>
      </div>

      <!-- Create Comment Form - At Bottom -->
      <div class="mt-4 pt-4">
        <div v-if="commentError" class="p-3 mb-3 text-sm text-red-700 bg-red-100 dark:bg-red-900 dark:text-red-200 rounded-lg">
          {{ commentError }}
        </div>
        <form @submit.prevent="handleCreateComment" class="flex flex-col gap-2">
          <textarea
            v-model="newCommentContent"
            class="w-full border border-gray-300 dark:border-gray-600 rounded-lg p-2 focus:ring-blue-500 focus:border-blue-500 transition text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 resize-none"
            rows="2"
            placeholder="Write a comment..."
          ></textarea>
          
          <!-- Comment Image Preview -->
          <div v-if="selectedCommentImage" class="flex items-start">
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
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import { useNotificationStore } from '../../stores/notification';
import api from '../../services/api';
import CommentItem from '../Comments/CommentItem.vue';
import ReactionButton from '../UI/ReactionButton.vue';
import ImageGallery from '../UI/ImageGallery.vue';

const props = defineProps({
  post: {
    type: Object,
    required: true,
  },
});

const emit = defineEmits(['delete-post', 'comment-added', 'comment-deleted']);

const authStore = useAuthStore();
const notificationStore = useNotificationStore();
const router = useRouter();

const showComments = ref(false);
const comments = ref([]);
const newCommentContent = ref('');
const commentError = ref('');
const selectedCommentImage = ref(null);
const commentFileInput = ref(null);

// Computed property to get all post images
const postImages = computed(() => {
  // Handle multiple images if media_urls array exists
  if (props.post.media_urls && props.post.media_urls.length > 0) {
    return props.post.media_urls;
  }
  // Fallback to single image_url
  if (props.post.image_url) {
    return [props.post.image_url];
  }
  return [];
});

const navigateToPost = () => {
  router.push(`/posts/${props.post.id}`);
};

const fetchComments = async () => {
  try {
    const response = await api.get(`/posts/${props.post.id}`);
    comments.value = response.data.comments;
  } catch (error) {
    console.error('Error fetching comments:', error);
  }
};

const toggleComments = async () => {
  showComments.value = !showComments.value;
  if (showComments.value && comments.value.length === 0) {
    await fetchComments();
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
    // Create FormData for multipart upload
    const formData = new FormData();
    formData.append('content', newCommentContent.value);
    
    if (selectedCommentImage.value) {
      formData.append('image', selectedCommentImage.value.file);
    }
    
    await api.post(`/posts/${props.post.id}/comments`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    });
    
    // Clear form
    newCommentContent.value = '';
    removeCommentImage();
    
    await fetchComments(); // Refetch comments to show the new one
    emit('comment-added', props.post.id); // Notify parent to update count
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
    await fetchComments(); // Refetch comments
    emit('comment-deleted', props.post.id); // Notify parent to update count
  } catch (error) {
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
    await fetchComments(); // Refetch comments
  } catch (error) {
    console.error('Error updating comment:', error);
  }
};

const formatTimestamp = (timestamp) => {
  return new Date(timestamp).toLocaleDateString("en-US", {
    year: 'numeric', month: 'short', day: 'numeric'
  });
};
</script>
