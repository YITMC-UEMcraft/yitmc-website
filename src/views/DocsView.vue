<template>
  <PixelBackground>
    <AppHeader />
    <main class="docs-page">
      <header class="docs-hero">
        <h1 class="docs-hero__title">指南与文档</h1>
        <p class="docs-hero__subtitle">新人指南、服务器连接与皮肤站使用</p>
        <BlockDivider variant="dirt" />
      </header>

      <div v-if="current" class="docs-layout">
        <aside class="docs-sidebar" :class="{ 'is-open': sidebarOpen }">
          <button type="button" class="docs-sidebar__toggle" :aria-expanded="sidebarOpen" @click="sidebarOpen = !sidebarOpen">
            {{ sidebarOpen ? '收起目录' : '展开文档目录' }}
          </button>
          <nav class="docs-sidebar__nav" aria-label="文档目录">
            <div v-for="group in groupedDocs" :key="group.category" class="docs-group">
              <p class="docs-group__title">{{ group.category }}</p>
              <RouterLink
                v-for="doc in group.docs"
                :key="doc.id"
                :to="`/docs/${doc.id}`"
                class="docs-sidebar__link"
                :class="{ 'is-active': doc.id === currentId }"
                @click="sidebarOpen = false"
              >{{ doc.title }}</RouterLink>
            </div>
          </nav>
        </aside>

        <article v-if="current" class="docs-article block-inset">
          <div class="docs-article__meta">
            <span class="docs-article__category">{{ current.category || '未分类' }}</span>
            <span v-if="current.updatedAt" class="docs-article__updated">更新于 {{ current.updatedAt }}</span>
          </div>
          <h2 class="docs-article__title">{{ current.title }}</h2>
          <div class="docs-article__body" @click="onArticleClick" v-html="rendered.html"></div>

          <nav class="docs-pager" aria-label="文档翻页">
            <RouterLink v-if="previous" :to="`/docs/${previous.id}`" class="docs-pager__link">
              <span class="docs-pager__label">上一篇</span>
              <span class="docs-pager__title">{{ previous.title }}</span>
            </RouterLink>
            <span v-else class="docs-pager__link is-empty">已经是第一篇</span>

            <RouterLink v-if="next" :to="`/docs/${next.id}`" class="docs-pager__link is-next">
              <span class="docs-pager__label">下一篇</span>
              <span class="docs-pager__title">{{ next.title }}</span>
            </RouterLink>
            <span v-else class="docs-pager__link is-empty is-next">已经是最后一篇</span>
          </nav>
        </article>

        <aside v-if="current && headings.length" class="docs-outline">
          <p class="docs-outline__title">本页目录</p>
          <a
            v-for="heading in headings"
            :key="heading.id"
            :href="`#${heading.id}`"
            class="docs-outline__link"
            :class="[`is-level-${heading.level}`, { 'is-active': heading.id === activeHeading }]"
            @click.prevent="jumpTo(heading.id)"
          >{{ heading.text }}</a>
        </aside>
      </div>

      <div v-else-if="!docs.length" class="docs-empty block-inset">
        <AppIcon name="book-open" :size="28" />
        <p>还没有文档，请在管理后台「指南文档」中添加。</p>
      </div>
    </main>
    <AppFooter />
  </PixelBackground>
</template>

<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watchEffect } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import PixelBackground from '@/components/layout/PixelBackground.vue'
import AppHeader from '@/components/layout/AppHeader.vue'
import AppFooter from '@/components/layout/AppFooter.vue'
import BlockDivider from '@/components/ui/BlockDivider.vue'
import AppIcon from '@/components/icons/AppIcon.vue'
import { docsData, type ClubDoc } from '@/data'
import { renderMarkdown } from '@/lib/markdown'
import { setSeo } from '@/lib/seo'

const route = useRoute()
const router = useRouter()
const sidebarOpen = ref(false)
const activeHeading = ref('')

const docs = computed<ClubDoc[]>(() =>
  [...docsData.docs].sort((a, b) => (a.order || 0) - (b.order || 0)),
)

const groupedDocs = computed(() => {
  const groups: { category: string; docs: ClubDoc[] }[] = []
  for (const doc of docs.value) {
    const category = doc.category || '未分类'
    let group = groups.find((g) => g.category === category)
    if (!group) {
      group = { category, docs: [] }
      groups.push(group)
    }
    group.docs.push(doc)
  }
  return groups
})

const currentId = computed(() => String(route.params.id || ''))
const current = computed(() => docs.value.find((d) => d.id === currentId.value) || null)
const currentIndex = computed(() => docs.value.findIndex((d) => d.id === currentId.value))
const previous = computed(() => (currentIndex.value > 0 ? docs.value[currentIndex.value - 1] : null))
const next = computed(() =>
  currentIndex.value >= 0 && currentIndex.value < docs.value.length - 1
    ? docs.value[currentIndex.value + 1]
    : null,
)

const rendered = computed(() =>
  current.value ? renderMarkdown(current.value.content) : { html: '', headings: [] },
)
const headings = computed(() => rendered.value.headings)

function stripMarkdown(text: string): string {
  return text
    .replace(/```[\s\S]*?```/g, ' ')
    .replace(/[#>*`_[\]()!|~-]/g, ' ')
    .replace(/\s+/g, ' ')
    .trim()
}

watchEffect(() => {
  const doc = current.value
  if (!doc) return
  setSeo({
    title: `${doc.title} - 指南与文档 - YITMC`,
    description: stripMarkdown(doc.content).slice(0, 120),
    path: `/docs/${doc.id}`,
    type: 'article',
    jsonLd: {
      '@context': 'https://schema.org',
      '@type': 'TechArticle',
      headline: doc.title,
      description: stripMarkdown(doc.content).slice(0, 120),
      ...(doc.updatedAt ? { dateModified: doc.updatedAt } : {}),
      author: { '@type': 'Organization', name: 'YITMC' },
    },
  })
})

function jumpTo(id: string) {
  const el = document.getElementById(id)
  if (!el) return
  const top = el.getBoundingClientRect().top + window.scrollY - 88
  window.scrollTo({ top, behavior: 'smooth' })
  activeHeading.value = id
  history.replaceState(null, '', `#${id}`)
}

function onArticleClick(event: MouseEvent) {
  const target = event.target as HTMLElement | null
  const anchor = target?.closest('a')
  if (!anchor) return
  const href = anchor.getAttribute('href') || ''
  if (href.startsWith('/') && !href.startsWith('//')) {
    event.preventDefault()
    router.push(href)
  }
}

let rafId = 0
function onScroll() {
  if (rafId) return
  rafId = requestAnimationFrame(() => {
    rafId = 0
    const offset = 120
    let active = headings.value[0]?.id || ''
    for (const heading of headings.value) {
      const el = document.getElementById(heading.id)
      if (el && el.getBoundingClientRect().top <= offset) active = heading.id
    }
    activeHeading.value = active
  })
}

onMounted(() => {
  window.addEventListener('scroll', onScroll, { passive: true })
  if (!route.params.id && docs.value.length) {
    router.replace(`/docs/${docs.value[0].id}`)
    return
  }
  if (route.params.id && !current.value) {
    router.replace('/404')
    return
  }
  onScroll()
})

onBeforeUnmount(() => {
  window.removeEventListener('scroll', onScroll)
  if (rafId) cancelAnimationFrame(rafId)
})

watchEffect(() => {
  // 切换文档后重置高亮，并等 DOM 更新后重新计算
  if (!current.value) return
  activeHeading.value = headings.value[0]?.id || ''
  nextTick(onScroll)
})
</script>

<style scoped>
.docs-page {
  padding-top: var(--header-height);
}

.docs-hero {
  text-align: center;
  padding: var(--space-3xl) var(--space-lg) 0;
}

.docs-hero__title {
  font-size: var(--text-hero);
  color: var(--color-accent);
  text-shadow: 3px 3px 0 rgba(0, 0, 0, 0.5);
  margin-bottom: var(--space-sm);
}

.docs-hero__subtitle {
  font-size: var(--text-base);
  color: var(--color-text-muted);
  margin-bottom: var(--space-xl);
}

.docs-layout {
  max-width: var(--max-width);
  margin: 0 auto;
  padding: var(--space-2xl) var(--space-lg) var(--space-section);
  display: grid;
  grid-template-columns: 220px minmax(0, 1fr) 180px;
  gap: var(--space-xl);
  align-items: start;
}

.docs-sidebar {
  position: sticky;
  top: calc(var(--header-height) + var(--space-lg));
}

.docs-sidebar__toggle {
  display: none;
}

.docs-group + .docs-group {
  margin-top: var(--space-lg);
}

.docs-group__title {
  font-family: var(--font-display);
  font-size: var(--text-xs);
  letter-spacing: 0.1em;
  color: var(--color-accent);
  margin-bottom: var(--space-sm);
}

.docs-sidebar__link {
  display: block;
  padding: var(--space-xs) var(--space-sm);
  font-size: var(--text-sm);
  color: var(--color-text-muted);
  border-left: 2px solid transparent;
}

.docs-sidebar__link:hover,
.docs-sidebar__link.is-active {
  color: var(--color-text);
  border-left-color: var(--color-accent);
  background: rgba(92, 154, 59, 0.08);
}

.docs-article {
  padding: var(--space-xl);
  background: var(--color-surface);
  min-width: 0;
}

.docs-article__meta {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-md);
  margin-bottom: var(--space-sm);
  font-size: var(--text-xs);
  color: var(--color-text-muted);
}

.docs-article__category {
  color: var(--color-accent-hover);
  padding: 1px var(--space-sm);
  border: var(--border-width-sm) solid var(--color-accent-dim);
}

.docs-article__title {
  font-size: var(--text-2xl);
  color: var(--color-text);
  margin-bottom: var(--space-lg);
  padding-bottom: var(--space-sm);
  border-bottom: var(--border-width-sm) solid var(--color-surface-light);
}

.docs-article__body {
  line-height: 1.9;
  color: var(--color-text);
  overflow-wrap: break-word;
}

/* 文档标题已单独展示，正文里的 h1 不再重复渲染 */
.docs-article__body :deep(h1) {
  display: none;
}

.docs-article__body :deep(h2),
.docs-article__body :deep(h3) {
  font-family: var(--font-display);
  color: var(--color-text);
  scroll-margin-top: 88px;
}

.docs-article__body :deep(h2) {
  font-size: var(--text-xl);
  margin: var(--space-xl) 0 var(--space-md);
  padding-bottom: var(--space-xs);
  border-bottom: var(--border-width-sm) solid var(--color-surface-light);
}

.docs-article__body :deep(h3) {
  font-size: var(--text-lg);
  margin: var(--space-lg) 0 var(--space-sm);
}

.docs-article__body :deep(p),
.docs-article__body :deep(ul),
.docs-article__body :deep(ol),
.docs-article__body :deep(blockquote),
.docs-article__body :deep(pre),
.docs-article__body :deep(table) {
  margin-bottom: var(--space-md);
}

.docs-article__body :deep(ul),
.docs-article__body :deep(ol) {
  padding-left: 1.5em;
}

.docs-article__body :deep(li + li) {
  margin-top: var(--space-xs);
}

.docs-article__body :deep(a) {
  color: var(--color-accent);
  text-decoration: underline;
}

.docs-article__body :deep(code) {
  font-family: var(--font-body);
  font-size: 0.9em;
  padding: 1px 4px;
  background: var(--color-bg);
  border: 1px solid var(--color-surface-light);
}

.docs-article__body :deep(pre) {
  padding: var(--space-md);
  background: var(--color-bg);
  border: var(--border-width-sm) solid var(--color-surface-light);
  overflow-x: auto;
}

.docs-article__body :deep(pre code) {
  padding: 0;
  border: 0;
  background: transparent;
}

.docs-article__body :deep(blockquote) {
  padding: var(--space-sm) var(--space-md);
  border-left: 4px solid var(--color-gold);
  background: rgba(218, 165, 32, 0.08);
  color: var(--color-text-muted);
}

.docs-article__body :deep(table) {
  width: 100%;
  border-collapse: collapse;
  display: block;
  overflow-x: auto;
}

.docs-article__body :deep(th),
.docs-article__body :deep(td) {
  border: var(--border-width-sm) solid var(--color-surface-light);
  padding: var(--space-xs) var(--space-sm);
  text-align: left;
}

.docs-article__body :deep(th) {
  background: var(--color-surface-light);
  color: var(--color-text);
}

.docs-article__body :deep(img) {
  max-width: 100%;
  height: auto;
  border: var(--border-width-sm) solid var(--color-surface-light);
}

.docs-pager {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--space-md);
  margin-top: var(--space-xl);
  padding-top: var(--space-lg);
  border-top: var(--border-width-sm) solid var(--color-surface-light);
}

.docs-pager__link {
  display: flex;
  flex-direction: column;
  gap: 2px;
  padding: var(--space-sm);
  border: var(--border-width-sm) solid var(--color-surface-light);
  color: var(--color-text);
}

.docs-pager__link.is-next {
  text-align: right;
}

.docs-pager__link.is-empty {
  color: var(--color-text-muted);
  font-size: var(--text-xs);
  justify-content: center;
}

.docs-pager__label {
  font-size: var(--text-xs);
  color: var(--color-accent-hover);
}

.docs-pager__title {
  font-size: var(--text-sm);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.docs-outline {
  position: sticky;
  top: calc(var(--header-height) + var(--space-lg));
  font-size: var(--text-xs);
  max-height: calc(100vh - var(--header-height) - var(--space-2xl));
  overflow-y: auto;
}

.docs-outline__title {
  font-family: var(--font-display);
  color: var(--color-accent);
  margin-bottom: var(--space-sm);
}

.docs-outline__link {
  display: block;
  padding: 3px 0;
  color: var(--color-text-muted);
  border-left: 2px solid transparent;
  padding-left: var(--space-sm);
}

.docs-outline__link.is-level-3 {
  padding-left: var(--space-lg);
}

.docs-outline__link:hover,
.docs-outline__link.is-active {
  color: var(--color-text);
  border-left-color: var(--color-accent);
}

.docs-empty {
  max-width: 640px;
  margin: var(--space-2xl) auto var(--space-section);
  padding: var(--space-xl);
  background: var(--color-surface);
  text-align: center;
  color: var(--color-text-muted);
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: var(--space-md);
}

@media (max-width: 1100px) {
  .docs-layout {
    grid-template-columns: 200px minmax(0, 1fr);
  }

  .docs-outline {
    display: none;
  }
}

@media (max-width: 800px) {
  .docs-layout {
    grid-template-columns: 1fr;
  }

  .docs-sidebar {
    position: static;
  }

  .docs-sidebar__toggle {
    display: block;
    width: 100%;
    padding: var(--space-sm) var(--space-md);
    text-align: left;
    background: var(--color-surface);
    border: var(--border-width-sm) solid var(--color-surface-light);
    color: var(--color-text);
    font-family: var(--font-display);
    cursor: pointer;
  }

  .docs-sidebar__nav {
    display: none;
    margin-top: var(--space-sm);
    padding: var(--space-sm);
    background: var(--color-surface);
    border: var(--border-width-sm) solid var(--color-surface-light);
  }

  .docs-sidebar.is-open .docs-sidebar__nav {
    display: block;
  }

  .docs-article {
    padding: var(--space-lg);
  }

  .docs-pager {
    grid-template-columns: 1fr;
  }

  .docs-pager__link.is-next {
    text-align: left;
  }
}
</style>
