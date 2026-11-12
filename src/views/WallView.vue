<template>
  <PixelBackground>
    <AppHeader />
    <main class="wall-page">
      <section class="page-hero">
        <h1 class="page-hero__title" data-reveal>留言墙</h1>
        <p class="page-hero__subtitle" data-reveal data-reveal-delay="1">留下你的足迹，与同好们分享想法与祝福</p>
        <BlockDivider variant="grass" />
      </section>

      <div class="wall-page__inner">
        <section class="wall-form block-inset" data-reveal>
          <h2 class="wall-form__title">写留言</h2>
          <form @submit.prevent="submit">
            <label class="wall-field">
              <span>昵称 <em>2–20 个字符</em></span>
              <input v-model="name" type="text" maxlength="20" placeholder="怎么称呼你？" autocomplete="nickname" />
            </label>

            <label class="wall-field">
              <span>留言内容 <em>1–500 个字符</em></span>
              <textarea v-model="content" rows="5" maxlength="500" placeholder="想对 YITMC 说点什么…"></textarea>
              <span class="wall-count" :class="{ 'is-over': contentLength > 500 }">{{ contentLength }} / 500</span>
            </label>

            <!-- 蜜罐：正常用户不可见 -->
            <label class="wall-honeypot" aria-hidden="true">
              <span>Website</span>
              <input v-model="website" type="text" tabindex="-1" autocomplete="off" />
            </label>

            <p v-if="error" class="wall-msg is-error">{{ error }}</p>
            <p v-else-if="success" class="wall-msg">{{ success }}</p>

            <button type="submit" class="wall-submit" :disabled="sending">
              {{ sending ? '提交中…' : '发表留言' }}
            </button>
            <p class="wall-note">留言采用先审后发，审核通过后会展示出来。</p>
          </form>
        </section>

        <section class="wall-list" data-reveal data-reveal-delay="1">
          <header class="wall-list__head">
            <h2 class="wall-list__title">大家的留言</h2>
            <span class="wall-list__count">共 {{ total }} 条</span>
          </header>

          <p v-if="loading" class="wall-empty">正在加载留言…</p>
          <p v-else-if="!messages.length" class="wall-empty">还没有留言，来抢第一个吧。</p>
          <ul v-else class="wall-list__items">
            <li v-for="message in messages" :key="message.id" class="wall-card block-inset">
              <div class="wall-card__head">
                <span class="wall-card__name">{{ message.name }}</span>
                <time class="wall-card__time" :datetime="new Date(message.createdAt * 1000).toISOString()">
                  {{ formatDate(message.createdAt) }}
                </time>
              </div>
              <p class="wall-card__content">{{ message.content }}</p>
            </li>
          </ul>

          <nav v-if="pages > 1" class="wall-pager" aria-label="留言翻页">
            <button type="button" :disabled="page <= 1 || loading" @click="load(page - 1)">上一页</button>
            <span>{{ page }} / {{ pages }}</span>
            <button type="button" :disabled="page >= pages || loading" @click="load(page + 1)">下一页</button>
          </nav>
        </section>
      </div>
    </main>
    <AppFooter />
  </PixelBackground>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import PixelBackground from '@/components/layout/PixelBackground.vue'
import AppHeader from '@/components/layout/AppHeader.vue'
import AppFooter from '@/components/layout/AppFooter.vue'
import BlockDivider from '@/components/ui/BlockDivider.vue'
import { fetchWall, postWall, type WallMessage } from '@/lib/wall'

const name = ref('')
const content = ref('')
const website = ref('')
const sending = ref(false)
const loading = ref(false)
const error = ref('')
const success = ref('')
const messages = ref<WallMessage[]>([])
const total = ref(0)
const page = ref(1)
const pages = ref(1)

const contentLength = computed(() => Array.from(content.value).length)

function formatDate(timestamp: number): string {
  const date = new Date(timestamp * 1000)
  if (Number.isNaN(date.getTime())) return ''
  return date.toLocaleString('zh-CN', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  })
}

async function load(nextPage = 1) {
  loading.value = true
  error.value = ''
  try {
    const data = await fetchWall(nextPage, 10)
    messages.value = data.messages
    total.value = data.total
    page.value = data.page
    pages.value = data.pages
  } catch (err) {
    error.value = err instanceof Error ? err.message : '加载留言失败'
  } finally {
    loading.value = false
  }
}

async function submit() {
  error.value = ''
  success.value = ''
  const nickname = name.value.trim()
  const text = content.value.trim()
  if (Array.from(nickname).length < 2 || Array.from(nickname).length > 20) {
    error.value = '昵称长度需在 2–20 个字符之间'
    return
  }
  if (!text || contentLength.value > 500) {
    error.value = '留言内容需在 1–500 个字符之间'
    return
  }
  sending.value = true
  try {
    await postWall(nickname, text, website.value)
    success.value = '留言已提交，审核通过后将展示在这里。'
    content.value = ''
    website.value = ''
  } catch (err) {
    error.value = err instanceof Error ? err.message : '提交失败，请稍后再试'
  } finally {
    sending.value = false
  }
}

onMounted(() => load(1))
</script>

<style scoped>
.wall-page {
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

.wall-page__inner {
  max-width: var(--max-width);
  margin: 0 auto;
  padding: var(--space-2xl) var(--space-lg) var(--space-section);
  display: grid;
  grid-template-columns: minmax(0, 380px) minmax(0, 1fr);
  gap: var(--space-xl);
  align-items: start;
}

.wall-form {
  position: sticky;
  top: calc(var(--header-height) + var(--space-lg));
  padding: var(--space-xl);
  background: var(--color-surface);
}

.wall-form__title {
  font-size: var(--text-xl);
  color: var(--color-text);
  margin-bottom: var(--space-lg);
}

.wall-field {
  display: flex;
  flex-direction: column;
  gap: var(--space-xs);
  margin-bottom: var(--space-md);
  font-size: var(--text-sm);
  color: var(--color-text);
}

.wall-field em {
  font-style: normal;
  font-size: var(--text-xs);
  color: var(--color-text-muted);
}

.wall-field input,
.wall-field textarea {
  width: 100%;
  padding: var(--space-sm) var(--space-md);
  background: var(--color-bg);
  border: var(--border-width-sm) solid var(--color-surface-light);
  color: var(--color-text);
  font: inherit;
  resize: vertical;
}

.wall-field input:focus,
.wall-field textarea:focus {
  outline: none;
  border-color: var(--color-accent);
}

.wall-count {
  align-self: flex-end;
  font-size: var(--text-xs);
  color: var(--color-text-muted);
}

.wall-count.is-over {
  color: var(--color-danger);
}

.wall-honeypot {
  position: absolute;
  left: -9999px;
  width: 1px;
  height: 1px;
  overflow: hidden;
}

.wall-submit {
  width: 100%;
  padding: var(--space-md);
  background: var(--color-accent);
  border: var(--border-width-sm) solid var(--color-accent-dim);
  color: #10200a;
  font-family: var(--font-display);
  font-size: var(--text-base);
  font-weight: 700;
  cursor: pointer;
}

.wall-submit:disabled {
  opacity: 0.6;
  cursor: default;
}

.wall-msg {
  margin-bottom: var(--space-sm);
  padding: var(--space-sm) var(--space-md);
  font-size: var(--text-sm);
  color: var(--color-accent-hover);
  background: rgba(92, 154, 59, 0.12);
  border-left: 3px solid var(--color-accent);
}

.wall-msg.is-error {
  color: #e79b9b;
  background: rgba(192, 57, 43, 0.14);
  border-left-color: var(--color-danger);
}

.wall-note {
  margin-top: var(--space-sm);
  font-size: var(--text-xs);
  color: var(--color-text-muted);
}

.wall-list__head {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: var(--space-md);
  margin-bottom: var(--space-lg);
  padding-bottom: var(--space-sm);
  border-bottom: var(--border-width-sm) solid var(--color-surface-light);
}

.wall-list__title {
  font-size: var(--text-xl);
  color: var(--color-text);
}

.wall-list__count {
  font-size: var(--text-xs);
  color: var(--color-text-muted);
}

.wall-empty {
  padding: var(--space-xl);
  color: var(--color-text-muted);
  text-align: center;
  background: var(--color-surface);
}

.wall-list__items {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: var(--space-md);
}

.wall-card {
  padding: var(--space-lg);
  background: var(--color-surface);
}

.wall-card__head {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: var(--space-md);
  margin-bottom: var(--space-sm);
}

.wall-card__name {
  font-family: var(--font-display);
  font-size: var(--text-lg);
  color: var(--color-accent-hover);
}

.wall-card__time {
  font-size: var(--text-xs);
  color: var(--color-text-muted);
}

.wall-card__content {
  white-space: pre-line;
  overflow-wrap: break-word;
  color: var(--color-text);
  line-height: 1.8;
}

.wall-pager {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: var(--space-md);
  margin-top: var(--space-lg);
  font-size: var(--text-sm);
  color: var(--color-text-muted);
}

.wall-pager button {
  padding: var(--space-xs) var(--space-md);
  background: var(--color-surface);
  border: var(--border-width-sm) solid var(--color-surface-light);
  color: var(--color-text);
  cursor: pointer;
}

.wall-pager button:disabled {
  opacity: 0.4;
  cursor: default;
}

@media (max-width: 900px) {
  .wall-page__inner {
    grid-template-columns: 1fr;
  }

  .wall-form {
    position: static;
  }
}
</style>
