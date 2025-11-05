import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from './store'; // Import the auth store
import Register from './pages/Register.vue';
import Login from './pages/Login.vue';
import Home from './pages/Home.vue';
import Post from './pages/Post.vue';
import EditPost from './pages/EditPost.vue';
import UserProfile from './pages/UserProfile.vue';
import EditProfile from './pages/EditProfile.vue';

import AuthLayout from './layouts/AuthLayout.vue';
import DefaultLayout from './layouts/DefaultLayout.vue';

const routes = [
  {
    path: '/',
    component: DefaultLayout,
    children: [
      { path: '', component: Home, meta: { requiresAuth: true } },
      { path: 'posts/:id', component: Post, meta: { requiresAuth: true } },
      { path: 'posts/:id/edit', component: EditPost, meta: { requiresAuth: true } },
      { path: 'users/:id', component: UserProfile, meta: { requiresAuth: true } },
      { path: 'profile/edit', component: EditProfile, meta: { requiresAuth: true } },
    ],
  },
  {
    path: '/auth',
    component: AuthLayout,
    children: [
      { path: 'login', component: Login },
      { path: 'register', component: Register },
    ],
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

// --- Navigation Guard ---
router.beforeEach((to, from, next) => {
  const authStore = useAuthStore();
  // Check if the route requires authentication and if the user is not logged in
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    // Redirect to the login page
    next('/auth/login');
  } else {
    // Otherwise, allow navigation
    next();
  }
});

export default router;
