<template>
  <component
    :is="member.isPublic ? RouterLink : 'div'"
    v-bind="member.isPublic ? { to: `/profile/${member.id}` } : {}"
    class="site-member"
    :class="{ 'is-static': !member.isPublic }"
  >
    <img :src="member.avatar" :alt="member.nickname" class="site-member__avatar" loading="lazy" />
    <div class="site-member__body">
      <div class="site-member__head">
        <strong class="site-member__name">{{ member.nickname }}</strong>
        <span v-if="roleLabel" class="site-member__role">{{ roleLabel }}</span>
      </div>
      <span class="site-member__player">{{ member.playerName || '未绑定角色' }}</span>
      <p v-if="member.bio" class="site-member__bio">{{ member.bio }}</p>
      <span class="site-member__hint">{{ member.isPublic ? '查看档案 →' : '档案未公开' }}</span>
    </div>
  </component>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import type { SiteMember } from '@/lib/site-members'

const props = defineProps<{ member: SiteMember }>()

const roleLabel = computed(() => {
  if (props.member.siteRole === 'super_admin') return '超级管理员'
  if (props.member.siteRole === 'admin') return '管理员'
  return ''
})
</script>

<style scoped>
.site-member {
  display: flex;
  gap: var(--space-md);
  height: 100%;
  min-width: 0;
  padding: var(--space-md);
  background: var(--color-surface);
  border: var(--border-width-sm) solid var(--color-surface-light);
  transition: border-color 0.15s ease, transform 0.15s ease;
}

a.site-member:hover {
  border-color: var(--color-accent);
  transform: translateY(-2px);
}

.site-member__avatar {
  width: 56px;
  height: 56px;
  flex: none;
  object-fit: cover;
  image-rendering: pixelated;
  background: var(--color-bg);
  border: var(--border-width-sm) solid var(--color-accent-dim);
}

.site-member__body {
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 0;
}

.site-member__head {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: var(--space-xs);
}

.site-member__name {
  font-family: var(--font-display);
  font-size: var(--text-base);
  color: var(--color-text);
  overflow-wrap: anywhere;
}

.site-member__role {
  flex: none;
  padding: 0 6px;
  font-size: 10px;
  color: var(--color-gold);
  border: var(--border-width-sm) solid var(--color-gold-dim);
}

.site-member__player {
  font-size: var(--text-xs);
  color: var(--color-text-muted);
}

.site-member__bio {
  display: -webkit-box;
  margin: 2px 0;
  overflow: hidden;
  font-size: var(--text-xs);
  line-height: 1.5;
  color: var(--color-text-muted);
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
}

.site-member__hint {
  margin-top: auto;
  padding-top: var(--space-xs);
  font-size: 10px;
  color: var(--color-accent);
}

.site-member.is-static .site-member__hint {
  color: var(--color-text-muted);
}
</style>
