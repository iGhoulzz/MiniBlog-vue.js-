<template>
  <div class="flex items-center justify-center min-h-screen bg-gray-100">
    <router-view v-slot="{ Component }">
      <Transition name="fade" mode="out-in">
        <component :is="Component" />
      </Transition>
    </router-view>
  </div>
</template>
