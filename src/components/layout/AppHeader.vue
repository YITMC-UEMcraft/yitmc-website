<template>
  <header class="app-header" :class="{ 'app-header--scrolled': scrolled }">
    <div class="app-header__inner">
      <router-link to="/" class="app-header__logo">
        <img src="/logo/logo.png" alt="YITMC Logo" class="app-header__logo-img" />
        <span class="app-header__logo-text">YITMC</span>
      </router-link>

      <nav class="app-header__nav" :class="{ 'app-header__nav--open': menuOpen }">
        <router-link v-for="link in navLinks" :key="link.to" :to="link.to" class="app-header__link" @click="menuOpen = false">
          {{ link.label }}
        </router-link>
        <a :href="config.skinStationUrl" target="_blank" rel="noopener" class="app-header__link" @click="menuOpen = false">皮肤站</a>
      </nav>

      <div class="app-header__actions">
        <AuthButton />
        <ThemeToggle />
        <button class="app-header__burger" @click="menuOpen = !menuOpen" aria-label="Toggle menu">
          <span></span><span></span><span></span>
        </button>
      </div>
    </div>
  </header>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { siteConfig } from '@/data'
import ThemeToggle from '@/components/layout/ThemeToggle.vue'
import AuthButton from '@/components/layout/AuthButton.vue'

const menuOpen = ref(false)
const scrolled = ref(false)
const config = siteConfig

const navLinks = [
  { to: '/', label: '首页' },
  { to: '/works', label: '作品' },
  { to: '/news', label: '动态' },
  { to: '/events', label: '活动' },
  { to: '/docs', label: '指南' },
  { to: '/wall', label: '留言墙' },
  { to: '/members', label: '成员' },
  { to: '/about', label: '关于' },
  { to: '/join', label: '加入' },
]

function onScroll() {
  scrolled.value = window.scrollY > 20
}

onMounted(() => window.addEventListener('scroll', onScroll))
onUnmounted(() => window.removeEventListener('scroll', onScroll))
</script>

<style scoped>
.app-header {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 100;
  height: var(--header-height);
  background: var(--color-bg);
  border-bottom: var(--border-width) solid var(--color-surface);
  transition: background var(--duration-fast) var(--ease-pixel),
              border-color var(--duration-fast) var(--ease-pixel);
}

.app-header--scrolled {
  background: var(--color-bg-translucent);
  border-bottom-color: var(--color-accent-dim);
}

.app-header__inner {
  max-width: var(--max-width);
  margin: 0 auto;
  padding: 0 var(--space-lg);
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.app-header__logo {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
  color: var(--color-text) !important;
  text-decoration: none;
}

.app-header__logo-img {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  object-fit: cover;
}

.app-header__logo-text {
  font-family: var(--font-pixel);
  font-size: var(--text-lg);
  color: var(--color-accent);
  text-shadow: 2px 2px 0px rgba(0, 0, 0, 0.5);
}

.app-header__nav {
  display: flex;
  gap: var(--space-xs);
}

.app-header__link {
  font-family: var(--font-display);
  font-size: var(--text-sm);
  color: var(--color-text-muted) !important;
  padding: var(--space-xs) var(--space-md);
  border: var(--border-width-sm) solid transparent;
  transition: all var(--duration-fast) var(--ease-pixel);
  image-rendering: pixelated;
}

.app-header__link:hover,
.app-header__link.router-link-active {
  color: var(--color-accent) !important;
  border-color: var(--color-accent);
  background: rgba(92, 154, 59, 0.1);
}

.app-header__actions {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
  flex: none;
}

.app-header__burger {
  display: none;
  flex-direction: column;
  gap: 4px;
  background: none;
  border: var(--border-width-sm) solid var(--color-brown);
  padding: 8px;
  cursor: pointer;
}

.app-header__burger span {
  display: block;
  width: 24px;
  height: 3px;
  background: var(--color-text);
}

@media (max-width: 1000px) {
  .app-header__link {
    padding-left: var(--space-sm);
    padding-right: var(--space-sm);
  }
}

@media (max-width: 768px) {
  .app-header__nav {
    position: fixed;
    top: var(--header-height);
    left: 0;
    right: 0;
    z-index: 99;
    flex-direction: column;
    background: var(--color-bg);
    border-bottom: var(--border-width) solid var(--color-accent-dim);
    padding: var(--space-md);
    display: none;
    max-height: calc(100dvh - var(--header-height));
    overflow-y: auto;
    box-shadow: 0 8px 0 rgba(0, 0, 0, 0.35);
  }

  .app-header__nav--open {
    display: flex;
  }

  .app-header__burger {
    display: flex;
  }
}
</style>
