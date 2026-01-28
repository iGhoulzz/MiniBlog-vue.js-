<template>
  <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg border border-gray-200 dark:border-gray-600 shadow-sm transition-colors duration-300">
    <div v-if="!isEditing">
      <div class="flex items-start">
        <!-- Clickable Avatar -->
        <router-link :to="`/users/${comment.user.id}`" class="flex-shrink-0 cursor-pointer hover:opacity-80 transition-opacity">
          <img
            v-if="comment.user?.avatar_url"
            :src="comment.user.avatar_url"
            alt="Avatar"
            class="w-8 h-8 rounded-full object-cover border border-gray-300"
          >
          <div v-else class="w-8 h-8 rounded-full bg-gray-300 flex items-center justify-center font-bold text-gray-600 text-sm">
            {{ comment.user?.name?.substring(0, 1) }}
          </div>
        </router-link>
        <div class="ml-3 flex-grow">
          <div class="flex justify-between items-center">
            <div>
              <!-- Clickable Username -->
              <router-link :to="`/users/${comment.user.id}`" class="font-semibold text-gray-800 dark:text-gray-100 text-sm hover:text-blue-600 dark:hover:text-blue-400 hover:underline transition-colors">
                {{ comment.user.name }}
              </router-link>
              <span class="text-gray-500 dark:text-gray-400 text-xs"> · {{ formatTimestamp(comment.created_at) }}</span>
            </div>
            <div v-if="authStore.user && authStore.user.id === comment.user_id" class="flex items-center gap-1">
              <!-- Edit Button - Icon only -->
              <button
                type="button"
                class="p-1.5 rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 transition-all"
                @click="isEditing = true"
                title="Edit comment"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
              </button>
              <!-- Delete Button - Icon only -->
              <button
                type="button"
                class="p-1.5 rounded-lg text-gray-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/30 transition-all"
                @click="$emit('delete-comment', comment.id)"
                title="Delete comment"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
              </button>
            </div>
          </div>
          <p class="mt-1 text-gray-700 dark:text-gray-300 text-sm">{{ comment.content }}</p>
          
          <!-- Comment Image - Smaller thumbnail -->
          <div v-if="commentImages.length > 0" class="mt-2">
            <a 
              v-for="(image, idx) in commentImages" 
              :key="idx"
              :href="image" 
              target="_blank"
              class="inline-block"
            >
              <img 
                :src="image" 
                :alt="`Comment image ${idx + 1}`"
                class="max-w-[200px] max-h-[150px] rounded-lg border border-gray-200 dark:border-gray-600 object-cover cursor-pointer hover:opacity-90 transition"
              />
            </a>
          </div>
          
          <!-- Reactions -->
          <div class="mt-2">
            <ReactionButton
              reactionable-type="comment"
              :reactionable-id="comment.id"
              :user-reaction="comment.user_reaction"
              :reactions-count="comment.reactions_count || 0"
              :reactions-summary="comment.reactions_summary || {}"
            />
          </div>
        </div>
      </div>
    </div>
    <div v-else>
      <EditCommentForm
        :comment="comment"
        @cancel="isEditing = false"
        @save="$emit('update-comment', $event); isEditing = false"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useAuthStore } from '../../stores/auth';
import EditCommentForm from './EditCommentForm.vue';
import ReactionButton from '../UI/ReactionButton.vue';

const authStore = useAuthStore();
const isEditing = ref(false);

const props = defineProps({
  comment: {
    type: Object,
    required: true,
  },
});

defineEmits(['delete-comment', 'update-comment']);

// Computed property to get comment images
const commentImages = computed(() => {
  // Handle multiple images if media_urls array exists
  if (props.comment.media_urls && props.comment.media_urls.length > 0) {
    return props.comment.media_urls;
  }
  // Fallback to single image_url
  if (props.comment.image_url) {
    return [props.comment.image_url];
  }
  return [];
});

const formatTimestamp = (timestamp) => {
  return new Date(timestamp).toLocaleDateString("en-US", {
    year: 'numeric', month: 'short', day: 'numeric'
  });
};
</script>
