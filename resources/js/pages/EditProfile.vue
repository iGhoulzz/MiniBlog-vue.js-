<template>
    <div class="container mx-auto p-4">
        <h1 class="text-2xl font-bold mb-4 text-gray-800 dark:text-white">Edit Profile</h1>
        <BaseCard v-if="authStore.user" class="px-8 pt-6 pb-8 mb-4">
            <form @submit.prevent="updateProfile">
                <BaseInput
                    id="name"
                    label="Name"
                    type="text"
                    v-model="name"
                    placeholder="Your Name"
                />

                <div class="mb-6">
                    <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2" for="avatar">
                        Avatar
                    </label>
                    <div
                        @dragover.prevent="onDragOver"
                        @dragleave.prevent="onDragLeave"
                        @drop.prevent="onDrop"
                        @click="triggerFileInput"
                        :class="['w-full', 'p-6', 'border-2', 'border-dashed', 'rounded-md', 'text-center', 'cursor-pointer', isDragging ? 'border-blue-500' : 'border-gray-300 dark:border-gray-600', 'dark:bg-gray-700 dark:text-gray-300']"
                    >
                        <input type="file" ref="fileInput" @change="onFileChange" class="hidden" id="avatar">
                        <p v-if="!avatarPreview && !(authStore.user && authStore.user.avatar_url) && !avatarRemoved">Drag & drop your avatar here, or click to select a file.</p>
                        <div v-if="avatarPreview" class="mt-4">
                            <img :src="avatarPreview" alt="Avatar Preview" class="w-32 h-32 rounded-full object-cover mx-auto">
                        </div>
                        <div v-else-if="authStore.user && authStore.user.avatar_url && !avatarRemoved" class="mt-4">
                            <img :src="authStore.user.avatar_url" alt="Current Avatar" class="w-32 h-32 rounded-full object-cover mx-auto">
                        </div>
                         <p v-if="avatarRemoved" class="text-gray-500 dark:text-gray-400">Avatar will be removed upon update.</p>
                        <p v-if="!avatarPreview && authStore.user && authStore.user.avatar_url && !avatarRemoved" class="mt-2">Drag & drop a new avatar or click to change.</p>
                    </div>
                    <button v-if="authStore.user?.avatar_url && !avatarRemoved" @click.prevent="markAvatarForRemoval" type="button" class="mt-2 text-sm text-red-500 hover:text-red-700">
                        Remove Avatar
                    </button>
                </div>
                <div class="flex items-center justify-between">
                    <BaseButton type="submit">
                        Update Profile
                    </BaseButton>
                </div>
            </form>
        </BaseCard>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth.js';
import { useNotificationStore } from '../stores/notification.js';
import api from '../services/api';
import BaseCard from '../components/UI/BaseCard.vue';
import BaseInput from '../components/UI/BaseInput.vue';
import BaseButton from '../components/UI/BaseButton.vue';

const router = useRouter();
const authStore = useAuthStore();
const notificationStore = useNotificationStore();
const name = ref('');
const avatarFile = ref(null);
const avatarPreview = ref(null);
const isDragging = ref(false);
const fileInput = ref(null);
const avatarRemoved = ref(false);

onMounted(() => {
    if (authStore.user) {
        name.value = authStore.user.name;
    }
});

const markAvatarForRemoval = () => {
    avatarFile.value = null;
    avatarPreview.value = null;
    avatarRemoved.value = true;
};

const onFileChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        avatarRemoved.value = false;
        avatarFile.value = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            avatarPreview.value = e.target.result;
        };
        reader.readAsDataURL(file);
    }
};

const onDragOver = () => { isDragging.value = true; };
const onDragLeave = () => { isDragging.value = false; };

const onDrop = (e) => {
    isDragging.value = false;
    const file = e.dataTransfer.files[0];
    if (file) {
        avatarRemoved.value = false;
        avatarFile.value = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            avatarPreview.value = e.target.result;
        };
        reader.readAsDataURL(file);
    }
};

const triggerFileInput = () => { fileInput.value.click(); };

const updateProfile = async () => {
    const formData = new FormData();
    formData.append('name', name.value);

    if (avatarFile.value) {
        formData.append('avatar', avatarFile.value);
    } else if (avatarRemoved.value) {
        formData.append('remove_avatar', 'true');
    }

    formData.append('_method', 'PATCH');

    try {
        await api.post(`/users/${authStore.user.id}`, formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });

        // Fetch fresh user data to ensure the store is 100% correct
        await authStore.fetchUser();

        notificationStore.showNotification({
            message: 'Profile updated successfully!',
            type: 'success'
        });

        router.push({ name: 'UserProfile', params: { id: authStore.user.id } });
    } catch (error) {
        notificationStore.showNotification({
            message: 'Error updating profile. Please try again.',
            type: 'error'
        });
        console.error('Error updating profile:', error);
    }
};
</script>
