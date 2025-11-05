<template>
    <div class="container mx-auto p-4">
        <h1 class="text-2xl font-bold mb-4">Edit Profile</h1>
        <form v-if="user" @submit.prevent="updateProfile" class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
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
                <input @change="onFileChange" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="avatar" type="file">
                <div v-if="avatarPreview" class="mt-4">
                    <img :src="avatarPreview" alt="Avatar Preview" class="w-32 h-32 rounded-full object-cover">
                </div>
                <div v-else-if="user && user.avatar" class="mt-4">
                    <img :src="user.avatar" alt="Current Avatar" class="w-32 h-32 rounded-full object-cover">
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

<script>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '../services/api';

export default {
    setup() {
        const router = useRouter();
        const user = ref(null);
        const name = ref('');
        const avatarFile = ref(null);
        const avatarPreview = ref(null);

        onMounted(async () => {
            try {
                const response = await api.get('/api/user');
                user.value = response.data;
                name.value = user.value.name;
            } catch (error) {
                console.error('Error fetching user for editing:', error);
            }
        });

        const onFileChange = (e) => {
            const file = e.target.files[0];
            avatarFile.value = file;
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    avatarPreview.value = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        };

        const updateProfile = async () => {
            const formData = new FormData();
            formData.append('name', name.value);
            if (avatarFile.value) {
                formData.append('avatar', avatarFile.value);
            }
            formData.append('_method', 'PATCH');

            try {
                await api.post(`/api/users/${user.value.id}`, formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data',
                    },
                });
                // After successful update, redirect to the user's profile page.
                // The UserProfile page will fetch the fresh data.
                router.push({ name: 'UserProfile', params: { id: user.value.id } });
            } catch (error) {
                console.error('Error updating profile:', error);
            }
        };

        return {
            user,
            name,
            avatarFile,
            avatarPreview,
            onFileChange,
            updateProfile,
        };
    },
};
</script>
