<template>
  <form @submit.prevent="handleSave">
    <textarea
      v-model="editableContent"
      class="w-full border border-gray-300 rounded-lg p-2 focus:ring-blue-500 focus:border-blue-500 transition"
      rows="3"
    ></textarea>
    <div class="flex justify-end mt-2">
      <button type="button" @click="$emit('cancel')" class="text-gray-600 font-bold py-2 px-4 mr-2 rounded-lg hover:bg-gray-100 transition">
        Cancel
      </button>
      <button type="submit" class="bg-blue-600 text-white font-bold py-2 px-4 rounded-lg hover:bg-blue-700 transition">
        Save
      </button>
    </div>
  </form>
</template>

<script setup>
import { ref } from 'vue';

const props = defineProps({
  comment: {
    type: Object,
    required: true,
  },
});

const emit = defineEmits(['cancel', 'save']);

const editableContent = ref(props.comment.content);

const handleSave = () => {
  emit('save', {
    id: props.comment.id,
    content: editableContent.value,
  });
};
</script>
