<template>
  <div class="app-root">
    <router-view v-slot="{ Component }">
      <transition name="page" mode="out-in">
        <component :is="Component" />
      </transition>
    </router-view>
    <MascotDock side="left" name="沃伦" role="社团吉祥物" image="/mascot/warren.webp" />
    <MascotDock side="right" name="沃蒂娜" role="社团吉祥物" image="/mascot/vodina.webp" />
  </div>
</template>

<script setup lang="ts">
import { onBeforeUnmount, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useScrollReveal } from '@/composables/useScrollReveal'
import { useAuth } from '@/composables/useAuth'
import MascotDock from '@/components/layout/MascotDock.vue'

const router = useRouter()
const { init, destroy } = useScrollReveal()
const { refreshAuth } = useAuth()

// 页面切换时重新观察新页面里的 [data-reveal] 元素；
// 延迟需覆盖 out-in 过渡的离场时长（150ms）
function observeReveals() {
  window.setTimeout(() => init(), 250)
}

onMounted(() => {
  init()
  refreshAuth()
  router.afterEach(observeReveals)
})

onBeforeUnmount(destroy)
</script>

<style>
.app-root {
  min-height: 100vh;
  background: var(--color-bg);
}
</style>
