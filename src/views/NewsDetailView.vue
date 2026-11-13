<template>
  <PixelBackground>
    <AppHeader />
    <main class="news-detail-page">
      <div class="news-detail-page__inner" v-if="item">
        <RouterLink to="/news" class="news-detail__back">← 返回社团动态</RouterLink>

        <article class="news-detail block-inset">
          <img v-if="item.image" :src="item.image" :alt="item.title" class="news-detail__cover" />

          <div class="news-detail__meta">
            <span class="news-detail__type">
              <AppIcon :name="typeMeta.icon" :size="14" /> {{ typeMeta.label }}
            </span>
            <span class="news-detail__date">
              <AppIcon name="calendar" :size="14" /> {{ item.date }}
            </span>
            <span v-if="viewCount !== null" class="news-detail__views">
              <AppIcon name="eye" :size="14" /> {{ formatViews(viewCount) }}
            </span>
          </div>

          <h1 class="news-detail__title">{{ item.title }}</h1>
          <p v-if="item.summary" class="news-detail__summary">{{ item.summary }}</p>

          <BlockDivider variant="grass" />

          <div class="news-detail__content">{{ item.content || item.summary }}</div>
        </article>

        <nav class="news-detail__pager" aria-label="动态翻页">
          <RouterLink v-if="newer" :to="`/news/${newer.id}`" class="news-detail__pager-link">
            <span class="news-detail__pager-label">上一条</span>
            <span class="news-detail__pager-title">{{ newer.title }}</span>
          </RouterLink>
          <span v-else class="news-detail__pager-link is-empty">已经是最新一条</span>

          <RouterLink v-if="older" :to="`/news/${older.id}`" class="news-detail__pager-link is-next">
            <span class="news-detail__pager-label">下一条</span>
            <span class="news-detail__pager-title">{{ older.title }}</span>
          </RouterLink>
          <span v-else class="news-detail__pager-link is-empty is-next">已经是最早一条</span>
        </nav>
      </div>
    </main>
    <AppFooter />
  </PixelBackground>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch, watchEffect } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import PixelBackground from '@/components/layout/PixelBackground.vue'
import AppHeader from '@/components/layout/AppHeader.vue'
import AppFooter from '@/components/layout/AppFooter.vue'
import BlockDivider from '@/components/ui/BlockDivider.vue'
import AppIcon from '@/components/icons/AppIcon.vue'
import { newsTypeMeta, sortedNews } from '@/lib/news'
import { setSeo, SITE_URL } from '@/lib/seo'
import { formatViews, recordNewsView } from '@/lib/views'

const route = useRoute()
const router = useRouter()

const newsItems = computed(() => sortedNews())
const item = computed(() =>
  newsItems.value.find((n) => String(n.id) === String(route.params.id)),
)
const typeMeta = computed(() => newsTypeMeta(item.value?.type || ''))
const viewCount = ref<number | null>(null)

watch(item, (news) => {
  if (!news) return
  viewCount.value = null
  recordNewsView(news.id)
    .then((count) => { viewCount.value = count })
    .catch(() => { /* 浏览量失败不影响阅读 */ })
}, { immediate: true })
const index = computed(() =>
  item.value ? newsItems.value.findIndex((n) => n.id === item.value!.id) : -1,
)
const newer = computed(() =>
  index.value > 0 ? newsItems.value[index.value - 1] : null,
)
const older = computed(() =>
  index.value >= 0 && index.value < newsItems.value.length - 1
    ? newsItems.value[index.value + 1]
    : null,
)

function isoDate(date: string): string {
  if (/^\d{4}-\d{2}-\d{2}$/.test(date)) return `${date}T00:00:00+08:00`
  if (/^\d{4}-\d{2}$/.test(date)) return `${date}-01T00:00:00+08:00`
  if (/^\d{4}$/.test(date)) return `${date}-01-01T00:00:00+08:00`
  return date
}

onMounted(() => {
  if (!item.value) router.replace('/404')
})

watchEffect(() => {
  const n = item.value
  if (!n) return
  const path = `/news/${n.id}`
  setSeo({
    title: `${n.title} - YITMC`,
    description: n.summary || n.content.slice(0, 120),
    path,
    image: n.image,
    type: 'article',
    jsonLd: {
      '@context': 'https://schema.org',
      '@type': 'NewsArticle',
      headline: n.title,
      description: n.summary || n.content.slice(0, 120),
      datePublished: isoDate(n.date),
      author: { '@type': 'Organization', name: 'YITMC' },
      publisher: {
        '@type': 'Organization',
        name: 'YITMC',
        logo: { '@type': 'ImageObject', url: SITE_URL + '/logo/logo.png' },
      },
      mainEntityOfPage: { '@type': 'WebPage', '@id': SITE_URL + path },
      ...(n.image ? { image: [SITE_URL + n.image] } : {}),
    },
  })
})
</script>

<style scoped>
.news-detail-page {
  min-height: calc(100vh - var(--header-height));
  padding: calc(var(--header-height) + var(--space-xl)) var(--space-lg) var(--space-section);
}

.news-detail-page__inner {
  max-width: 800px;
  margin: 0 auto;
}

.news-detail__back {
  display: inline-block;
  font-size: var(--text-sm);
  color: var(--color-text-muted);
  margin-bottom: var(--space-md);
}

.news-detail {
  padding: var(--space-xl);
  background: var(--color-surface);
}

.news-detail__cover {
  width: 100%;
  max-height: 420px;
  object-fit: cover;
  border: var(--border-width-sm) solid var(--color-surface-light);
  margin-bottom: var(--space-lg);
}

.news-detail__meta {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: var(--space-md);
  margin-bottom: var(--space-sm);
}

.news-detail__type,
.news-detail__date,
.news-detail__views {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: var(--text-xs);
  color: var(--color-text-muted);
}

.news-detail__type {
  padding: 2px var(--space-sm);
  color: var(--color-accent-hover);
  background: rgba(92, 154, 59, 0.12);
  border: var(--border-width-sm) solid var(--color-accent-dim);
}

.news-detail__title {
  font-size: var(--text-2xl);
  color: var(--color-text);
  margin-bottom: var(--space-sm);
}

.news-detail__summary {
  color: var(--color-text-muted);
  margin-bottom: var(--space-lg);
}

.news-detail__content {
  white-space: pre-line;
  line-height: 1.9;
  color: var(--color-text);
}

.news-detail__pager {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--space-md);
  margin-top: var(--space-lg);
}

.news-detail__pager-link {
  display: flex;
  flex-direction: column;
  gap: 4px;
  padding: var(--space-md);
  background: var(--color-surface);
  border: var(--border-width-sm) solid var(--color-surface-light);
  color: var(--color-text);
}

.news-detail__pager-link.is-next {
  text-align: right;
}

.news-detail__pager-link.is-empty {
  color: var(--color-text-muted);
  font-size: var(--text-sm);
  justify-content: center;
}

.news-detail__pager-label {
  font-size: var(--text-xs);
  color: var(--color-accent-hover);
}

.news-detail__pager-title {
  font-size: var(--text-sm);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

@media (max-width: 640px) {
  .news-detail {
    padding: var(--space-lg);
  }

  .news-detail__pager {
    grid-template-columns: 1fr;
  }

  .news-detail__pager-link.is-next {
    text-align: left;
  }
}
</style>
