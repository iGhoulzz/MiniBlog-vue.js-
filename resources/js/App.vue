<template>
  <router-view v-slot="{ Component }">
    <Transition name="fade" mode="out-in">
      <component :is="Component" />
    </Transition>
  </router-view>
  <Notification />
  <ChatDock />
</template>

<script setup>
import Notification from './components/Notification.vue';
import ChatDock from './components/Chat/ChatDock.vue';
</script>
