<template>
  <article class="event-card block-inset">
    <div class="event-card__cover">
      <img v-if="event.cover" :src="event.cover" :alt="event.title" loading="lazy" />
      <div v-else class="event-card__placeholder bg-grid">
        <AppIcon name="calendar" :size="28" />
        <span>{{ event.dateLabel || event.dateStart }}</span>
      </div>
      <span class="event-card__status" :class="`event-card__status--${status.className}`">
        {{ status.label }}
      </span>
    </div>

    <div class="event-card__body">
      <div class="event-card__meta">
        <span class="event-card__date">
          <AppIcon name="calendar" :size="14" />
          {{ event.dateLabel || event.dateStart }}<template v-if="event.dateEnd && event.dateEnd !== event.dateStart"> — {{ event.dateEnd }}</template>
        </span>
        <span v-if="event.location" class="event-card__location">
          <AppIcon name="map" :size="14" /> {{ event.location }}
        </span>
      </div>

      <h3 class="event-card__title">{{ event.title }}</h3>
      <p v-if="event.summary" class="event-card__summary">{{ event.summary }}</p>

      <RouterLink v-if="internalLink" :to="event.link" class="event-card__cta">查看详情 →</RouterLink>
      <a v-else-if="event.link" :href="event.link" target="_blank" rel="noopener" class="event-card__cta">查看详情 →</a>
      <span v-else class="event-card__cta is-muted">活动已结束</span>
    </div>
  </article>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import AppIcon from '@/components/icons/AppIcon.vue'
import type { ClubEvent } from '@/data'
import { eventStatusMeta } from '@/lib/events'

const props = defineProps<{ event: ClubEvent }>()

const status = computed(() => eventStatusMeta(props.event.status))
const internalLink = computed(() => props.event.link.startsWith('/'))
</script>

<style scoped>
.event-card {
  background: var(--color-surface);
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.event-card__cover {
  position: relative;
  aspect-ratio: 16 / 9;
  background: var(--color-bg);
  border-bottom: var(--border-width-sm) solid var(--color-surface-light);
}

.event-card__cover img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.event-card__placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: var(--space-sm);
  color: var(--color-accent);
  font-family: var(--font-pixel);
  font-size: var(--text-xs);
}

.event-card__status {
  position: absolute;
  top: var(--space-sm);
  left: var(--space-sm);
  padding: 2px var(--space-sm);
  font-size: var(--text-xs);
  border: var(--border-width-sm) solid;
  background: var(--color-overlay);
}

.event-card__status--upcoming {
  color: var(--color-water);
  border-color: var(--color-water);
}

.event-card__status--ongoing {
  color: var(--color-accent-hover);
  border-color: var(--color-accent);
}

.event-card__status--past {
  color: var(--color-text-muted);
  border-color: var(--color-surface-light);
}

.event-card__body {
  padding: var(--space-lg);
  display: flex;
  flex-direction: column;
  flex: 1;
}

.event-card__meta {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-md);
  margin-bottom: var(--space-sm);
  font-size: var(--text-xs);
  color: var(--color-text-muted);
}

.event-card__date,
.event-card__location {
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

.event-card__title {
  font-size: var(--text-lg);
  color: var(--color-text);
  margin-bottom: var(--space-sm);
}

.event-card__summary {
  font-size: var(--text-sm);
  color: var(--color-text-muted);
  line-height: 1.7;
  margin-bottom: var(--space-md);
}

.event-card__cta {
  margin-top: auto;
  font-size: var(--text-sm);
  color: var(--color-accent);
}

.event-card__cta.is-muted {
  color: var(--color-text-muted);
}
</style>
