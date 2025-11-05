<template>
  <div class="bg-white p-6 rounded-lg shadow-md" @dblclick="navigateToPost">
    <div class="flex items-start">
      <router-link :to="`/users/${post.user.id}`" class="shrink-0">
        <img v-if="post.user.avatar" :src="`/storage/${post.user.avatar}`" alt="User Avatar" class="w-12 h-12 rounded-full object-cover">
        <div v-else class="w-12 h-12 rounded-full bg-gray-300 flex items-center justify-center font-bold text-gray-600">
          {{ post.user.name.substring(0, 1) }}
        </div>
      </router-link>
      <div class="ml-4 flex-grow">
        <div class="flex justify-between items-center">
          <div>
            <router-link :to="`/users/${post.user.id}`" class="font-bold text-gray-800 hover:underline">{{ post.user.name }}</router-link>
            <span class="text-gray-500 text-sm"> · {{ formatTimestamp(post.created_at) }}</span>
          </div>
          <div v-if="authStore.user && authStore.user.id === post.user_id" class="relative">
            <router-link :to="`/posts/${post.id}/edit`" class="text-blue-500 text-sm hover:underline mr-2">Edit</router-link>
            <button @click.stop="$emit('delete-post', post.id)" class="text-red-500 text-sm hover:underline">Delete</button>
          </div>
        </div>
        <p class="mt-2 text-gray-700 whitespace-pre-wrap">{{ post.content }}</p>
        <div class="mt-4">
          <button @click.stop="toggleComments" class="flex items-center text-gray-500 hover:text-blue-600 transition">
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
      <!-- Create Comment Form -->
      <div class="mb-6">
        <h3 class="text-lg font-semibold mb-4">Leave a Comment</h3>
        <div v-if="commentError" class="p-3 mb-4 text-sm text-red-700 bg-red-100 rounded-lg">
          {{ commentError }}
        </div>
        <form @submit.prevent="handleCreateComment">
          <textarea
            v-model="newCommentContent"
            class="w-full border border-gray-300 rounded-lg p-4 focus:ring-blue-500 focus:border-blue-500 transition"
            rows="3"
            placeholder="Write your comment..."
          ></textarea>
          <div class="flex justify-end mt-4">
            <button
              type="submit"
              class="bg-blue-600 text-white font-bold py-2 px-6 rounded-lg hover:bg-blue-700 transition"
            >
              Submit
            </button>
          </div>
        </form>
      </div>

      <!-- Comments List -->
      <h3 class="text-lg font-semibold mb-4">Comments</h3>
      <div v-if="comments.length > 0" class="space-y-4">
        <CommentItem
          v-for="comment in comments"
          :key="comment.id"
          :comment="comment"
          @delete-comment="handleDeleteComment"
          @update-comment="handleUpdateComment"
        />
      </div>
      <div v-else>
        <p class="text-gray-500">No comments yet. Be the first to comment!</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../store';
import api from '../../services/api';
import CommentItem from '../comments/CommentItem.vue';

const props = defineProps({
  post: {
    type: Object,
    required: true,
  },
});

const emit = defineEmits(['delete-post']);

const authStore = useAuthStore();
const router = useRouter();

const showComments = ref(false);
const comments = ref([]);
const newCommentContent = ref('');
const commentError = ref('');

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

const handleCreateComment = async () => {
  commentError.value = '';
  if (newCommentContent.value.trim() === '') {
    commentError.value = 'Comment cannot be empty.';
    return;
  }

  try {
    await api.post(`/posts/${props.post.id}/comments`, {
      content: newCommentContent.value,
    });
    newCommentContent.value = '';
    await fetchComments(); // Refetch comments to show the new one
    emit('comment-added'); // Notify parent to update count
  } catch (error) {
    if (error.response && error.response.data.errors) {
      commentError.value = error.response.data.errors.content[0];
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
    emit('comment-deleted'); // Notify parent to update count
  } catch (error) {
    console.error('Error deleting comment:', error);
  }
};

const handleUpdateComment = async (commentData) => {
  try {
    await api.patch(`/comments/${commentData.id}`, {
      content: commentData.content,
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
