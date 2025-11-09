<template>
  <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 shadow-sm">
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
              <router-link :to="`/users/${comment.user.id}`" class="font-semibold text-gray-800 text-sm hover:text-blue-600 hover:underline transition-colors">
                {{ comment.user.name }}
              </router-link>
              <span class="text-gray-500 text-xs"> · {{ formatTimestamp(comment.created_at) }}</span>
            </div>
            <div v-if="authStore.user && authStore.user.id === comment.user_id" class="flex items-center">
              <button @click="isEditing = true" class="text-blue-500 text-xs hover:underline mr-2">Edit</button>
              <button @click="$emit('delete-comment', comment.id)" class="text-red-500 text-xs hover:underline">Delete</button>
            </div>
          </div>
          <p class="mt-1 text-gray-700 text-sm">{{ comment.content }}</p>
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
