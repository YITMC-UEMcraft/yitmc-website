<template>
  <div class="copy-text block-inset">
    <span class="copy-text__label">{{ label }}</span>
    <div class="copy-text__row">
      <code class="copy-text__value">{{ text }}</code>
      <button class="copy-text__btn" @click="copy" :class="{ 'copy-text__btn--done': copied }">
        <AppIcon :name="copied ? 'check' : 'clipboard'" :size="14" />
        {{ copied ? '已复制' : '复制' }}
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import AppIcon from '@/components/icons/AppIcon.vue'

const props = defineProps<{ label: string; text: string }>()
const copied = ref(false)

async function copy() {
  await navigator.clipboard.writeText(props.text)
  copied.value = true
  setTimeout(() => { copied.value = false }, 2000)
}
</script>

<style scoped>
.copy-text {
  padding: var(--space-lg);
  background: var(--color-surface);
}

.copy-text__label {
  display: block;
  font-family: var(--font-pixel);
  font-size: var(--text-xs);
  color: var(--color-accent);
  margin-bottom: var(--space-sm);
}

.copy-text__row {
  display: flex;
  gap: var(--space-sm);
  align-items: center;
}

.copy-text__value {
  flex: 1;
  min-width: 0;
  font-family: var(--font-body);
  font-size: var(--text-lg);
  color: var(--color-text);
  background: var(--color-bg);
  padding: var(--space-sm) var(--space-md);
  border: var(--border-width-sm) solid var(--color-surface-light);
  overflow-wrap: anywhere;
}

.copy-text__btn {
  font-family: var(--font-pixel);
  font-size: var(--text-xs);
  padding: var(--space-sm) var(--space-md);
  background: var(--color-surface-light);
  color: var(--color-text);
  border: var(--border-width-sm) solid var(--color-brown);
  cursor: pointer;
  white-space: nowrap;
  display: flex;
  align-items: center;
  gap: 4px;
  transition: all var(--duration-fast) var(--ease-pixel);
}

.copy-text__btn:hover {
  border-color: var(--color-accent);
  color: var(--color-accent);
}

.copy-text__btn--done {
  background: var(--color-accent);
  color: #fff !important;
  border-color: var(--color-accent-dim);
}

@media (max-width: 520px) {
  .copy-text__row {
    flex-wrap: wrap;
  }

  .copy-text__value {
    flex: 1 1 100%;
    font-size: var(--text-base);
  }

  .copy-text__btn {
    width: 100%;
    justify-content: center;
  }
}
</style>
