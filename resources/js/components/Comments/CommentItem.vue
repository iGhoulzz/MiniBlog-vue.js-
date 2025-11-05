<template>
  <div class="bg-white p-6 rounded-lg shadow-md">
    <div v-if="!isEditing">
      <div class="flex items-start">
        <div class="flex-shrink-0">
          <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center font-bold text-gray-500">
            {{ comment.user.name.substring(0, 1) }}
          </div>
        </div>
        <div class="ml-4 flex-grow">
          <div class="flex justify-between items-center">
            <div>
              <span class="font-bold text-gray-800">{{ comment.user.name }}</span>
              <span class="text-gray-500 text-sm"> · {{ formatTimestamp(comment.created_at) }}</span>
            </div>
            <div v-if="authStore.user && authStore.user.id === comment.user_id" class="flex items-center">
              <button @click="isEditing = true" class="text-blue-500 text-sm hover:underline mr-2">Edit</button>
              <button @click="$emit('delete-comment', comment.id)" class="text-red-500 text-sm hover:underline">Delete</button>
            </div>
          </div>
          <p class="mt-1 text-gray-700">{{ comment.content }}</p>
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
import { useAuthStore } from '../../store';
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
