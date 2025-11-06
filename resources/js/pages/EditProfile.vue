<template>
    <div class="container mx-auto p-4">
        <h1 class="text-2xl font-bold mb-4">Edit Profile</h1>
        <form v-if="authStore.user" @submit.prevent="updateProfile" class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="name">
                    Name
                </label>
                <input v-model="name" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="name" type="text" placeholder="Name">
            </div>
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="avatar">
                    Avatar
                </label>
                <div
                    @dragover.prevent="onDragOver"
                    @dragleave.prevent="onDragLeave"
                    @drop.prevent="onDrop"
                    @click="triggerFileInput"
                    :class="['w-full', 'p-6', 'border-2', 'border-dashed', 'rounded-md', 'text-center', 'cursor-pointer', isDragging ? 'border-blue-500' : 'border-gray-300']"
                >
                    <input type="file" ref="fileInput" @change="onFileChange" class="hidden" id="avatar">
                    <p v-if="!avatarPreview && !(authStore.user && authStore.user.avatar)">Drag & drop your avatar here, or click to select a file.</p>
                    <div v-if="avatarPreview" class="mt-4">
                        <img :src="avatarPreview" alt="Avatar Preview" class="w-32 h-32 rounded-full object-cover mx-auto">
                    </div>
                    <div v-else-if="authStore.user && authStore.user.avatar" class="mt-4">
                        <img :src="authStore.user.avatar" alt="Current Avatar" class="w-32 h-32 rounded-full object-cover mx-auto">
                    </div>
                    <p v-if="!avatarPreview && authStore.user && authStore.user.avatar" class="mt-2">Drag & drop a new avatar or click to change.</p>
                </div>
            </div>
            <div class="flex items-center justify-between">
                <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" type="submit">
                    Update Profile
                </button>
            </div>
        </form>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth.js';
import api from '../services/api';

const router = useRouter();
const authStore = useAuthStore();
const name = ref('');
const avatarFile = ref(null);
const avatarPreview = ref(null);
const isDragging = ref(false);
const fileInput = ref(null);

onMounted(() => {
    // Get user data directly from the auth store instead of making an API call
    if (authStore.user) {
        name.value = authStore.user.name;
    }
});

const onFileChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        avatarFile.value = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            avatarPreview.value = e.target.result;
        };
        reader.readAsDataURL(file);
    }
};

const onDragOver = () => {
    isDragging.value = true;
};

const onDragLeave = () => {
    isDragging.value = false;
};

const onDrop = (e) => {
    isDragging.value = false;
    const file = e.dataTransfer.files[0];
    if (file) {
        avatarFile.value = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            avatarPreview.value = e.target.result;
        };
        reader.readAsDataURL(file);
    }
};

const triggerFileInput = () => {
    fileInput.value.click();
};

const updateProfile = async () => {
    const formData = new FormData();
    formData.append('name', name.value);
    if (avatarFile.value) {
        formData.append('avatar', avatarFile.value);
    }
    formData.append('_method', 'PATCH');

    try {
        const response = await api.post(`/users/${authStore.user.id}`, formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });
        // Update the user in the auth store with the new data
        // Handle both response.data.user and response.data formats
        const updatedUser = response.data.user || response.data;
        authStore.setUser(authStore.token, updatedUser);
        // After successful update, redirect to the user's profile page.
        router.push({ name: 'UserProfile', params: { id: authStore.user.id } });
    } catch (error) {
        console.error('Error updating profile:', error);
    }
};
</script>
