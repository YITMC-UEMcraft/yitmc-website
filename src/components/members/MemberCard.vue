<template>
  <div class="member-card block-inset hover-lift">
    <div class="member-card__avatar">
      <img v-if="member.avatar" :src="member.avatar" :alt="member.name" class="member-card__avatar-img" />
      <span v-else class="member-card__avatar-placeholder">{{ initial }}</span>
    </div>
    <h3 class="member-card__name">{{ member.name }}</h3>
    <span v-if="member.role" class="member-card__role">{{ member.role }}</span>
    <p v-if="member.description" class="member-card__desc">{{ member.description }}</p>
    <span v-if="member.grade" class="member-card__grade">{{ member.grade }}</span>
    <RouterLink v-if="member.profileId" :to="`/profile/${member.profileId}`" class="member-card__profile">
      个人主页 →
    </RouterLink>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
  member: {
    id: number
    name: string
    role: string
    avatar: string
    description: string
    grade: string
    profileId?: number
  }
}>()

const initial = computed(() => props.member.name.charAt(0))
</script>

<style scoped>
.member-card {
  padding: var(--space-xl);
  text-align: center;
  background: var(--color-surface);
}

.member-card__avatar {
  width: 80px;
  height: 80px;
  margin: 0 auto var(--space-md);
  background: var(--color-surface-light);
  border: var(--border-width) solid var(--color-brown);
  display: flex;
  align-items: center;
  justify-content: center;
}

.member-card__avatar-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  border-radius: 0;
}

.member-card__avatar-placeholder {
  font-family: var(--font-pixel);
  font-size: var(--text-2xl);
  color: var(--color-accent);
}

.member-card__name {
  font-family: var(--font-display);
  font-size: var(--text-lg);
  color: var(--color-text);
  margin-bottom: var(--space-xs);
}

.member-card__role {
  font-family: var(--font-pixel);
  font-size: var(--text-xs);
  color: var(--color-accent);
  padding: var(--space-xs) var(--space-md);
  border: var(--border-width-sm) solid var(--color-accent-dim);
  display: inline-block;
  margin-bottom: var(--space-md);
}

.member-card__desc {
  font-size: var(--text-sm);
  color: var(--color-text-muted);
  margin-bottom: var(--space-md);
}

.member-card__grade {
  display: block;
  font-size: var(--text-xs);
  color: var(--color-brown);
}

.member-card__profile {
  display: inline-block;
  margin-top: var(--space-md);
  padding: var(--space-xs) var(--space-md);
  font-size: var(--text-xs);
  color: var(--color-accent);
  border: var(--border-width-sm) solid var(--color-accent-dim);
}

.member-card__profile:hover {
  border-color: var(--color-accent);
  background: rgba(92, 154, 59, 0.1);
}
</style>
