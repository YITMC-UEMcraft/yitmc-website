<template>
  <PixelBackground>
    <AppHeader />
    <main class="work-detail-page">
      <div class="work-detail-page__inner" v-if="work">
        <RouterLink to="/works" class="work-detail__back">← 返回作品展示</RouterLink>

        <article class="work-detail block-inset">
          <div class="work-detail__stage">
            <img :src="work.image" :alt="work.title" class="work-detail__image" />
          </div>

          <div class="work-detail__body">
            <div class="work-detail__meta">
              <span class="work-detail__tag">{{ categoryLabel }}</span>
              <span class="work-detail__date">
                <AppIcon name="calendar" :size="14" /> {{ work.date }}
              </span>
            </div>

            <h1 class="work-detail__title">{{ work.title }}</h1>
            <p class="work-detail__desc">{{ work.description }}</p>

            <div class="work-detail__authors">
              <AppIcon name="user" :size="16" />
              <span>{{ (work.authors || []).join('，') || 'YITMC' }}</span>
            </div>
          </div>
        </article>

        <nav class="work-detail__pager" aria-label="作品翻页">
          <RouterLink v-if="previous" :to="`/works/${previous.id}`" class="work-detail__pager-link">
            <span class="work-detail__pager-label">上一件</span>
            <span class="work-detail__pager-title">{{ previous.title }}</span>
          </RouterLink>
          <span v-else class="work-detail__pager-link is-empty">已经是第一件</span>

          <RouterLink v-if="next" :to="`/works/${next.id}`" class="work-detail__pager-link is-next">
            <span class="work-detail__pager-label">下一件</span>
            <span class="work-detail__pager-title">{{ next.title }}</span>
          </RouterLink>
          <span v-else class="work-detail__pager-link is-empty is-next">已经是最后一件</span>
        </nav>
      </div>
    </main>
    <AppFooter />
  </PixelBackground>
</template>

<script setup lang="ts">
import { computed, onMounted, watchEffect } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import PixelBackground from '@/components/layout/PixelBackground.vue'
import AppHeader from '@/components/layout/AppHeader.vue'
import AppFooter from '@/components/layout/AppFooter.vue'
import AppIcon from '@/components/icons/AppIcon.vue'
import { worksData } from '@/data'
import { workCategoryLabel } from '@/lib/works'
import { setSeo, SITE_URL } from '@/lib/seo'

const route = useRoute()
const router = useRouter()

const work = computed(() =>
  worksData.works.find((w) => String(w.id) === String(route.params.id)),
)
const categoryLabel = computed(() => workCategoryLabel(work.value?.category || ''))
const index = computed(() =>
  work.value ? worksData.works.findIndex((w) => w.id === work.value!.id) : -1,
)
const previous = computed(() =>
  index.value > 0 ? worksData.works[index.value - 1] : null,
)
const next = computed(() =>
  index.value >= 0 && index.value < worksData.works.length - 1
    ? worksData.works[index.value + 1]
    : null,
)

onMounted(() => {
  if (!work.value) router.replace('/404')
})

watchEffect(() => {
  const w = work.value
  if (!w) return
  const path = `/works/${w.id}`
  const created = /^\d{4}$/.test(w.date) ? `${w.date}-01-01` : w.date
  setSeo({
    title: `${w.title} - 作品展示 - YITMC`,
    description: w.description,
    path,
    image: w.image,
    type: 'article',
    jsonLd: {
      '@context': 'https://schema.org',
      '@type': 'CreativeWork',
      name: w.title,
      description: w.description,
      dateCreated: created,
      author: {
        '@type': 'Organization',
        name: (w.authors || []).join('，') || 'YITMC',
      },
      image: [SITE_URL + w.image],
      url: SITE_URL + path,
    },
  })
})
</script>

<style scoped>
.work-detail-page {
  min-height: calc(100vh - var(--header-height));
  padding: calc(var(--header-height) + var(--space-xl)) var(--space-lg) var(--space-section);
}

.work-detail-page__inner {
  max-width: 1000px;
  margin: 0 auto;
}

.work-detail__back {
  display: inline-block;
  font-size: var(--text-sm);
  color: var(--color-text-muted);
  margin-bottom: var(--space-md);
}

.work-detail {
  background: var(--color-surface);
  overflow: hidden;
}

.work-detail__stage {
  display: flex;
  justify-content: center;
  background: var(--color-bg);
  border-bottom: var(--border-width-sm) solid var(--color-surface-light);
}

.work-detail__image {
  width: 100%;
  max-height: 62vh;
  object-fit: contain;
}

.work-detail__body {
  padding: var(--space-xl);
}

.work-detail__meta {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: var(--space-md);
  margin-bottom: var(--space-sm);
}

.work-detail__tag {
  font-family: var(--font-pixel);
  font-size: 0.5rem;
  color: var(--color-accent);
  padding: var(--space-xs) var(--space-sm);
  border: var(--border-width-sm) solid var(--color-accent-dim);
}

.work-detail__date {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: var(--text-xs);
  color: var(--color-text-muted);
}

.work-detail__title {
  font-size: var(--text-2xl);
  color: var(--color-text);
  margin-bottom: var(--space-sm);
}

.work-detail__desc {
  color: var(--color-text-muted);
  line-height: 1.8;
  margin-bottom: var(--space-lg);
}

.work-detail__authors {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
  padding-top: var(--space-md);
  border-top: var(--border-width-sm) dashed var(--color-surface-light);
  font-size: var(--text-sm);
  color: var(--color-text);
}

.work-detail__pager {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--space-md);
  margin-top: var(--space-lg);
}

.work-detail__pager-link {
  display: flex;
  flex-direction: column;
  gap: 4px;
  padding: var(--space-md);
  background: var(--color-surface);
  border: var(--border-width-sm) solid var(--color-surface-light);
  color: var(--color-text);
}

.work-detail__pager-link.is-next {
  text-align: right;
}

.work-detail__pager-link.is-empty {
  color: var(--color-text-muted);
  font-size: var(--text-sm);
  justify-content: center;
}

.work-detail__pager-label {
  font-size: var(--text-xs);
  color: var(--color-accent-hover);
}

.work-detail__pager-title {
  font-size: var(--text-sm);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

@media (max-width: 640px) {
  .work-detail__body {
    padding: var(--space-lg);
  }

  .work-detail__pager {
    grid-template-columns: 1fr;
  }

  .work-detail__pager-link.is-next {
    text-align: left;
  }
}
</style>
