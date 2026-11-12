<template>
  <PixelBackground>
    <AppHeader />
    <main class="account-page">
      <section class="page-hero">
        <h1 class="page-hero__title" data-reveal>我的档案</h1>
        <p class="page-hero__subtitle" data-reveal data-reveal-delay="1">管理你的公开主页与个人信息</p>
        <BlockDivider variant="grass" />
      </section>

      <div class="account-page__inner">
        <div v-if="!loaded" class="account-card block-inset">正在读取登录状态…</div>

        <div v-else-if="!user" class="account-login block-inset">
          <h2>使用皮肤站账号登录</h2>
          <p>本站与 YIT &amp; UEM 联合皮肤站互通，使用皮肤站账号授权登录即可自动创建本站档案，无需单独注册。</p>
          <p v-if="loginError" class="account-error">{{ loginError }}</p>
          <p v-else-if="!configured" class="account-error">
            皮肤站登录尚未配置，请联系网站维护者完成 OIDC 客户端配置。
          </p>
          <div class="account-login__actions">
            <a :href="loginHref('/account')" class="account-primary">使用皮肤站账号登录</a>
            <a :href="siteConfig.skinStationUrl" target="_blank" rel="noopener" class="account-secondary">注册皮肤站账号</a>
          </div>
        </div>

        <template v-else>
          <aside class="account-profile block-inset">
            <img :src="user.avatar" :alt="user.nickname" class="account-avatar" @error="avatarFallback" />
            <h2 class="account-nickname">{{ user.nickname }}</h2>
            <p v-if="user.playerName" class="account-player">展示角色：{{ user.playerName }}</p>
            <p v-if="user.email" class="account-email">{{ user.email }}</p>
            <p class="account-joined">加入于 {{ formatDate(user.createdAt) }}</p>

            <RouterLink v-if="form.isPublic" :to="`/profile/${user.id}`" class="account-public-link">
              查看公开主页 →
            </RouterLink>
            <p v-else class="account-private-hint">公开主页已关闭</p>

            <RouterLink
              v-if="user.siteRole === 'admin' || user.siteRole === 'super_admin'"
              to="/admin"
              class="account-admin-link"
            >
              进入官网管理（{{ user.siteRole === 'super_admin' ? '超级管理员' : '管理员' }}） →
            </RouterLink>

            <button type="button" class="account-logout" @click="doLogout">退出登录</button>
          </aside>

          <section class="account-form block-inset">
            <h2>编辑资料</h2>

            <label class="account-field">
              <span>个人简介 <em>{{ bioLength }} / 200</em></span>
              <textarea v-model="form.bio" rows="4" maxlength="200" placeholder="介绍一下你自己吧"></textarea>
            </label>

            <label class="account-field">
              <span>联系方式 <em>仅在你允许时公开</em></span>
              <input v-model="form.contact" maxlength="100" placeholder="QQ / 微信 / 邮箱等" />
            </label>
            <label class="account-check">
              <input v-model="form.showContact" type="checkbox" />
              <span>在公开主页展示联系方式</span>
            </label>

            <label class="account-field">
              <span>展示角色</span>
              <select v-model="form.playerUuid" :disabled="!user.players?.length">
                <option value="">不展示角色</option>
                <option v-for="player in user.players" :key="player.uuid" :value="player.uuid">
                  {{ player.name }}
                </option>
              </select>
              <span v-if="!user.players?.length" class="account-hint">皮肤站账号下还没有角色。</span>
            </label>

            <label class="account-check">
              <input v-model="form.isPublic" type="checkbox" />
              <span>公开我的档案（其他人可通过成员主页访问）</span>
            </label>

            <div class="account-social">
              <div class="account-social__head">
                <span>社交链接</span>
                <button type="button" class="account-add" :disabled="form.socialLinks.length >= 6" @click="addSocial">
                  + 添加
                </button>
              </div>
              <div v-for="(link, i) in form.socialLinks" :key="i" class="account-social__row">
                <input v-model="link.label" maxlength="20" placeholder="名称（如 B站）" />
                <input v-model="link.url" maxlength="200" placeholder="https://..." />
                <button type="button" class="account-remove" @click="removeSocial(i)">删除</button>
              </div>
              <p v-if="!form.socialLinks.length" class="account-hint">最多添加 6 个社交链接。</p>
            </div>

            <p v-if="error" class="account-error">{{ error }}</p>
            <p v-else-if="success" class="account-success">{{ success }}</p>

            <button type="button" class="account-primary account-save" :disabled="saving" @click="save">
              {{ saving ? '保存中…' : '保存资料' }}
            </button>
          </section>
        </template>
      </div>
    </main>
    <AppFooter />
  </PixelBackground>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import PixelBackground from '@/components/layout/PixelBackground.vue'
import AppHeader from '@/components/layout/AppHeader.vue'
import AppFooter from '@/components/layout/AppFooter.vue'
import BlockDivider from '@/components/ui/BlockDivider.vue'
import { siteConfig } from '@/data'
import { loginHref, updateProfile, type SocialLink } from '@/lib/auth'
import { setSeo } from '@/lib/seo'
import { useAuth } from '@/composables/useAuth'

const route = useRoute()
const router = useRouter()
const { user, configured, loaded, refreshAuth, setUser, logout } = useAuth()

const form = reactive({
  bio: '',
  contact: '',
  showContact: false,
  isPublic: true,
  playerUuid: '',
  socialLinks: [] as SocialLink[],
})
const saving = ref(false)
const error = ref('')
const success = ref('')

const bioLength = computed(() => Array.from(form.bio).length)

const loginError = computed(() => {
  const code = String(route.query.login_error || '')
  const messages: Record<string, string> = {
    not_configured: '皮肤站登录尚未配置，请联系网站维护者。',
    denied: '你取消了皮肤站授权。',
    state: '登录会话已过期，请重新登录。',
    exchange: '换取登录凭证失败，请重试。',
    userinfo: '读取皮肤站资料失败，请重试。',
    config: '登录配置有误，请联系维护者。',
  }
  return messages[code] || ''
})

function syncForm() {
  if (!user.value) return
  form.bio = user.value.bio || ''
  form.contact = user.value.contact || ''
  form.showContact = Boolean(user.value.showContact)
  form.isPublic = user.value.isPublic !== 0
  form.playerUuid = user.value.playerUuid || ''
  form.socialLinks = (user.value.socialLinks || []).map((link) => ({ ...link }))
}

watch(user, syncForm, { immediate: true })

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

function addSocial() {
  if (form.socialLinks.length >= 6) return
  form.socialLinks.push({ label: '', url: '' })
}

function removeSocial(index: number) {
  form.socialLinks.splice(index, 1)
}

async function save() {
  error.value = ''
  success.value = ''
  if (bioLength.value > 200) {
    error.value = '个人简介不能超过 200 个字符'
    return
  }
  saving.value = true
  try {
    const updated = await updateProfile({
      bio: form.bio,
      contact: form.contact,
      socialLinks: form.socialLinks,
      playerUuid: form.playerUuid,
      isPublic: form.isPublic,
      showContact: form.showContact,
    })
    setUser(updated)
    syncForm()
    success.value = '资料已保存'
  } catch (err) {
    error.value = err instanceof Error ? err.message : '保存失败，请稍后再试'
  } finally {
    saving.value = false
  }
}

async function doLogout() {
  await logout()
  router.push('/')
}

onMounted(async () => {
  setSeo({
    title: '我的档案 - YITMC',
    description: '管理 YITMC 个人档案：简介、联系方式、展示角色与社交链接。',
    path: '/account',
    noindex: true,
  })
  await refreshAuth()
})
</script>

<style scoped>
.account-page {
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

.account-page__inner {
  max-width: 1000px;
  margin: 0 auto;
  padding: var(--space-2xl) var(--space-lg) var(--space-section);
  display: grid;
  grid-template-columns: 300px minmax(0, 1fr);
  gap: var(--space-xl);
  align-items: start;
}

.account-card,
.account-login {
  background: var(--color-surface);
  padding: var(--space-xl);
}

.account-login {
  grid-column: 1 / -1;
  max-width: 560px;
  margin: 0 auto;
  text-align: center;
}

.account-login h2 {
  font-size: var(--text-xl);
  margin-bottom: var(--space-sm);
}

.account-login p {
  color: var(--color-text-muted);
  margin-bottom: var(--space-md);
}

.account-login__actions {
  display: flex;
  justify-content: center;
  flex-wrap: wrap;
  gap: var(--space-md);
}

.account-primary,
.account-secondary {
  display: inline-block;
  padding: var(--space-sm) var(--space-lg);
  font-family: var(--font-display);
  border: var(--border-width-sm) solid var(--color-accent);
  color: #10200a;
  background: var(--color-accent);
}

.account-secondary {
  background: transparent;
  color: var(--color-text);
  border-color: var(--color-surface-light);
}

.account-profile {
  position: sticky;
  top: calc(var(--header-height) + var(--space-lg));
  background: var(--color-surface);
  padding: var(--space-xl);
  text-align: center;
}

.account-avatar {
  width: 96px;
  height: 96px;
  margin: 0 auto var(--space-md);
  object-fit: cover;
  border: var(--border-width-sm) solid var(--color-accent-dim);
  image-rendering: pixelated;
}

.account-nickname {
  font-size: var(--text-xl);
  color: var(--color-accent-hover);
  margin-bottom: var(--space-xs);
}

.account-player,
.account-email,
.account-joined {
  font-size: var(--text-sm);
  color: var(--color-text-muted);
  margin-bottom: var(--space-xs);
  overflow-wrap: anywhere;
}

.account-public-link {
  display: inline-block;
  margin-top: var(--space-md);
  font-size: var(--text-sm);
  color: var(--color-accent);
}

.account-private-hint {
  margin-top: var(--space-md);
  font-size: var(--text-sm);
  color: var(--color-text-muted);
}

.account-admin-link {
  display: block;
  margin-top: var(--space-md);
  padding: var(--space-xs) var(--space-sm);
  font-size: var(--text-sm);
  color: var(--color-gold);
  border: var(--border-width-sm) solid var(--color-gold-dim);
}

.account-logout {
  margin-top: var(--space-lg);
  padding: var(--space-xs) var(--space-md);
  background: transparent;
  border: var(--border-width-sm) solid var(--color-danger);
  color: var(--color-danger);
  cursor: pointer;
}

.account-form {
  background: var(--color-surface);
  padding: var(--space-xl);
}

.account-form h2 {
  font-size: var(--text-xl);
  margin-bottom: var(--space-lg);
}

.account-field {
  display: flex;
  flex-direction: column;
  gap: var(--space-xs);
  margin-bottom: var(--space-md);
  font-size: var(--text-sm);
  color: var(--color-text);
}

.account-field em {
  font-style: normal;
  font-size: var(--text-xs);
  color: var(--color-text-muted);
}

.account-field input,
.account-field textarea,
.account-field select {
  width: 100%;
  padding: var(--space-sm) var(--space-md);
  background: var(--color-bg);
  border: var(--border-width-sm) solid var(--color-surface-light);
  color: var(--color-text);
  font: inherit;
  resize: vertical;
}

.account-field input:focus,
.account-field textarea:focus,
.account-field select:focus {
  outline: none;
  border-color: var(--color-accent);
}

.account-check {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
  margin-bottom: var(--space-md);
  font-size: var(--text-sm);
  color: var(--color-text-muted);
}

.account-check input {
  width: auto;
}

.account-social {
  margin: var(--space-lg) 0;
  padding-top: var(--space-md);
  border-top: var(--border-width-sm) dashed var(--color-surface-light);
}

.account-social__head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: var(--space-sm);
  font-size: var(--text-sm);
  color: var(--color-text);
}

.account-add,
.account-remove {
  padding: 2px var(--space-sm);
  background: transparent;
  border: var(--border-width-sm) solid var(--color-surface-light);
  color: var(--color-text-muted);
  cursor: pointer;
  font-size: var(--text-xs);
}

.account-social__row {
  display: grid;
  grid-template-columns: 140px minmax(0, 1fr) auto;
  gap: var(--space-sm);
  margin-bottom: var(--space-sm);
}

.account-social__row input {
  width: 100%;
  padding: var(--space-xs) var(--space-sm);
  background: var(--color-bg);
  border: var(--border-width-sm) solid var(--color-surface-light);
  color: var(--color-text);
  font: inherit;
}

.account-hint {
  font-size: var(--text-xs);
  color: var(--color-text-muted);
}

.account-error,
.account-success {
  margin-bottom: var(--space-sm);
  padding: var(--space-sm) var(--space-md);
  font-size: var(--text-sm);
  border-left: 3px solid var(--color-danger);
  background: rgba(192, 57, 43, 0.12);
  color: var(--color-danger);
}

.account-success {
  border-left-color: var(--color-accent);
  background: rgba(92, 154, 59, 0.12);
  color: var(--color-accent-hover);
}

.account-save {
  width: 100%;
  cursor: pointer;
}

.account-save:disabled {
  opacity: 0.6;
  cursor: default;
}

@media (max-width: 820px) {
  .account-page__inner {
    grid-template-columns: 1fr;
  }

  .account-profile {
    position: static;
  }
}

@media (max-width: 520px) {
  .account-social__row {
    grid-template-columns: 1fr;
  }
}
</style>
