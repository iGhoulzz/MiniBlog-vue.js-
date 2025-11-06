<template>
  <div class="space-y-6">
    <div v-if="post" class="bg-white p-6 rounded-lg shadow-md">
      <!-- Post Details -->
      <div class="flex items-start">
        <div class="flex-shrink-0">
          <div class="w-12 h-12 rounded-full bg-gray-300 flex items-center justify-center font-bold text-gray-600">
            {{ post.user.name.substring(0, 1) }}
          </div>
        </div>
        <div class="ml-4 flex-grow">
          <div>
            <span class="font-bold text-gray-800">{{ post.user.name }}</span>
            <span class="text-gray-500 text-sm"> · {{ formatTimestamp(post.created_at) }}</span>
          </div>
          <p class="mt-2 text-gray-700 whitespace-pre-wrap">{{ post.content }}</p>
        </div>
      </div>

      <!-- Create Comment Form -->
      <div class="mt-6 border-t pt-6">
        <h3 class="text-lg font-semibold mb-4">Leave a Comment</h3>
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
      <div class="mt-6 border-t pt-6">
        <h3 class="text-lg font-semibold mb-4">Comments</h3>
        <div v-if="post.comments && post.comments.length > 0" class="space-y-4">
          <CommentItem
            v-for="comment in post.comments"
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
    <div v-else class="text-center">
      <p class="text-gray-500">Loading post...</p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { useNotificationStore } from '../stores/notification.js';
import api from '../services/api.js';
import CommentItem from '../components/Comments/CommentItem.vue';

const route = useRoute();
const notificationStore = useNotificationStore();
const post = ref(null);
const newCommentContent = ref('');

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

const handleCreateComment = async () => {
  if (newCommentContent.value.trim() === '') {
    notificationStore.showNotification({
      message: 'Comment cannot be empty.',
      type: 'error'
    });
    return;
  }

  try {
    const postId = route.params.id;
    await api.post(`/posts/${postId}/comments`, {
      content: newCommentContent.value,
    });
    newCommentContent.value = '';
    notificationStore.showNotification({
      message: 'Comment posted successfully!',
      type: 'success'
    });
    await fetchPost(); // Refetch post to show the new comment
  } catch (error) {
    if (error.response && error.response.data.errors) {
      notificationStore.showNotification({
        message: error.response.data.errors.content?.[0] || 'An error occurred while posting the comment.',
        type: 'error'
      });
    } else {
      notificationStore.showNotification({
        message: 'An error occurred while posting the comment.',
        type: 'error'
      });
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
    await fetchPost(); // Refetch post to remove the deleted comment
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
    await api.patch(`/comments/${commentData.id}`, {
      content: commentData.content,
    });
    notificationStore.showNotification({
      message: 'Comment updated successfully!',
      type: 'success'
    });
    await fetchPost(); // Refetch post to show the updated comment
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
