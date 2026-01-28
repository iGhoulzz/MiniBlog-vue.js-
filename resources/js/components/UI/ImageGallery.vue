<template>
  <!-- Thumbnail Grid -->
  <div v-if="images.length > 0" class="image-gallery">
    <div
      :class="[
        'grid gap-1 rounded-lg overflow-hidden',
        gridClass
      ]"
    >
      <div
        v-for="(image, index) in displayImages"
        :key="index"
        :class="getImageContainerClass(index)"
        class="relative cursor-pointer overflow-hidden group"
        @click="openLightbox(index)"
      >
        <img
          :src="image"
          :alt="`Image ${index + 1}`"
          class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
        />
        <!-- Overlay for remaining images count -->
        <div
          v-if="index === maxDisplay - 1 && remainingCount > 0"
          class="absolute inset-0 bg-black/60 flex items-center justify-center"
        >
          <span class="text-white text-3xl font-bold">+{{ remainingCount }}</span>
        </div>
      </div>
    </div>
  </div>

  <!-- Lightbox Modal -->
  <Teleport to="body">
    <Transition name="fade">
      <div
        v-if="isLightboxOpen"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/90"
        @click.self="closeLightbox"
        @keydown.left="prevImage"
        @keydown.right="nextImage"
        @keydown.escape="closeLightbox"
      >
        <!-- Close Button -->
        <button
          @click="closeLightbox"
          class="absolute top-4 right-4 z-10 p-2 rounded-full bg-white/10 hover:bg-white/20 transition-colors"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>

        <!-- Previous Button -->
        <button
          v-if="images.length > 1"
          @click="prevImage"
          class="absolute left-4 z-10 p-3 rounded-full bg-white/10 hover:bg-white/20 transition-colors"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
        </button>

        <!-- Image -->
        <div class="max-w-[90vw] max-h-[90vh] flex items-center justify-center">
          <Transition :name="slideDirection" mode="out-in">
            <img
              :key="currentIndex"
              :src="images[currentIndex]"
              :alt="`Image ${currentIndex + 1}`"
              class="max-w-full max-h-[90vh] object-contain rounded-lg shadow-2xl"
            />
          </Transition>
        </div>

        <!-- Next Button -->
        <button
          v-if="images.length > 1"
          @click="nextImage"
          class="absolute right-4 z-10 p-3 rounded-full bg-white/10 hover:bg-white/20 transition-colors"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
          </svg>
        </button>

        <!-- Image Counter -->
        <div class="absolute bottom-4 left-1/2 -translate-x-1/2 text-white text-sm bg-black/50 px-4 py-2 rounded-full">
          {{ currentIndex + 1 }} / {{ images.length }}
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';

const props = defineProps({
  images: {
    type: Array,
    default: () => [],
  },
  maxDisplay: {
    type: Number,
    default: 4,
  },
});

const isLightboxOpen = ref(false);
const currentIndex = ref(0);
const slideDirection = ref('slide-right');

const displayImages = computed(() => {
  return props.images.slice(0, props.maxDisplay);
});

const remainingCount = computed(() => {
  return Math.max(0, props.images.length - props.maxDisplay);
});

const gridClass = computed(() => {
  const count = Math.min(props.images.length, props.maxDisplay);
  switch (count) {
    case 1:
      return 'grid-cols-1';
    case 2:
      return 'grid-cols-2';
    case 3:
      return 'grid-cols-2';
    case 4:
    default:
      return 'grid-cols-2';
  }
});

const getImageContainerClass = (index) => {
  const count = Math.min(props.images.length, props.maxDisplay);
  
  // Single image - make it larger
  if (count === 1) {
    return 'aspect-video';
  }
  
  // Two images side by side
  if (count === 2) {
    return 'aspect-square';
  }
  
  // Three images - first one takes full row
  if (count === 3) {
    if (index === 0) {
      return 'col-span-2 aspect-video';
    }
    return 'aspect-square';
  }
  
  // Four or more images - 2x2 grid
  return 'aspect-square';
};

const openLightbox = (index) => {
  currentIndex.value = index;
  isLightboxOpen.value = true;
  document.body.style.overflow = 'hidden';
};

const closeLightbox = () => {
  isLightboxOpen.value = false;
  document.body.style.overflow = '';
};

const nextImage = () => {
  slideDirection.value = 'slide-right';
  currentIndex.value = (currentIndex.value + 1) % props.images.length;
};

const prevImage = () => {
  slideDirection.value = 'slide-left';
  currentIndex.value = (currentIndex.value - 1 + props.images.length) % props.images.length;
};

const handleKeydown = (e) => {
  if (!isLightboxOpen.value) return;
  
  switch (e.key) {
    case 'ArrowLeft':
      prevImage();
      break;
    case 'ArrowRight':
      nextImage();
      break;
    case 'Escape':
      closeLightbox();
      break;
  }
};

onMounted(() => {
  document.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
  document.removeEventListener('keydown', handleKeydown);
  document.body.style.overflow = '';
});
</script>

<style scoped>
/* Fade animation for lightbox backdrop */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* Slide animations for images */
.slide-right-enter-active,
.slide-right-leave-active,
.slide-left-enter-active,
.slide-left-leave-active {
  transition: all 0.3s ease;
}

.slide-right-enter-from {
  opacity: 0;
  transform: translateX(30px);
}

.slide-right-leave-to {
  opacity: 0;
  transform: translateX(-30px);
}

.slide-left-enter-from {
  opacity: 0;
  transform: translateX(-30px);
}

.slide-left-leave-to {
  opacity: 0;
  transform: translateX(30px);
}
</style>
