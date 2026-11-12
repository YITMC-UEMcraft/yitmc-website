<template>
  <div class="timeline">
    <div v-for="(item, i) in newsItems" :key="item.id" class="timeline__item" :style="{ animationDelay: `${i * 0.15}s` }">
      <div class="timeline__marker">
        <span class="timeline__dot"></span>
        <span v-if="i < newsItems.length - 1" class="timeline__line"></span>
      </div>
      <div class="timeline__card block-inset">
        <div class="timeline__header">
          <span class="timeline__type" :class="`timeline__type--${item.type}`"><AppIcon :name="newsTypeMeta(item.type).icon" :size="14" /> {{ newsTypeMeta(item.type).label }}</span>
          <span class="timeline__date">{{ item.date }}</span>
        </div>
        <img v-if="item.image" :src="item.image" :alt="item.title" loading="lazy" class="timeline__pic" />
        <h3 class="timeline__title">
          <RouterLink :to="`/news/${item.id}`">{{ item.title }}</RouterLink>
        </h3>
        <p class="timeline__summary">{{ item.summary }}</p>
        <RouterLink :to="`/news/${item.id}`" class="timeline__more">阅读全文 →</RouterLink>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import AppIcon from '@/components/icons/AppIcon.vue'
import { newsData } from '@/data'
import { newsTypeMeta } from '@/lib/news'

const newsItems = newsData.news
</script>

<style scoped>
.timeline {
  position: relative;
}

.timeline__item {
  display: flex;
  gap: var(--space-lg);
  animation: fadeUp 0.5s var(--ease-pixel) both;
}

.timeline__marker {
  display: flex;
  flex-direction: column;
  align-items: center;
  flex-shrink: 0;
}

.timeline__dot {
  width: 16px;
  height: 16px;
  background: var(--color-accent);
  border: var(--border-width-sm) solid var(--color-accent-dim);
  flex-shrink: 0;
}

.timeline__line {
  width: 2px;
  flex: 1;
  background: var(--color-surface-light);
  margin: var(--space-xs) 0;
}

.timeline__card {
  flex: 1;
  margin-bottom: var(--space-xl);
  padding: var(--space-lg);
  background: var(--color-surface);
}

.timeline__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: var(--space-sm);
}

.timeline__type {
  font-family: var(--font-pixel);
  font-size: var(--text-xs);
  padding: var(--space-xs) var(--space-sm);
  border: var(--border-width-sm) solid;
}

.timeline__type--announcement { color: var(--color-gold); border-color: var(--color-gold-dim); }
.timeline__type--project { color: var(--color-accent); border-color: var(--color-accent-dim); }
.timeline__type--achievement { color: var(--color-gold); border-color: var(--color-gold-dim); }
.timeline__type--event { color: var(--color-water); border-color: var(--color-water); }

.timeline__date {
  font-size: var(--text-xs);
  color: var(--color-text-muted);
}

.timeline__pic {
  display: block;
  width: 100%;
  height: auto;
  object-fit: contain;
  margin-bottom: var(--space-md);
  border: var(--border-width-sm) solid var(--color-accent-dim);
  box-shadow: 4px 4px 0px rgba(0, 0, 0, 0.35);
}

@media (max-width: 640px) {
  .timeline__pic {
    width: 100%;
    height: auto;
  }
}

.timeline__title {
  font-family: var(--font-display);
  font-size: var(--text-lg);
  color: var(--color-text);
  margin-bottom: var(--space-sm);
}

.timeline__title a {
  color: inherit;
}

.timeline__title a:hover {
  color: var(--color-accent-hover);
}

.timeline__summary {
  font-size: var(--text-sm);
  color: var(--color-text-muted);
  line-height: 1.6;
}

.timeline__more {
  display: inline-block;
  margin-top: var(--space-md);
  padding-top: var(--space-md);
  border-top: var(--border-width-sm) dashed var(--color-surface-light);
  font-size: var(--text-sm);
  color: var(--color-accent);
  width: 100%;
}
</style>
