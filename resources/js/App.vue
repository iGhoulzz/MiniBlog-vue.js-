<template>
  <router-view v-slot="{ Component }">
    <Transition name="fade" mode="out-in">
      <component :is="Component" />
    </Transition>
  </router-view>
  <Notification />
</template>

<script setup>
import Notification from './components/Notification.vue';
</script>
