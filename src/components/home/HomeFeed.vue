<template>
  <section v-if="latestNews.length || events.length" class="home-feed">
    <div class="home-feed__inner">
      <div class="home-feed__col" data-reveal>
        <header class="home-feed__head">
          <h2 class="home-feed__title">最新动态</h2>
          <RouterLink to="/news" class="home-feed__more">全部动态 →</RouterLink>
        </header>

        <ul v-if="latestNews.length" class="home-feed__list">
          <li v-for="item in latestNews" :key="item.id" class="home-feed__item">
            <RouterLink :to="`/news/${item.id}`" class="home-feed__link">
              <span class="home-feed__meta">
                <span class="home-feed__badge">{{ newsTypeMeta(item.type).label }}</span>
                <span class="home-feed__date">{{ item.date }}</span>
              </span>
              <span class="home-feed__item-title">{{ item.title }}</span>
            </RouterLink>
          </li>
        </ul>
        <p v-else class="home-feed__empty">暂无动态</p>
      </div>

      <div class="home-feed__col" data-reveal data-reveal-delay="1">
        <header class="home-feed__head">
          <h2 class="home-feed__title">{{ hasUpcoming ? '近期活动' : '往期活动' }}</h2>
          <RouterLink to="/events" class="home-feed__more">活动中心 →</RouterLink>
        </header>

        <ul v-if="events.length" class="home-feed__list">
          <li v-for="event in events" :key="event.id" class="home-feed__item">
            <RouterLink v-if="event.link.startsWith('/')" :to="event.link" class="home-feed__link">
              <span class="home-feed__meta">
                <span class="home-feed__badge" :class="`home-feed__badge--${eventStatusMeta(event.status).className}`">
                  {{ eventStatusMeta(event.status).label }}
                </span>
                <span class="home-feed__date">{{ event.dateLabel || event.dateStart }}</span>
              </span>
              <span class="home-feed__item-title">{{ event.title }}</span>
            </RouterLink>
            <a v-else-if="event.link" :href="event.link" target="_blank" rel="noopener" class="home-feed__link">
              <span class="home-feed__meta">
                <span class="home-feed__badge" :class="`home-feed__badge--${eventStatusMeta(event.status).className}`">
                  {{ eventStatusMeta(event.status).label }}
                </span>
                <span class="home-feed__date">{{ event.dateLabel || event.dateStart }}</span>
              </span>
              <span class="home-feed__item-title">{{ event.title }}</span>
            </a>
            <span v-else class="home-feed__link is-static">
              <span class="home-feed__meta">
                <span class="home-feed__badge" :class="`home-feed__badge--${eventStatusMeta(event.status).className}`">
                  {{ eventStatusMeta(event.status).label }}
                </span>
                <span class="home-feed__date">{{ event.dateLabel || event.dateStart }}</span>
              </span>
              <span class="home-feed__item-title">{{ event.title }}</span>
            </span>
          </li>
        </ul>
        <p v-else class="home-feed__empty">暂无活动</p>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { newsData } from '@/data'
import { featuredEvents, eventStatusMeta, upcomingEvents } from '@/lib/events'
import { newsTypeMeta } from '@/lib/news'

const latestNews = computed(() => newsData.news.slice(0, 3))
const events = computed(() => featuredEvents(3))
const hasUpcoming = computed(() => upcomingEvents().length > 0)
</script>

<style scoped>
.home-feed {
  padding: var(--space-section) var(--space-lg);
}

.home-feed__inner {
  max-width: var(--max-width);
  margin: 0 auto;
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--space-xl);
}

.home-feed__col {
  background: var(--color-surface);
  border: var(--border-width-sm) solid var(--color-surface-light);
  padding: var(--space-lg);
}

.home-feed__head {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: var(--space-md);
  margin-bottom: var(--space-md);
  padding-bottom: var(--space-sm);
  border-bottom: var(--border-width-sm) solid var(--color-surface-light);
}

.home-feed__title {
  font-size: var(--text-xl);
  color: var(--color-text);
}

.home-feed__more {
  font-size: var(--text-xs);
  color: var(--color-accent);
  white-space: nowrap;
}

.home-feed__list {
  list-style: none;
  margin: 0;
  padding: 0;
}

.home-feed__item + .home-feed__item {
  border-top: var(--border-width-sm) dashed var(--color-surface-light);
}

.home-feed__link {
  display: flex;
  flex-direction: column;
  gap: var(--space-xs);
  padding: var(--space-md) 0;
  color: var(--color-text);
}

.home-feed__link.is-static {
  cursor: default;
}

.home-feed__meta {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
}

.home-feed__badge {
  font-size: var(--text-xs);
  padding: 1px var(--space-sm);
  color: var(--color-accent-hover);
  border: var(--border-width-sm) solid var(--color-accent-dim);
}

.home-feed__badge--upcoming {
  color: var(--color-water);
  border-color: var(--color-water);
}

.home-feed__badge--ongoing {
  color: var(--color-accent-hover);
  border-color: var(--color-accent);
}

.home-feed__badge--past {
  color: var(--color-text-muted);
  border-color: var(--color-surface-light);
}

.home-feed__date {
  font-size: var(--text-xs);
  color: var(--color-text-muted);
}

.home-feed__item-title {
  font-family: var(--font-display);
  font-size: var(--text-lg);
  line-height: 1.4;
  transition: color var(--duration-fast) var(--ease-out);
}

.home-feed__link:hover .home-feed__item-title {
  color: var(--color-accent-hover);
}

.home-feed__empty {
  color: var(--color-text-muted);
  font-size: var(--text-sm);
  padding: var(--space-lg) 0;
}

@media (max-width: 900px) {
  .home-feed__inner {
    grid-template-columns: 1fr;
  }
}
</style>
