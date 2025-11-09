import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from './stores/auth';
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
      { path: '', name: 'Home', component: Home, meta: { requiresAuth: true } },
      { path: 'posts/:id', name: 'Post', component: Post, meta: { requiresAuth: true } },
      { path: 'posts/:id/edit', name: 'EditPost', component: EditPost, meta: { requiresAuth: true } },
      { path: 'users/:id', name: 'UserProfile', component: UserProfile, meta: { requiresAuth: true } },
      { path: 'profile/edit', name: 'EditProfile', component: EditProfile, meta: { requiresAuth: true } },
    ],
  },
  {
    path: '/auth',
    component: AuthLayout,
    // Add a meta field to this group to identify auth-only routes
    meta: { requiresGuest: true },
    children: [
      { path: 'login', name: 'Login', component: Login },
      { path: 'register', name: 'Register', component: Register },
    ],
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

// --- Complete Navigation Guard ---
router.beforeEach((to, from, next) => {
  const authStore = useAuthStore();
  const isAuthenticated = authStore.isAuthenticated;

  // Case 1: Route requires authentication
  if (to.meta.requiresAuth) {
    if (isAuthenticated) {
      // User is authenticated, allow access
      next();
    } else {
      // User is not authenticated, redirect to login
      next({ name: 'Login' });
    }
  }
  // Case 2: Route is for guests only (like login/register)
  else if (to.meta.requiresGuest) {
    if (isAuthenticated) {
      // User is already logged in, redirect them to the home page
      next({ name: 'Home' });
    } else {
      // User is not logged in, allow access
      next();
    }
  }
  // Case 3: Route doesn't have any specific auth requirements
  else {
    next();
  }
});

export default router;
