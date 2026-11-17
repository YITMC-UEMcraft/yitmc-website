<template>
  <PixelBackground>
    <AppHeader />
    <main class="sponsor-page">
      <section class="page-hero">
        <h1 class="page-hero__title" data-reveal>赞助我们</h1>
        <p class="page-hero__subtitle" data-reveal data-reveal-delay="1">
          让热爱持续运转，让方块世界走得更远
        </p>
        <BlockDivider variant="grass" />
      </section>

      <div class="sponsor-page__inner">
        <section class="sponsor-intro block-inset" data-reveal>
          <div class="sponsor-intro__text">
            <h2>为什么需要赞助？</h2>
            <p>
              YITMC 是由燕京理工学院学生自发创建和运营的社团。我们做校园复刻、办建筑赛、维护服务器，
              也持续更新官网和皮肤站。
            </p>
            <p>
              服务器、域名、网络、活动物料和站点维护都需要持续投入。你的赞助会全部用于社团公共支出，
              让这些属于所有成员的项目继续运转。
            </p>
          </div>
          <div class="sponsor-intro__badge">
            <span class="sponsor-intro__heart"><AppIcon name="heart" :size="30" /></span>
            <strong>用爱发电</strong>
            <span>也欢迎用行动支持</span>
          </div>
        </section>

        <section class="sponsor-section" data-reveal>
          <h2 class="sponsor-section__title">赞助用在哪里</h2>
          <div class="sponsor-uses">
            <article v-for="use in uses" :key="use.title" class="sponsor-use block-inset">
              <AppIcon :name="use.icon" :size="22" />
              <h3>{{ use.title }}</h3>
              <p>{{ use.description }}</p>
            </article>
          </div>
        </section>

        <section class="sponsor-methods block-inset" data-reveal>
          <h2>如何赞助</h2>
          <p class="sponsor-methods__intro">
            可以通过爱发电直接赞助；也可以添加社长微信或进入 QQ 群联系管理团队。添加好友或进群时请备注
            「赞助 YITMC」，方便我们及时回复。
          </p>
          <div v-if="afdianStatus.pageUrl" class="sponsor-methods__afdian">
            <a :href="afdianStatus.pageUrl" target="_blank" rel="noopener" class="sponsor-pay">
              <AppIcon name="heart" :size="16" /> 前往爱发电赞助 →
            </a>
            <span>支付后回到本页，登录并登记订单号即可自动加入鸣谢。</span>
          </div>
          <div class="sponsor-methods__grid">
            <CopyText label="社长微信" :text="config.presidentWechat" />
            <CopyText label="QQ 群" :text="config.qqGroup" />
          </div>

          <div class="sponsor-claim">
            <h3>已赞助？登记订单号</h3>
            <p v-if="!afdianStatus.loaded" class="sponsor-claim__hint">正在检查自动登记状态…</p>
            <template v-else-if="afdianStatus.configured">
              <template v-if="user">
                <div v-if="sponsorCode" class="sponsor-code">
                  <div class="sponsor-code__head">
                    <span>你的赞助登记码</span>
                    <button type="button" @click="copyCode">{{ codeCopied ? '已复制' : '复制' }}</button>
                  </div>
                  <code>{{ sponsorCode }}</code>
                  <p>
                    在爱发电付款时，把这段码填到订单「留言」里，系统会自动登记并展示你的鸣谢；
                    如需匿名，请在留言里加上「匿名」。忘记填也可以继续用下面的订单号登记。
                  </p>
                </div>
                <p class="sponsor-claim__hint">
                  填写爱发电订单号，系统会自动核验并把这次赞助记到你的官网账号上。
                </p>
                <form class="sponsor-claim__form" @submit.prevent="submitClaim">
                  <input
                    v-model="claimForm.outTradeNo"
                    type="text"
                    maxlength="64"
                    placeholder="爱发电订单号"
                    autocomplete="off"
                  />
                  <label class="sponsor-claim__anonymous">
                    <input v-model="claimForm.anonymous" type="checkbox" /> 匿名展示
                  </label>
                  <button type="submit" class="sponsor-claim__submit" :disabled="claimForm.sending">
                    {{ claimForm.sending ? '核验中…' : '核验并登记' }}
                  </button>
                </form>
                <p v-if="claimForm.error" class="sponsor-claim__msg is-error">{{ claimForm.error }}</p>
                <p v-else-if="claimForm.success" class="sponsor-claim__msg">{{ claimForm.success }}</p>
              </template>
              <p v-else class="sponsor-claim__hint">
                请先 <a :href="loginHref('/sponsor')">登录官网账号</a>，系统会把赞助记到你的账号上。
              </p>
            </template>
            <p v-else class="sponsor-claim__hint">
              自动登记暂未开放，可以先通过上面的社长微信或 QQ 群联系管理团队。
            </p>
          </div>

          <div class="sponsor-methods__actions">
            <PixelButton to="/join" variant="primary">先来认识我们</PixelButton>
            <a :href="config.qqGroupJoinUrl" target="_blank" rel="noopener" class="sponsor-methods__link">
              一键加入 QQ 群 →
            </a>
          </div>
        </section>

        <section class="sponsor-section" data-reveal>
          <header class="sponsor-thanks__head">
            <div>
              <h2 class="sponsor-section__title">赞助者鸣谢</h2>
              <p class="sponsor-thanks__desc">感谢每一位愿意为社团添一块砖的人。</p>
            </div>
            <span class="sponsor-thanks__count">{{ visibleSponsors.length }} 位</span>
          </header>

          <div v-if="visibleSponsors.length" class="sponsor-thanks__grid">
            <article v-for="sponsor in visibleSponsors" :key="sponsor.id" class="sponsor-card block-inset">
              <div class="sponsor-card__head">
                <strong>{{ sponsor.name }}</strong>
                <span class="sponsor-card__type">{{ sponsor.type || '赞助者' }}</span>
              </div>
              <p v-if="sponsor.message" class="sponsor-card__message">{{ sponsor.message }}</p>
              <div class="sponsor-card__foot">
                <span v-if="sponsor.date">{{ sponsor.date }}</span>
                <a v-if="safeLink(sponsor.link)" :href="safeLink(sponsor.link)" target="_blank" rel="noopener">
                  访问主页 →
                </a>
              </div>
            </article>
          </div>

          <div v-else class="sponsor-empty block-inset">
            <AppIcon name="heart" :size="28" />
            <p>还没有公开的赞助者，欢迎成为第一位支持 YITMC 的人。</p>
            <RouterLink to="/join">先来加入我们 →</RouterLink>
          </div>
        </section>

        <section class="sponsor-notes block-inset" data-reveal>
          <h2>说明</h2>
          <ul>
            <li>赞助完全自愿，请量力而行；学生社团不接受任何形式的强制摊派。</li>
            <li>赞助将用于服务器、域名、活动物料与站点维护等公共支出，收支会在社团群内公示。</li>
            <li>公开鸣谢默认展示名称/昵称与留言；如需匿名或关闭展示，请在联系时说明。</li>
            <li>涉及企业、物资或长期合作，请先与社长确认，并遵守学校社团管理规定。</li>
          </ul>
        </section>
      </div>
    </main>
    <AppFooter />
  </PixelBackground>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import PixelBackground from '@/components/layout/PixelBackground.vue'
import AppHeader from '@/components/layout/AppHeader.vue'
import AppFooter from '@/components/layout/AppFooter.vue'
import BlockDivider from '@/components/ui/BlockDivider.vue'
import PixelButton from '@/components/ui/PixelButton.vue'
import AppIcon from '@/components/icons/AppIcon.vue'
import CopyText from '@/components/join/CopyText.vue'
import { siteConfig, sponsorsData } from '@/data'
import { dateSortKey } from '@/lib/date'
import { useAuth } from '@/composables/useAuth'
import { loginHref } from '@/lib/auth'
import { claimSponsor, fetchSponsorCode, fetchSponsorStatus, type SponsorPublic } from '@/lib/sponsor'

const config = siteConfig
const { user, refreshAuth } = useAuth()
const sponsorList = ref<SponsorPublic[]>([...sponsorsData.sponsors])
const afdianStatus = reactive({
  loaded: false,
  configured: false,
  pageUrl: '',
})
const claimForm = reactive({
  outTradeNo: '',
  anonymous: false,
  sending: false,
  error: '',
  success: '',
})
const sponsorCode = ref('')
const codeCopied = ref(false)

const uses = [
  {
    icon: 'gamepad',
    title: '服务器与网络',
    description: 'MC 服务器、域名、网络加速与数据备份。',
  },
  {
    icon: 'trophy',
    title: '活动与赛事',
    description: '建筑赛、交流会与线下活动的物料和奖品。',
  },
  {
    icon: 'construction',
    title: '创作与维护',
    description: '官网、皮肤站与社团创作工具的持续更新。',
  },
]

const visibleSponsors = computed(() =>
  [...sponsorList.value]
    .filter((sponsor) => sponsor.visible !== false && String(sponsor.name || '').trim() !== '')
    .sort((a, b) => dateSortKey(b.date) - dateSortKey(a.date) || b.id - a.id),
)

function safeLink(value: string): string {
  const link = String(value || '').trim()
  return /^https?:\/\//i.test(link) ? link : ''
}

function upsertSponsor(sponsor: SponsorPublic) {
  if (!sponsor || !sponsor.id) return
  const index = sponsorList.value.findIndex(
    (item) =>
      item.id === sponsor.id
      || (item.source === 'afdian' && sponsor.source === 'afdian' && item.userId === sponsor.userId),
  )
  if (index >= 0) sponsorList.value[index] = sponsor
  else sponsorList.value.push(sponsor)
}

async function loadAfdianStatus() {
  try {
    const status = await fetchSponsorStatus()
    afdianStatus.configured = status.configured
    afdianStatus.pageUrl = status.pageUrl
  } catch {
    // 接口未配置或不可用时保持关闭自动登记
  } finally {
    afdianStatus.loaded = true
  }
}

async function submitClaim() {
  claimForm.error = ''
  claimForm.success = ''
  if (!user.value) {
    claimForm.error = '请先登录官网账号'
    return
  }
  const outTradeNo = claimForm.outTradeNo.trim()
  if (!/^[A-Za-z0-9_-]{6,64}$/.test(outTradeNo)) {
    claimForm.error = '请填写正确的爱发电订单号'
    return
  }
  claimForm.sending = true
  try {
    const result = await claimSponsor(outTradeNo, claimForm.anonymous)
    upsertSponsor(result.sponsor)
    claimForm.outTradeNo = ''
    claimForm.success = result.already
      ? '这个订单已经登记过了，感谢你的支持！'
      : '登记成功，感谢你的支持！'
  } catch (err) {
    claimForm.error = err instanceof Error ? err.message : '登记失败，请稍后再试'
  } finally {
    claimForm.sending = false
  }
}

async function copyCode() {
  if (!sponsorCode.value) return
  try {
    await navigator.clipboard.writeText(sponsorCode.value)
    codeCopied.value = true
    setTimeout(() => { codeCopied.value = false }, 2000)
  } catch {
    // 复制失败时保留文本供手动选择
  }
}

onMounted(async () => {
  await refreshAuth()
  await loadAfdianStatus()
  if (user.value && afdianStatus.configured) {
    try {
      sponsorCode.value = await fetchSponsorCode()
    } catch {
      // 登记码获取失败时仍可手动提交订单号
    }
  }
})
</script>

<style scoped>
.sponsor-page {
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

.sponsor-page__inner {
  max-width: 900px;
  margin: 0 auto;
  padding: var(--space-2xl) var(--space-lg) var(--space-section);
  display: flex;
  flex-direction: column;
  gap: var(--space-xl);
}

.sponsor-intro {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 220px;
  gap: var(--space-xl);
  align-items: center;
  padding: var(--space-xl);
  background: var(--color-surface);
}

.sponsor-intro__text h2,
.sponsor-methods h2,
.sponsor-notes h2 {
  font-family: var(--font-display);
  font-size: var(--text-xl);
  color: var(--color-text);
  margin-bottom: var(--space-sm);
}

.sponsor-intro__text p,
.sponsor-methods__intro,
.sponsor-thanks__desc {
  font-size: var(--text-sm);
  line-height: 1.9;
  color: var(--color-text-muted);
}

.sponsor-intro__text p + p {
  margin-top: var(--space-sm);
}

.sponsor-intro__badge {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  padding: var(--space-lg);
  text-align: center;
  border: var(--border-width-sm) dashed var(--color-gold-dim);
  background: var(--color-bg);
}

.sponsor-intro__heart {
  color: var(--color-danger);
}

.sponsor-intro__badge strong {
  font-family: var(--font-display);
  font-size: var(--text-lg);
  color: var(--color-gold);
}

.sponsor-intro__badge > span:last-child {
  font-size: var(--text-xs);
  color: var(--color-text-muted);
}

.sponsor-section__title {
  font-size: var(--text-xl);
  color: var(--color-text);
}

.sponsor-section > .sponsor-section__title,
.sponsor-thanks__head {
  margin-bottom: var(--space-lg);
}

.sponsor-uses {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: var(--space-md);
}

.sponsor-use {
  padding: var(--space-lg);
  background: var(--color-surface);
  color: var(--color-accent);
}

.sponsor-use h3 {
  margin: var(--space-sm) 0 var(--space-xs);
  font-family: var(--font-display);
  font-size: var(--text-base);
  color: var(--color-text);
}

.sponsor-use p {
  font-size: var(--text-sm);
  line-height: 1.7;
  color: var(--color-text-muted);
}

.sponsor-methods {
  padding: var(--space-xl);
  background: var(--color-surface);
}

.sponsor-methods__grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: var(--space-md);
  margin-top: var(--space-lg);
}

.sponsor-methods__afdian {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: var(--space-md);
  margin-top: var(--space-lg);
  padding-top: var(--space-lg);
  border-top: var(--border-width-sm) solid var(--color-surface-light);
}

.sponsor-methods__afdian > span {
  font-size: var(--text-xs);
  color: var(--color-text-muted);
}

.sponsor-pay {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: var(--space-sm) var(--space-lg);
  font-family: var(--font-display);
  font-size: var(--text-base);
  color: #2b2000 !important;
  background: var(--color-gold);
  border: var(--border-width-sm) solid var(--color-gold-dim);
  transition: background var(--duration-fast) var(--ease-out);
}

.sponsor-pay:hover {
  background: var(--color-gold);
  color: #2b2000 !important;
  filter: brightness(1.08);
}

.sponsor-claim {
  margin-top: var(--space-lg);
  padding-top: var(--space-lg);
  border-top: var(--border-width-sm) dashed var(--color-surface-light);
}

.sponsor-claim h3 {
  margin-bottom: var(--space-xs);
  font-family: var(--font-display);
  font-size: var(--text-lg);
  color: var(--color-text);
}

.sponsor-claim__hint {
  font-size: var(--text-sm);
  line-height: 1.7;
  color: var(--color-text-muted);
}

.sponsor-claim__hint a {
  color: var(--color-accent);
}

.sponsor-code {
  margin-top: var(--space-md);
  padding: var(--space-md);
  background: var(--color-bg);
  border: var(--border-width-sm) dashed var(--color-gold-dim);
}

.sponsor-code__head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-sm);
  margin-bottom: var(--space-xs);
  font-family: var(--font-pixel);
  font-size: var(--text-xs);
  color: var(--color-gold);
}

.sponsor-code__head button {
  padding: 3px 10px;
  font-family: var(--font-display);
  font-size: var(--text-xs);
  color: var(--color-text);
  background: transparent;
  border: var(--border-width-sm) solid var(--color-gold-dim);
  cursor: pointer;
}

.sponsor-code__head button:hover {
  color: var(--color-gold);
  border-color: var(--color-gold);
}

.sponsor-code code {
  display: block;
  padding: var(--space-sm) var(--space-md);
  font-family: var(--font-body);
  font-size: var(--text-lg);
  color: var(--color-gold);
  background: var(--color-surface);
  border: var(--border-width-sm) solid var(--color-surface-light);
  overflow-wrap: anywhere;
  user-select: all;
}

.sponsor-code p {
  margin-top: var(--space-xs);
  font-size: var(--text-xs);
  line-height: 1.7;
  color: var(--color-text-muted);
}

.sponsor-claim__form {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: var(--space-sm);
  margin-top: var(--space-md);
}

.sponsor-claim__form input[type='text'] {
  flex: 1 1 280px;
  min-width: 0;
  padding: var(--space-sm) var(--space-md);
  font: inherit;
  font-size: var(--text-sm);
  color: var(--color-text);
  background: var(--color-bg);
  border: var(--border-width-sm) solid var(--color-surface-light);
}

.sponsor-claim__form input[type='text']:focus {
  outline: none;
  border-color: var(--color-accent);
}

.sponsor-claim__anonymous {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: var(--text-xs);
  color: var(--color-text-muted);
  white-space: nowrap;
}

.sponsor-claim__anonymous input {
  width: auto;
}

.sponsor-claim__submit {
  padding: var(--space-sm) var(--space-lg);
  font-family: var(--font-display);
  font-size: var(--text-sm);
  color: #10200a;
  background: var(--color-accent);
  border: var(--border-width-sm) solid var(--color-accent-dim);
  cursor: pointer;
}

.sponsor-claim__submit:disabled {
  opacity: 0.6;
  cursor: default;
}

.sponsor-claim__msg {
  margin-top: var(--space-sm);
  font-size: var(--text-sm);
  color: var(--color-accent);
}

.sponsor-claim__msg.is-error {
  color: var(--color-danger);
}

.sponsor-methods__actions {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: var(--space-md);
  margin-top: var(--space-lg);
}

.sponsor-methods__link {
  font-size: var(--text-sm);
  color: var(--color-accent);
}

.sponsor-thanks__head {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: var(--space-md);
  padding-bottom: var(--space-sm);
  border-bottom: var(--border-width-sm) solid var(--color-surface-light);
}

.sponsor-thanks__count {
  flex: none;
  font-size: var(--text-xs);
  color: var(--color-text-muted);
}

.sponsor-thanks__grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  gap: var(--space-md);
}

.sponsor-card {
  display: flex;
  flex-direction: column;
  gap: var(--space-sm);
  padding: var(--space-lg);
  background: var(--color-surface);
}

.sponsor-card__head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-sm);
}

.sponsor-card__head strong {
  font-family: var(--font-display);
  font-size: var(--text-lg);
  color: var(--color-text);
  overflow-wrap: anywhere;
}

.sponsor-card__type {
  flex: none;
  padding: 1px 8px;
  font-size: 11px;
  color: var(--color-gold);
  border: var(--border-width-sm) solid var(--color-gold-dim);
}

.sponsor-card__message {
  font-size: var(--text-sm);
  line-height: 1.7;
  color: var(--color-text-muted);
  overflow-wrap: anywhere;
}

.sponsor-card__foot {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-sm);
  margin-top: auto;
  padding-top: var(--space-sm);
  border-top: var(--border-width-sm) solid var(--color-surface-light);
  font-size: var(--text-xs);
  color: var(--color-text-muted);
}

.sponsor-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: var(--space-sm);
  padding: var(--space-2xl);
  text-align: center;
  color: var(--color-text-muted);
  background: var(--color-surface);
}

.sponsor-empty p {
  font-size: var(--text-sm);
}

.sponsor-empty a {
  font-size: var(--text-sm);
  color: var(--color-accent);
}

.sponsor-notes {
  padding: var(--space-xl);
  background: var(--color-surface);
}

.sponsor-notes ul {
  display: flex;
  flex-direction: column;
  gap: var(--space-xs);
  margin-top: var(--space-md);
  padding-left: 1.2em;
  font-size: var(--text-sm);
  line-height: 1.8;
  color: var(--color-text-muted);
}

@media (max-width: 768px) {
  .sponsor-intro {
    grid-template-columns: 1fr;
  }

  .sponsor-methods__grid {
    grid-template-columns: 1fr;
  }

  .sponsor-thanks__head {
    align-items: flex-start;
    flex-direction: column;
    gap: var(--space-xs);
  }
}
</style>
