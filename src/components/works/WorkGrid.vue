<template>
  <div class="work-grid stagger-children">
    <RouterLink v-for="work in filteredWorks" :key="work.id" :to="`/works/${work.id}`" class="work-grid__card">
      <PixelCard>
        <template #image>
          <img :src="work.image" :alt="work.title" loading="lazy" />
        </template>
        <h3 class="work-grid__title">{{ work.title }}</h3>
        <p class="work-grid__desc">{{ work.description }}</p>
        <div class="work-grid__meta">
          <span class="work-grid__tag">{{ getCategoryLabel(work.category) }}</span>
          <span class="work-grid__date">{{ work.date }}</span>
        </div>
      </PixelCard>
    </RouterLink>
  </div>

  <p v-if="filteredWorks.length === 0" class="work-grid__empty">
    暂无该分类的作品，敬请期待！
  </p>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import PixelCard from '@/components/ui/PixelCard.vue'
import { worksData } from '@/data'

const props = defineProps<{ activeCategory: string }>()

const filteredWorks = computed(() => {
  if (props.activeCategory === 'all') return worksData.works
  return worksData.works.filter(w => w.category === props.activeCategory)
})

function getCategoryLabel(key: string): string {
  return worksData.categories.find(c => c.key === key)?.label || key
}
</script>

<style scoped>
.work-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: var(--space-lg);
}

.work-grid__card {
  display: block;
  color: inherit;
}

.work-grid__title {
  font-family: var(--font-display);
  font-size: var(--text-lg);
  color: var(--color-text);
  margin-bottom: var(--space-sm);
}

.work-grid__desc {
  font-size: var(--text-sm);
  color: var(--color-text-muted);
  margin-bottom: var(--space-md);
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.work-grid__meta {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.work-grid__tag {
  font-family: var(--font-pixel);
  font-size: 0.5rem;
  color: var(--color-accent);
  padding: var(--space-xs) var(--space-sm);
  border: var(--border-width-sm) solid var(--color-accent-dim);
}

.work-grid__date {
  font-size: var(--text-xs);
  color: var(--color-text-muted);
}

.work-grid__empty {
  text-align: center;
  font-family: var(--font-display);
  font-size: var(--text-lg);
  color: var(--color-text-muted);
  padding: var(--space-3xl);
}
</style>
