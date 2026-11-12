<template>
  <button
    type="button"
    class="theme-toggle"
    :aria-label="theme === 'dark' ? '切换到浅色主题' : '切换到深色主题'"
    :title="theme === 'dark' ? '切换到浅色主题' : '切换到深色主题'"
    @click="toggleTheme"
  >
    <AppIcon :name="theme === 'dark' ? 'sun' : 'moon'" :size="18" />
  </button>
</template>

<script setup lang="ts">
import AppIcon from '@/components/icons/AppIcon.vue'
import { useTheme } from '@/composables/useTheme'

const { theme, toggleTheme } = useTheme()
</script>

<style scoped>
.theme-toggle {
  width: 36px;
  height: 36px;
  display: grid;
  place-items: center;
  flex: none;
  background: transparent;
  border: var(--border-width-sm) solid var(--color-surface-light);
  color: var(--color-text-muted);
  cursor: pointer;
  transition: color var(--duration-fast) var(--ease-pixel),
              border-color var(--duration-fast) var(--ease-pixel),
              background var(--duration-fast) var(--ease-pixel);
}

.theme-toggle:hover {
  color: var(--color-accent);
  border-color: var(--color-accent);
  background: rgba(92, 154, 59, 0.1);
}
</style>
