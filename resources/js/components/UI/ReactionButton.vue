<template>
  <div class="relative inline-flex items-center gap-2">
    <!-- Main Reaction Button with Hover Picker -->
    <div
      class="relative"
      @mouseenter="showPicker = true"
      @mouseleave="showPicker = false"
    >
      <!-- Main Button: Shows user's reaction or default Like + count -->
      <button
        @click="handleButtonClick"
        :class="[
          'flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-medium transition-all duration-200',
          currentReaction
            ? reactionStyles[currentReaction] || 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 border border-blue-200 dark:border-blue-700'
            : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-600 hover:bg-gray-200 dark:hover:bg-gray-600'
        ]"
      >
        <!-- Reaction emoji icons (stacked if multiple types exist) -->
        <div class="flex -space-x-1">
          <span 
            v-for="type in topReactionTypes" 
            :key="type" 
            class="text-base"
          >{{ reactionEmojis[type] }}</span>
          <span v-if="topReactionTypes.length === 0" class="text-base">👍</span>
        </div>
        
        <!-- Count and label -->
        <span v-if="localReactionsCount > 0" class="font-semibold">{{ localReactionsCount }}</span>
        <span class="hidden sm:inline">{{ currentReaction ? capitalizeFirst(currentReaction) : 'Like' }}</span>
      </button>

      <!-- Emoji Picker Popup (appears on hover) -->
      <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0 translate-y-2 scale-95"
        enter-to-class="opacity-100 translate-y-0 scale-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100 translate-y-0 scale-100"
        leave-to-class="opacity-0 translate-y-2 scale-95"
      >
        <div
          v-if="showPicker && !showModal"
          class="absolute bottom-full left-0 mb-2 flex items-center gap-1 p-2 bg-white dark:bg-gray-800 rounded-full shadow-lg border border-gray-200 dark:border-gray-600 z-50"
        >
          <button
            v-for="(emoji, type) in reactionEmojis"
            :key="type"
            @click.stop="handleReact(type)"
            :class="[
              'text-2xl p-1 rounded-full transition-all duration-150 hover:scale-125 hover:bg-gray-100 dark:hover:bg-gray-700',
              currentReaction === type ? 'bg-blue-100 dark:bg-blue-900/50 scale-110' : ''
            ]"
            :title="capitalizeFirst(type)"
          >
            {{ emoji }}
          </button>
        </div>
      </Transition>
    </div>

    <!-- Modal: Shows who reacted -->
    <Teleport to="body">
      <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div
          v-if="showModal"
          class="fixed inset-0 bg-black/50 flex items-center justify-center z-[100]"
          @click.self="showModal = false"
        >
          <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-md max-h-[70vh] overflow-hidden">
            <!-- Modal Header -->
            <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
              <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Reactions</h3>
              <button
                @click="showModal = false"
                class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 transition"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
            
            <!-- Reaction Type Tabs -->
            <div class="flex border-b border-gray-200 dark:border-gray-700 overflow-x-auto">
              <button
                @click="activeTab = 'all'"
                :class="[
                  'px-4 py-2 text-sm font-medium transition',
                  activeTab === 'all' 
                    ? 'border-b-2 border-blue-500 text-blue-600 dark:text-blue-400' 
                    : 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'
                ]"
              >
                All {{ localReactionsCount }}
              </button>
              <button
                v-for="(count, type) in localReactionsSummary"
                :key="type"
                @click="activeTab = type"
                :class="[
                  'px-4 py-2 text-sm font-medium transition flex items-center gap-1',
                  activeTab === type 
                    ? 'border-b-2 border-blue-500 text-blue-600 dark:text-blue-400' 
                    : 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'
                ]"
              >
                {{ reactionEmojis[type] }} {{ count }}
              </button>
            </div>
            
            <!-- Users List -->
            <div class="p-4 overflow-y-auto max-h-80">
              <div v-if="loadingUsers" class="flex justify-center py-8">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
              </div>
              <div v-else-if="filteredReactors.length === 0" class="text-center py-8 text-gray-500">
                No reactions yet
              </div>
              <div v-else class="space-y-3">
                <router-link
                  v-for="reactor in filteredReactors"
                  :key="reactor.id"
                  :to="`/users/${reactor.user.id}`"
                  @click="showModal = false"
                  class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                >
                  <!-- Avatar -->
                  <div class="relative">
                    <img
                      v-if="reactor.user.avatar_url"
                      :src="reactor.user.avatar_url"
                      :alt="reactor.user.name"
                      class="w-10 h-10 rounded-full object-cover"
                    >
                    <div v-else class="w-10 h-10 rounded-full bg-gray-300 flex items-center justify-center font-bold text-gray-600">
                      {{ reactor.user.name?.substring(0, 1) }}
                    </div>
                    <!-- Reaction badge -->
                    <span class="absolute -bottom-1 -right-1 text-sm">
                      {{ reactionEmojis[reactor.type] }}
                    </span>
                  </div>
                  
                  <!-- Name -->
                  <span class="font-medium text-gray-800 dark:text-white">{{ reactor.user.name }}</span>
                </router-link>
              </div>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import api from '../../services/api';

const props = defineProps({
  reactionableType: {
    type: String,
    required: true,
    validator: (val) => ['post', 'comment'].includes(val)
  },
  reactionableId: {
    type: Number,
    required: true
  },
  userReaction: {
    type: String,
    default: null
  },
  reactionsCount: {
    type: Number,
    default: 0
  },
  reactionsSummary: {
    type: Object,
    default: () => ({})
  }
});

const emit = defineEmits(['reaction-updated']);

// State
const showPicker = ref(false);
const showModal = ref(false);
const activeTab = ref('all');
const loadingUsers = ref(false);
const reactors = ref([]);
const currentReaction = ref(props.userReaction);
const localReactionsCount = ref(props.reactionsCount);
const localReactionsSummary = ref({ ...props.reactionsSummary });
const isLoading = ref(false);

// Watch for prop changes (when parent updates)
watch(() => props.userReaction, (val) => currentReaction.value = val);
watch(() => props.reactionsCount, (val) => localReactionsCount.value = val);
watch(() => props.reactionsSummary, (val) => localReactionsSummary.value = { ...val });

// Reaction emojis map
const reactionEmojis = {
  like: '👍',
  love: '❤️',
  haha: '😂',
  wow: '😮',
  sad: '😢',
  angry: '😡'
};

// Reaction button styles per type
const reactionStyles = {
  like: 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 border border-blue-200 dark:border-blue-700',
  love: 'bg-red-50 dark:bg-red-900/30 text-red-500 dark:text-red-400 border border-red-200 dark:border-red-700',
  haha: 'bg-yellow-50 dark:bg-yellow-900/30 text-yellow-600 dark:text-yellow-400 border border-yellow-200 dark:border-yellow-700',
  wow: 'bg-yellow-50 dark:bg-yellow-900/30 text-yellow-600 dark:text-yellow-400 border border-yellow-200 dark:border-yellow-700',
  sad: 'bg-yellow-50 dark:bg-yellow-900/30 text-yellow-600 dark:text-yellow-400 border border-yellow-200 dark:border-yellow-700',
  angry: 'bg-orange-50 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400 border border-orange-200 dark:border-orange-700'
};

// Computed
const topReactionTypes = computed(() => {
  const entries = Object.entries(localReactionsSummary.value);
  entries.sort((a, b) => b[1] - a[1]);
  return entries.slice(0, 3).map(([type]) => type);
});

const filteredReactors = computed(() => {
  if (activeTab.value === 'all') return reactors.value;
  return reactors.value.filter(r => r.type === activeTab.value);
});

// Methods
const capitalizeFirst = (str) => str.charAt(0).toUpperCase() + str.slice(1);

const handleButtonClick = () => {
  // If there are reactions, show the modal
  if (localReactionsCount.value > 0) {
    openModal();
  } else {
    // Otherwise, add a like
    handleReact('like');
  }
};

const openModal = async () => {
  showModal.value = true;
  showPicker.value = false;
  activeTab.value = 'all';
  
  if (reactors.value.length === 0) {
    await fetchReactors();
  }
};

const fetchReactors = async () => {
  loadingUsers.value = true;
  try {
    const response = await api.get(`/reactions`, {
      params: {
        reactionable_type: props.reactionableType,
        reactionable_id: props.reactionableId
      }
    });
    // API returns { reactions: [...], reactions_summary: {...}, reactions_count: N }
    reactors.value = Array.isArray(response.data.reactions) ? response.data.reactions : [];
    
    // Also update the summary and count so tabs show correctly
    if (response.data.reactions_summary) {
      localReactionsSummary.value = response.data.reactions_summary;
    }
    if (response.data.reactions_count !== undefined) {
      localReactionsCount.value = response.data.reactions_count;
    }
  } catch (error) {
    console.error('Error fetching reactors:', error);
    reactors.value = [];
  } finally {
    loadingUsers.value = false;
  }
};

const handleReact = async (type) => {
  if (isLoading.value) return;
  
  isLoading.value = true;
  showPicker.value = false;

  try {
    const response = await api.post('/react', {
      reactionable_type: props.reactionableType,
      reactionable_id: props.reactionableId,
      type: type
    });

    // Update local state from server response
    currentReaction.value = response.data.user_reaction;
    localReactionsCount.value = response.data.reactions_count;
    localReactionsSummary.value = response.data.reactions_summary || {};

    // Clear cached reactors so they'll be refetched
    reactors.value = [];

    // Emit event for parent component
    emit('reaction-updated', {
      userReaction: currentReaction.value,
      reactionsCount: localReactionsCount.value,
      reactionsSummary: localReactionsSummary.value
    });
  } catch (error) {
    console.error('Error reacting:', error);
  } finally {
    isLoading.value = false;
  }
};
</script>
