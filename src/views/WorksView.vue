<template>
  <PixelBackground>
    <AppHeader />
    <main class="works-page">
      <section class="page-hero">
        <h1 class="page-hero__title" data-reveal>作品展示</h1>
        <p class="page-hero__subtitle" data-reveal data-reveal-delay="1">燕理校园复刻工程及社团创作作品</p>
        <BlockDivider variant="grass" />
      </section>

      <div class="works-page__inner" data-reveal data-reveal-delay="2">
        <FilterTabs v-model="activeCategory" :categories="worksData.categories" />
        <WorkGrid :active-category="activeCategory" />
      </div>
    </main>
    <AppFooter />
  </PixelBackground>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import PixelBackground from '@/components/layout/PixelBackground.vue'
import AppHeader from '@/components/layout/AppHeader.vue'
import AppFooter from '@/components/layout/AppFooter.vue'
import BlockDivider from '@/components/ui/BlockDivider.vue'
import FilterTabs from '@/components/works/FilterTabs.vue'
import WorkGrid from '@/components/works/WorkGrid.vue'
import { worksData } from '@/data'

const activeCategory = ref('all')
</script>

<style scoped>
.works-page {
  padding-top: var(--header-height);
}

.page-hero {
  text-align: center;
  padding: var(--space-3xl) var(--space-lg) 0;
}

.page-hero__title {
  font-family: var(--font-pixel);
  font-size: var(--text-2xl);
  color: var(--color-accent);
  text-shadow: 3px 3px 0px rgba(0, 0, 0, 0.5);
  margin-bottom: var(--space-sm);
}

.page-hero__subtitle {
  font-size: var(--text-base);
  color: var(--color-text-muted);
  margin-bottom: var(--space-lg);
}

.works-page__inner {
  max-width: var(--max-width);
  margin: 0 auto;
  padding: var(--space-2xl) var(--space-lg) var(--space-section);
}
</style>
