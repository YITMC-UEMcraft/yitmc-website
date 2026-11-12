<template>
  <PixelBackground>
    <AppHeader />
    <main class="profile-page">
      <section class="page-hero">
        <h1 class="page-hero__title" data-reveal>成员主页</h1>
        <p class="page-hero__subtitle" data-reveal data-reveal-delay="1">YITMC 成员公开档案</p>
        <BlockDivider variant="grass" />
      </section>

      <div class="profile-page__inner">
        <p v-if="loading" class="profile-card block-inset">正在读取档案…</p>

        <div v-else-if="error" class="profile-card block-inset profile-empty">
          <h2>没有找到这个档案</h2>
          <p>{{ error }}</p>
          <PixelButton to="/members" variant="secondary">返回成员风采</PixelButton>
        </div>

        <article v-else-if="profile" class="profile-card block-inset">
          <img :src="profile.avatar" :alt="profile.nickname" class="profile-avatar" @error="avatarFallback" />
          <h2 class="profile-nickname">{{ profile.nickname }}</h2>
          <p v-if="profile.playerName" class="profile-player">展示角色：{{ profile.playerName }}</p>
          <p class="profile-joined">加入于 {{ formatDate(profile.createdAt) }}</p>

          <p v-if="profile.bio" class="profile-bio">{{ profile.bio }}</p>
          <p v-else class="profile-bio is-empty">这个人很懒，还没有写简介。</p>

          <div v-if="profile.socialLinks?.length" class="profile-links">
            <a
              v-for="link in profile.socialLinks"
              :key="link.label + link.url"
              :href="link.url"
              target="_blank"
              rel="noopener"
            >{{ link.label }}</a>
          </div>

          <p v-if="profile.contact" class="profile-contact">联系方式：{{ profile.contact }}</p>

          <div class="profile-actions">
            <RouterLink v-if="isOwn" to="/account" class="profile-edit">编辑我的档案 →</RouterLink>
            <RouterLink to="/members" class="profile-back">← 成员风采</RouterLink>
          </div>
        </article>
      </div>
    </main>
    <AppFooter />
  </PixelBackground>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import PixelBackground from '@/components/layout/PixelBackground.vue'
import AppHeader from '@/components/layout/AppHeader.vue'
import AppFooter from '@/components/layout/AppFooter.vue'
import BlockDivider from '@/components/ui/BlockDivider.vue'
import PixelButton from '@/components/ui/PixelButton.vue'
import { fetchPublicUser, type AuthUser } from '@/lib/auth'
import { setSeo } from '@/lib/seo'
import { useAuth } from '@/composables/useAuth'

const route = useRoute()
const { user: currentUser } = useAuth()
const profile = ref<AuthUser | null>(null)
const loading = ref(true)
const error = ref('')

const isOwn = computed(() => Boolean(profile.value && currentUser.value && profile.value.id === currentUser.value.id))

function formatDate(timestamp: number): string {
  const date = new Date(timestamp * 1000)
  if (Number.isNaN(date.getTime())) return '—'
  return date.toLocaleDateString('zh-CN', { year: 'numeric', month: '2-digit', day: '2-digit' })
}

function avatarFallback(event: Event) {
  const img = event.target as HTMLImageElement
  if (img.src.endsWith('/logo/logo.png')) return
  img.src = '/logo/logo.png'
}

async function load(id: string) {
  loading.value = true
  error.value = ''
  profile.value = null
  try {
    profile.value = await fetchPublicUser(id)
    setSeo({
      title: `${profile.value.nickname} - 成员主页 - YITMC`,
      description: profile.value.bio || `${profile.value.nickname} 的 YITMC 公开档案`,
      path: `/profile/${profile.value.id}`,
      image: profile.value.avatar,
      noindex: true,
    })
  } catch (err) {
    error.value = err instanceof Error ? err.message : '档案不存在或未公开'
    setSeo({
      title: '成员主页 - YITMC',
      description: '该成员档案不存在或未公开。',
      path: `/profile/${id}`,
      noindex: true,
    })
  } finally {
    loading.value = false
  }
}

watch(
  () => String(route.params.id || ''),
  (id) => {
    if (id) load(id)
  },
  { immediate: true },
)

onMounted(() => {
  setSeo({ title: '成员主页 - YITMC', description: 'YITMC 成员公开档案', noindex: true })
})
</script>

<style scoped>
.profile-page {
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

.profile-page__inner {
  max-width: 720px;
  margin: 0 auto;
  padding: var(--space-2xl) var(--space-lg) var(--space-section);
}

.profile-card {
  background: var(--color-surface);
  padding: var(--space-xl);
  text-align: center;
}

.profile-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: var(--space-md);
}

.profile-empty h2 {
  font-size: var(--text-xl);
}

.profile-empty p {
  color: var(--color-text-muted);
}

.profile-avatar {
  width: 112px;
  height: 112px;
  margin: 0 auto var(--space-md);
  object-fit: cover;
  border: var(--border-width-sm) solid var(--color-accent-dim);
  image-rendering: pixelated;
}

.profile-nickname {
  font-size: var(--text-2xl);
  color: var(--color-accent-hover);
  margin-bottom: var(--space-xs);
}

.profile-player,
.profile-joined {
  font-size: var(--text-sm);
  color: var(--color-text-muted);
  margin-bottom: var(--space-xs);
}

.profile-bio {
  margin: var(--space-lg) 0;
  padding-top: var(--space-lg);
  border-top: var(--border-width-sm) dashed var(--color-surface-light);
  color: var(--color-text);
  line-height: 1.8;
  white-space: pre-line;
  text-align: left;
}

.profile-bio.is-empty {
  color: var(--color-text-muted);
}

.profile-links {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: var(--space-sm);
  margin-bottom: var(--space-md);
}

.profile-links a {
  padding: var(--space-xs) var(--space-md);
  border: var(--border-width-sm) solid var(--color-surface-light);
  color: var(--color-text);
  font-size: var(--text-sm);
}

.profile-links a:hover {
  border-color: var(--color-accent);
  color: var(--color-accent);
}

.profile-contact {
  font-size: var(--text-sm);
  color: var(--color-text-muted);
  overflow-wrap: anywhere;
}

.profile-actions {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: center;
  gap: var(--space-lg);
  margin-top: var(--space-lg);
}

.profile-edit {
  color: var(--color-accent);
  font-size: var(--text-sm);
}

.profile-back {
  color: var(--color-text-muted);
  font-size: var(--text-sm);
}
</style>
