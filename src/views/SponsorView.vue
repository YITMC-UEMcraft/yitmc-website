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
            目前通过社长微信或 QQ 群联系管理团队，确认赞助方式与用途。添加好友或进群时请备注「赞助 YITMC」，
            方便我们及时回复。
          </p>
          <div class="sponsor-methods__grid">
            <CopyText label="社长微信" :text="config.presidentWechat" />
            <CopyText label="QQ 群" :text="config.qqGroup" />
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
import { computed } from 'vue'
import PixelBackground from '@/components/layout/PixelBackground.vue'
import AppHeader from '@/components/layout/AppHeader.vue'
import AppFooter from '@/components/layout/AppFooter.vue'
import BlockDivider from '@/components/ui/BlockDivider.vue'
import PixelButton from '@/components/ui/PixelButton.vue'
import AppIcon from '@/components/icons/AppIcon.vue'
import CopyText from '@/components/join/CopyText.vue'
import { siteConfig, sponsorsData } from '@/data'
import { dateSortKey } from '@/lib/date'

const config = siteConfig

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
  [...sponsorsData.sponsors]
    .filter((sponsor) => sponsor.visible !== false && String(sponsor.name || '').trim() !== '')
    .sort((a, b) => dateSortKey(b.date) - dateSortKey(a.date) || b.id - a.id),
)

function safeLink(value: string): string {
  const link = String(value || '').trim()
  return /^https?:\/\//i.test(link) ? link : ''
}
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
