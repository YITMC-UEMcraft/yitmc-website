<template>
  <PixelBackground>
    <AppHeader />
    <main class="events-page">
      <section class="page-hero">
        <h1 class="page-hero__title" data-reveal>活动中心</h1>
        <p class="page-hero__subtitle" data-reveal data-reveal-delay="1">交流会、建筑赛与服务器活动，都在这里</p>
        <BlockDivider variant="grass" />
      </section>

      <div class="events-page__inner">
        <section class="events-section">
          <div class="events-section__head" data-reveal>
            <h2 class="events-section__title">近期活动</h2>
            <span class="events-section__count">{{ upcoming.length }} 场</span>
          </div>
          <div v-if="upcoming.length" class="events-grid">
            <EventCard v-for="event in upcoming" :key="event.id" :event="event" data-reveal />
          </div>
          <p v-else class="events-empty block-inset" data-reveal>
            近期还没有排期，新的活动会在 QQ 群和「社团动态」里第一时间公布。
          </p>
        </section>

        <section class="events-section">
          <div class="events-section__head" data-reveal>
            <h2 class="events-section__title">往期回顾</h2>
            <span class="events-section__count">{{ past.length }} 场</span>
          </div>
          <div v-if="past.length" class="events-grid">
            <EventCard v-for="event in past" :key="event.id" :event="event" data-reveal />
          </div>
          <p v-else class="events-empty block-inset" data-reveal>
            还没有往期活动记录。
          </p>
        </section>

        <div class="events-page__cta block-inset" data-reveal>
          <div>
            <h2>想一起办活动？</h2>
            <p>加入 QQ 群，和社团一起策划下一次建筑赛、交流会或服务器活动。</p>
          </div>
          <PixelButton to="/join" variant="primary">加入我们</PixelButton>
        </div>
      </div>
    </main>
    <AppFooter />
  </PixelBackground>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import PixelBackground from '@/components/layout/PixelBackground.vue'
import AppHeader from '@/components/layout/AppHeader.vue'
import AppFooter from '@/components/layout/AppFooter.vue'
import BlockDivider from '@/components/ui/BlockDivider.vue'
import PixelButton from '@/components/ui/PixelButton.vue'
import EventCard from '@/components/events/EventCard.vue'
import { pastEvents, upcomingEvents } from '@/lib/events'

const upcoming = computed(() => upcomingEvents())
const past = computed(() => pastEvents())
</script>

<style scoped>
.events-page {
  padding-top: var(--header-height);
}

.page-hero {
  text-align: center;
  padding: var(--space-3xl) var(--space-lg) 0;
}

.page-hero__title {
  font-size: var(--text-hero);
  color: var(--color-accent);
  text-shadow: 3px 3px 0 rgba(0, 0, 0, 0.5);
  margin-bottom: var(--space-sm);
}

.page-hero__subtitle {
  font-size: var(--text-base);
  color: var(--color-text-muted);
  margin-bottom: var(--space-xl);
}

.events-page__inner {
  max-width: var(--max-width);
  margin: 0 auto;
  padding: var(--space-2xl) var(--space-lg) var(--space-section);
}

.events-section + .events-section {
  margin-top: var(--space-3xl);
}

.events-section__head {
  display: flex;
  align-items: baseline;
  gap: var(--space-md);
  margin-bottom: var(--space-lg);
  padding-bottom: var(--space-sm);
  border-bottom: var(--border-width-sm) solid var(--color-surface-light);
}

.events-section__title {
  font-size: var(--text-xl);
  color: var(--color-text);
}

.events-section__count {
  font-size: var(--text-xs);
  color: var(--color-text-muted);
}

.events-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: var(--space-lg);
}

.events-empty {
  padding: var(--space-xl);
  color: var(--color-text-muted);
  text-align: center;
  background: var(--color-surface);
}

.events-page__cta {
  margin-top: var(--space-3xl);
  padding: var(--space-xl);
  background: var(--color-surface);
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-lg);
}

.events-page__cta h2 {
  font-size: var(--text-xl);
  color: var(--color-text);
  margin-bottom: var(--space-xs);
}

.events-page__cta p {
  color: var(--color-text-muted);
  font-size: var(--text-sm);
}

@media (max-width: 640px) {
  .events-page__cta {
    flex-direction: column;
    align-items: flex-start;
  }
}
</style>
