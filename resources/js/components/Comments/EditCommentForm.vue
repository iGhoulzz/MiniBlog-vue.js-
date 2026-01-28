<template>
  <form @submit.prevent="handleSave">
    <textarea
      v-model="editableContent"
      class="w-full border border-gray-300 dark:border-gray-600 rounded-lg p-2 focus:ring-blue-500 focus:border-blue-500 transition bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
      rows="3"
    ></textarea>
    
    <!-- Existing Image Display -->
    <div v-if="existingImageUrl && !removeExisting" class="mt-3">
      <div class="relative inline-block">
        <img
          :src="existingImageUrl"
          alt="Current image"
          class="max-w-xs max-h-32 rounded-lg border border-gray-200 dark:border-gray-600"
        />
        <button
          type="button"
          @click="removeExisting = true"
          class="absolute top-1 right-1 p-1 rounded-full bg-black/70 text-white hover:bg-red-600 transition"
          title="Remove image"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
    </div>
    
    <!-- New Image Preview -->
    <div v-if="selectedImage" class="mt-3">
      <div class="relative inline-block">
        <img
          :src="selectedImage.preview"
          alt="New image preview"
          class="max-w-xs max-h-32 rounded-lg border border-gray-200 dark:border-gray-600"
        />
        <button
          type="button"
          @click="removeSelectedImage"
          class="absolute top-1 right-1 p-1 rounded-full bg-black/70 text-white hover:bg-red-600 transition"
          title="Remove image"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
    </div>
    
    <!-- Actions Row -->
    <div class="flex items-center justify-between mt-3">
      <!-- Left: Photo Button -->
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
          class="flex items-center gap-1.5 px-2 py-1.5 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-600 transition text-sm"
          title="Add photo"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
          <span>Photo</span>
        </button>
      </div>
      
      <!-- Right: Cancel & Save Buttons -->
      <div class="flex items-center gap-2">
        <button 
          type="button" 
          @click="$emit('cancel')" 
          class="text-gray-600 dark:text-gray-400 font-bold py-2 px-4 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition"
        >
          Cancel
        </button>
        <button 
          type="submit" 
          class="bg-blue-600 text-white font-bold py-2 px-4 rounded-lg hover:bg-blue-700 transition"
        >
          Save
        </button>
      </div>
    </div>
  </form>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
  comment: {
    type: Object,
    required: true,
  },
});

const emit = defineEmits(['cancel', 'save']);

const editableContent = ref(props.comment.content);
const removeExisting = ref(false);
const selectedImage = ref(null);
const fileInput = ref(null);

// Get existing image URL from comment
const existingImageUrl = computed(() => {
  return props.comment.image_url || null;
});

const handleFileSelect = (event) => {
  const file = event.target.files[0];
  if (!file) return;
  
  // Clear previous selection
  if (selectedImage.value) {
    URL.revokeObjectURL(selectedImage.value.preview);
  }
  
  selectedImage.value = {
    file,
    preview: URL.createObjectURL(file)
  };
  
  // Mark existing as removed since we're replacing
  removeExisting.value = true;
  
  event.target.value = '';
};

const removeSelectedImage = () => {
  if (selectedImage.value) {
    URL.revokeObjectURL(selectedImage.value.preview);
    selectedImage.value = null;
  }
};

const handleSave = () => {
  emit('save', {
    id: props.comment.id,
    content: editableContent.value,
    image: selectedImage.value?.file || null,
    remove_image: removeExisting.value && !selectedImage.value
  });
};
</script>
