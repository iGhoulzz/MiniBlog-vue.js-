<template>
    <div class="relative">
        <!-- Bell Icon Button -->
        <button
            @click="toggleDropdown"
            class="relative p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition"
        >
            <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-6 w-6 text-gray-600 dark:text-gray-300"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
                />
            </svg>

            <!-- Unread Badge -->
            <span
                v-if="unreadCount > 0"
                class="absolute -top-1 -right-1 bg-red-500 text-white text-xs font-bold rounded-full h-5 w-5 flex items-center justify-center"
            >
                {{ unreadCount > 9 ? "9+" : unreadCount }}
            </span>
        </button>

        <!-- Dropdown -->
        <Transition name="dropdown">
            <div
                v-if="isOpen"
                class="absolute right-0 mt-2 w-80 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 z-50 overflow-hidden"
            >
                <!-- Header -->
                <div
                    class="flex items-center justify-between px-4 py-3 border-b border-gray-200 dark:border-gray-700"
                >
                    <h3 class="font-semibold text-gray-800 dark:text-white">
                        Notifications
                    </h3>
                    <button
                        v-if="unreadCount > 0"
                        @click="markAllAsRead"
                        class="text-sm text-blue-500 hover:text-blue-600"
                    >
                        Mark all as read
                    </button>
                </div>

                <!-- Loading -->
                <div v-if="isLoading" class="p-8 text-center">
                    <div
                        class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500 mx-auto"
                    ></div>
                </div>

                <!-- Notification List -->
                <div
                    v-else-if="notifications.length > 0"
                    class="max-h-96 overflow-y-auto"
                >
                    <div
                        v-for="notification in notifications"
                        :key="notification.id"
                        @click="handleNotificationClick(notification)"
                        :class="[
                            'flex items-start gap-3 px-4 py-3 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 transition border-b border-gray-100 dark:border-gray-700',
                            !notification.read_at
                                ? 'bg-blue-50 dark:bg-blue-900/20'
                                : '',
                        ]"
                    >
                        <!-- Avatar -->
                        <div class="flex-shrink-0 relative">
                            <img
                                v-if="
                                    notification.data.reactor_avatar ||
                                    notification.data.commenter_avatar
                                "
                                :src="
                                    notification.data.reactor_avatar ||
                                    notification.data.commenter_avatar
                                "
                                class="w-10 h-10 rounded-full object-cover"
                            />
                            <div
                                v-else
                                class="w-10 h-10 rounded-full bg-gray-300 flex items-center justify-center font-bold text-gray-600"
                            >
                                {{
                                    (
                                        notification.data.reactor_name ||
                                        notification.data.commenter_name
                                    )?.substring(0, 1)
                                }}
                            </div>
                            <span
                                v-if="notification.data.reaction_emoji"
                                class="absolute -bottom-1 -right-1 text-sm"
                            >
                                {{ notification.data.reaction_emoji }}
                            </span>
                            <span
                                v-else-if="
                                    notification.type ===
                                        'App\\Notifications\\CommentNotification' ||
                                    notification.data.type === 'comment'
                                "
                                class="absolute -bottom-1 -right-1 text-sm bg-white rounded-full p-0.5"
                            >
                                💬
                            </span>
                        </div>

                        <!-- Content -->
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-gray-800 dark:text-gray-200">
                                {{ notification.data.message }}
                            </p>
                            <p class="text-xs text-gray-500 mt-1">
                                {{ formatTime(notification.created_at) }}
                            </p>
                        </div>

                        <!-- Unread dot -->
                        <div
                            v-if="!notification.read_at"
                            class="w-2 h-2 bg-blue-500 rounded-full mt-2"
                        ></div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="p-8 text-center text-gray-500">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-12 w-12 mx-auto mb-2 text-gray-300"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
                        />
                    </svg>
                    <p>No notifications yet</p>
                </div>
            </div>
        </Transition>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from "vue";
import { useRouter } from "vue-router";
import api from "../../services/api";

const router = useRouter();
const isOpen = ref(false);
const isLoading = ref(false);
const notifications = ref([]);
const unreadCount = ref(0);

const toggleDropdown = () => {
    isOpen.value = !isOpen.value;
    if (isOpen.value) {
        // Emit event to close other dropdowns
        window.dispatchEvent(new CustomEvent('dropdown-opened', { detail: 'notifications' }));
        fetchNotifications();
    }
};

// Listen for other dropdowns opening
const handleOtherDropdownOpened = (event) => {
    if (event.detail !== 'notifications') {
        isOpen.value = false;
    }
};

onMounted(() => {
    window.addEventListener('dropdown-opened', handleOtherDropdownOpened);
});

onUnmounted(() => {
    window.removeEventListener('dropdown-opened', handleOtherDropdownOpened);
});

const fetchNotifications = async () => {
    isLoading.value = true;
    try {
        const response = await api.get("/notifications");
        notifications.value = response.data.notifications;
        unreadCount.value = response.data.unread_count;
    } catch (error) {
        console.error("Error fetching notifications:", error);
    } finally {
        isLoading.value = false;
    }
};

const markAllAsRead = async () => {
    try {
        await api.patch("/notifications/read-all");
        notifications.value = notifications.value.map((n) => ({
            ...n,
            read_at: new Date().toISOString(),
        }));
        unreadCount.value = 0;
    } catch (error) {
        console.error("Error marking notifications as read:", error);
    }
};

const handleNotificationClick = async (notification) => {
    // Mark as read
    if (!notification.read_at) {
        try {
            await api.patch(`/notifications/${notification.id}/read`);
            notification.read_at = new Date().toISOString();
            unreadCount.value = Math.max(0, unreadCount.value - 1);
        } catch (error) {
            console.error("Error marking notification as read:", error);
        }
    }

    isOpen.value = false;

    // Navigate to the post/comment
    if (notification.data.reactionable_type === "post") {
        router.push(`/posts/${notification.data.reactionable_id}`);
    } else if (notification.data.post_id) {
        router.push(`/posts/${notification.data.post_id}`);
    }
};

const formatTime = (dateString) => {
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);

    if (diffMins < 1) return "Just now";
    if (diffMins < 60) return `${diffMins}m ago`;
    if (diffHours < 24) return `${diffHours}h ago`;
    if (diffDays < 7) return `${diffDays}d ago`;
    return date.toLocaleDateString();
};

// Fetch on mount
onMounted(() => {
    fetchNotifications();
});

// Close dropdown when clicking outside
const handleClickOutside = (event) => {
    if (!event.target.closest(".relative")) {
        isOpen.value = false;
    }
};

onMounted(() => {
    document.addEventListener("click", handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener("click", handleClickOutside);
});
</script>

<style scoped>
.dropdown-enter-active,
.dropdown-leave-active {
    transition: all 0.2s ease;
}

.dropdown-enter-from,
.dropdown-leave-to {
    opacity: 0;
    transform: translateY(-10px);
}
</style>
