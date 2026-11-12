<template>
  <transition :name="`mascot-dock-${side}`">
    <aside
      v-if="visible"
      class="mascot-dock"
      :class="`mascot-dock--${side}`"
      :aria-label="name"
    >
      <!-- 收起态：只留一个小头像入口 -->
      <button
        v-if="collapsed"
        type="button"
        class="mascot-dock__tab"
        :aria-label="`展开${name}`"
        @click="collapsed = false"
      >
        <img :src="image" :alt="name" />
        <span class="mascot-dock__tab-label">{{ name }}</span>
      </button>

      <!-- 展开态：像素风悬浮窗 -->
      <div v-else class="mascot-dock__window">
        <div class="mascot-dock__titlebar">
          <span class="mascot-dock__dot" aria-hidden="true"></span>
          <span class="mascot-dock__title">{{ name }}</span>
          <button
            type="button"
            class="mascot-dock__min"
            :aria-label="`收起${name}`"
            title="收起"
            @click="collapsed = true"
          >—</button>
        </div>

        <div class="mascot-dock__body">
          <img
            :src="image"
            :alt="`YITMC ${role} ${name}`"
            class="mascot-dock__avatar"
            decoding="async"
          />
        </div>

        <div class="mascot-dock__nameplate">
          <span class="mascot-dock__name">{{ name }}</span>
          <span class="mascot-dock__role">{{ role }}</span>
        </div>
      </div>
    </aside>
  </transition>
</template>

<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'

defineProps<{
  /** 悬浮窗所在侧，左右各一个 */
  side: 'left' | 'right'
  /** 吉祥物名字 */
  name: string
  /** 身份标注 */
  role: string
  /** 立绘地址（publicDir: img） */
  image: string
}>()

const route = useRoute()
const router = useRouter()
const visible = ref(false)
// 手机屏幕寸土寸金，默认收成小头像，点开再看立绘
const collapsed = ref(
  typeof window !== 'undefined' && window.matchMedia('(max-width: 768px)').matches,
)

let observer: IntersectionObserver | null = null
let timer = 0
let attempt = 0

function cleanup() {
  observer?.disconnect()
  observer = null
  window.clearTimeout(timer)
}

// 首页 hero 占满首屏：hero 还在视口内就不出现，
// 滑到 hero 下面（或进入没有 hero 的页面）才展示。
function scan() {
  if (route.path.startsWith('/admin')) {
    visible.value = false
    return
  }

  const hero = document.querySelector<HTMLElement>('.hero')

  // 首次加载时路由可能还没解析完（route.name 为空），
  // 首页 hero 也可能因懒加载还没挂载：先保持隐藏并继续等，
  // 避免在首屏上闪一下
  if (!hero) {
    const pending = route.name == null || route.name === 'home'
    if (pending && attempt++ < 40) {
      timer = window.setTimeout(scan, 100)
      return
    }
    visible.value = true
    return
  }

  observer = new IntersectionObserver(
    ([entry]) => {
      visible.value = !entry.isIntersecting
    },
    { threshold: 0 },
  )
  observer.observe(hero)
  // 首帧先按几何位置判断，避免等 IO 首次回调时闪一下
  visible.value = hero.getBoundingClientRect().bottom <= 0
}

function syncVisibility() {
  cleanup()
  attempt = 0
  // 等待 out-in 页面过渡结束（150ms）后再扫描新页面的 hero
  timer = window.setTimeout(scan, 250)
}

onMounted(() => {
  syncVisibility()
  // 首屏路由解析完成后再同步一次（loadRemoteData 可能先于路由就绪挂载）
  router.isReady().then(syncVisibility)
})

watch(
  [() => route.fullPath, () => route.name],
  () => {
    // 回到首页时先藏起来，避免路由切换瞬间闪过
    if (route.name === 'home' || route.path.startsWith('/admin')) {
      visible.value = false
    }
    syncVisibility()
  },
)

onBeforeUnmount(cleanup)
</script>

<style scoped>
.mascot-dock {
  position: fixed;
  bottom: calc(var(--space-lg) + env(safe-area-inset-bottom, 0px));
  z-index: 90;
  width: clamp(140px, 10vw, 180px);
  display: flex;
  will-change: transform, opacity;
}

.mascot-dock--right {
  right: var(--space-lg);
  justify-content: flex-end;
}

.mascot-dock--left {
  left: var(--space-lg);
  justify-content: flex-start;
}

.mascot-dock__window {
  background: var(--color-bg);
  border: var(--border-width-sm) solid var(--color-brown);
  box-shadow: var(--shadow-pixel);
  transition: transform 0.2s var(--ease-spring), box-shadow 0.2s var(--ease-apple-out);
}

.mascot-dock__window:hover {
  transform: translate3d(0, -3px, 0);
  box-shadow: var(--shadow-pixel-lg);
}

.mascot-dock__titlebar {
  display: flex;
  align-items: center;
  gap: var(--space-xs);
  padding: 4px var(--space-sm);
  background: var(--color-accent-dim);
  border-bottom: var(--border-width-sm) solid var(--color-surface-light);
}

/* 左侧窗口做镜像，和右侧对称 */
.mascot-dock--left .mascot-dock__titlebar {
  flex-direction: row-reverse;
}

.mascot-dock__dot {
  width: 6px;
  height: 6px;
  flex: none;
  background: var(--color-accent-hover);
  box-shadow: 0 0 6px rgba(109, 184, 64, 0.8);
  animation: mascotBlink 2.4s steps(2, jump-none) infinite;
}

.mascot-dock__title {
  flex: 1;
  font-family: var(--font-display);
  font-size: var(--text-sm);
  line-height: 1.4;
  color: var(--color-text);
  text-shadow: 2px 2px 0px rgba(0, 0, 0, 0.5);
}

.mascot-dock--left .mascot-dock__title {
  text-align: right;
}

.mascot-dock__min {
  flex: none;
  width: 18px;
  height: 18px;
  display: grid;
  place-items: center;
  background: transparent;
  border: var(--border-width-sm) solid transparent;
  color: var(--color-text-muted);
  font-family: var(--font-body);
  font-size: var(--text-sm);
  line-height: 1;
  cursor: pointer;
  transition: all var(--duration-fast) var(--ease-pixel);
}

.mascot-dock__min:hover {
  background: rgba(0, 0, 0, 0.3);
  border-color: var(--color-accent);
  color: var(--color-text);
}

.mascot-dock__body {
  background:
    radial-gradient(ellipse at 50% 25%, rgba(92, 154, 59, 0.16), transparent 65%),
    var(--color-bg);
}

.mascot-dock__avatar {
  width: 100%;
  height: auto;
  max-height: min(46vh, 320px);
  object-fit: contain;
  object-position: bottom center;
}

.mascot-dock__nameplate {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: var(--space-sm);
  padding: 4px var(--space-sm);
  background: var(--color-surface);
  border-top: var(--border-width-sm) solid var(--color-surface-light);
}

.mascot-dock--left .mascot-dock__nameplate {
  flex-direction: row-reverse;
}

.mascot-dock__name {
  font-family: var(--font-display);
  font-size: var(--text-base);
  line-height: 1.2;
  color: var(--color-accent-hover);
  text-shadow: 2px 2px 0px rgba(0, 0, 0, 0.5);
  white-space: nowrap;
}

.mascot-dock__role {
  font-size: var(--text-xs);
  color: var(--color-text-muted);
  letter-spacing: 0.05em;
  white-space: nowrap;
}

/* 收起态 */
.mascot-dock__tab {
  position: relative;
  width: 56px;
  height: 56px;
  padding: 0;
  overflow: hidden;
  background: var(--color-surface);
  border: var(--border-width-sm) solid var(--color-brown);
  box-shadow: var(--shadow-pixel-sm);
  cursor: pointer;
  transition: transform 0.2s var(--ease-spring);
}

.mascot-dock__tab:hover {
  transform: translate3d(0, -2px, 0);
}

.mascot-dock__tab img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: 50% 10%;
}

.mascot-dock__tab-label {
  position: absolute;
  left: 0;
  right: 0;
  bottom: 0;
  padding: 1px 0;
  background: rgba(26, 26, 26, 0.82);
  font-family: var(--font-display);
  font-size: 9px;
  line-height: 1.4;
  text-align: center;
  color: #f2ecdc;
}

@keyframes mascotBlink {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.35; }
}

/* 出现 / 消失：右侧从右滑入，左侧从左滑入 */
.mascot-dock-left-enter-active,
.mascot-dock-left-leave-active,
.mascot-dock-right-enter-active,
.mascot-dock-right-leave-active {
  transition: opacity var(--duration-slow) var(--ease-apple-out),
              transform var(--duration-slow) var(--ease-apple-out);
}

.mascot-dock-left-enter-from,
.mascot-dock-left-leave-to {
  opacity: 0;
  transform: translate3d(-32px, 16px, 0);
}

.mascot-dock-right-enter-from,
.mascot-dock-right-leave-to {
  opacity: 0;
  transform: translate3d(32px, 16px, 0);
}

@media (max-width: 768px) {
  .mascot-dock {
    bottom: calc(var(--space-md) + env(safe-area-inset-bottom, 0px));
    width: clamp(88px, 24vw, 118px);
  }

  .mascot-dock--right {
    right: var(--space-md);
  }

  .mascot-dock--left {
    left: var(--space-md);
  }

  .mascot-dock__title {
    font-size: 0.6875rem;
  }

  .mascot-dock__min {
    width: 16px;
    height: 16px;
  }

  .mascot-dock__avatar {
    max-height: 30vh;
  }

  .mascot-dock__name {
    font-size: var(--text-sm);
  }

  .mascot-dock__role {
    display: none;
  }
}

@media (max-height: 520px) {
  .mascot-dock__avatar {
    max-height: 32vh;
  }
}
</style>
