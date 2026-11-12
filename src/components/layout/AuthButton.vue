<template>
  <RouterLink
    v-if="user"
    to="/account"
    class="auth-button auth-button--user"
    :title="user.nickname"
    :aria-label="`${user.nickname} 的个人档案`"
  >
    <img :src="user.avatar" :alt="user.nickname" />
  </RouterLink>
  <a
    v-else
    :href="loginHref('/account')"
    class="auth-button"
    :title="configured ? '使用皮肤站账号登录' : '登录未配置'"
    aria-label="使用皮肤站账号登录"
  >
    <AppIcon name="user" :size="18" />
  </a>
</template>

<script setup lang="ts">
import AppIcon from '@/components/icons/AppIcon.vue'
import { loginHref } from '@/lib/auth'
import { useAuth } from '@/composables/useAuth'

const { user, configured } = useAuth()
</script>

<style scoped>
.auth-button {
  width: 36px;
  height: 36px;
  display: grid;
  place-items: center;
  flex: none;
  overflow: hidden;
  background: transparent;
  border: var(--border-width-sm) solid var(--color-surface-light);
  color: var(--color-text-muted);
  transition: color var(--duration-fast) var(--ease-pixel),
              border-color var(--duration-fast) var(--ease-pixel);
}

.auth-button:hover {
  color: var(--color-accent);
  border-color: var(--color-accent);
}

.auth-button--user {
  border-color: var(--color-accent-dim);
}

.auth-button img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  image-rendering: pixelated;
}
</style>
