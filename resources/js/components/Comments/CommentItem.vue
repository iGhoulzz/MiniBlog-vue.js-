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
            <div v-if="authStore.user && authStore.user.id === comment.user_id" class="flex items-center gap-2">
              <button
                type="button"
                class="inline-flex items-center gap-1.5 rounded-full border border-blue-200 bg-blue-50 px-2.5 py-1 text-[11px] font-semibold text-blue-600 transition hover:border-blue-300 hover:bg-blue-100 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-200"
                @click="isEditing = true"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m16.862 3.487 3.651 3.651m-9.546 3.178 3.652 3.651m-7.547 2.717 4.16-1.04a2 2 0 0 0 .975-.544l7.3-7.3a1 1 0 0 0 0-1.414l-3.652-3.651a1 1 0 0 0-1.414 0l-7.3 7.3a2 2 0 0 0-.544.975l-1.04 4.16a1 1 0 0 0 1.214 1.214Z" />
                </svg>
                Edit
              </button>
              <button
                type="button"
                class="inline-flex items-center gap-1.5 rounded-full border border-rose-200 bg-rose-50 px-2.5 py-1 text-[11px] font-semibold text-rose-600 transition hover:border-rose-300 hover:bg-rose-100 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-rose-200"
                @click="$emit('delete-comment', comment.id)"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 3.75h6m4.5 3h-15m12 0-.8 11.2a2 2 0 0 1-2 1.8H9.3a2 2 0 0 1-2-1.8L6.5 6.75m3 4.5v6m4-6v6" />
                </svg>
                Delete
              </button>
            </div>
          </div>
          <p class="mt-1 text-gray-700 dark:text-gray-300 text-sm">{{ comment.content }}</p>
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
import { ref } from 'vue';
import { useAuthStore } from '../../stores/auth';
import EditCommentForm from './EditCommentForm.vue';

const authStore = useAuthStore();
const isEditing = ref(false);

defineProps({
  comment: {
    type: Object,
    required: true,
  },
});

defineEmits(['delete-comment', 'update-comment']);

const formatTimestamp = (timestamp) => {
  return new Date(timestamp).toLocaleDateString("en-US", {
    year: 'numeric', month: 'short', day: 'numeric'
  });
};
</script>
