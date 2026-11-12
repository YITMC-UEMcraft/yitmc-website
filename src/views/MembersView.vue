<template>
  <PixelBackground>
    <AppHeader />
    <main class="members-page">
      <section class="page-hero">
        <h1 class="page-hero__title" data-reveal>成员风采</h1>
        <p class="page-hero__subtitle" data-reveal data-reveal-delay="1">YITMC 管理团队与全体社员</p>
        <BlockDivider variant="grass" />
      </section>

      <div class="members-page__inner">
        <div class="members-page__grid stagger-children" data-reveal>
          <MemberCard v-for="member in membersData.leadership" :key="member.id" :member="member" />
        </div>

        <div class="members-page__join">
          <p>想要成为YITMC的一员吗？</p>
          <PixelButton to="/join" variant="gold">加入我们</PixelButton>
        </div>

        <section class="members-all" data-reveal>
          <header class="members-all__head">
            <div>
              <h2 class="members-all__title">全体社员</h2>
              <p class="members-all__desc">使用皮肤站账号登录过官网的社员会自动收录在这里</p>
            </div>
            <span class="members-all__count">共 {{ siteMembers.length }} 位</span>
          </header>

          <p v-if="loading" class="members-all__state">正在加载社员名单…</p>
          <p v-else-if="error" class="members-all__state is-error">{{ error }}</p>
          <p v-else-if="!siteMembers.length" class="members-all__state">
            还没有社员登录过，<RouterLink to="/account">登录后自动加入</RouterLink>。
          </p>
          <div v-else class="members-all__grid stagger-children">
            <SiteMemberCard v-for="member in siteMembers" :key="member.id" :member="member" />
          </div>
          <p class="members-all__note">未公开个人档案的社员只显示昵称、头像与游戏角色。</p>
        </section>
      </div>
    </main>
    <AppFooter />
  </PixelBackground>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import PixelBackground from '@/components/layout/PixelBackground.vue'
import AppHeader from '@/components/layout/AppHeader.vue'
import AppFooter from '@/components/layout/AppFooter.vue'
import BlockDivider from '@/components/ui/BlockDivider.vue'
import PixelButton from '@/components/ui/PixelButton.vue'
import MemberCard from '@/components/members/MemberCard.vue'
import SiteMemberCard from '@/components/members/SiteMemberCard.vue'
import { membersData } from '@/data'
import { fetchSiteMembers, type SiteMember } from '@/lib/site-members'

const siteMembers = ref<SiteMember[]>([])
const loading = ref(true)
const error = ref('')

async function loadSiteMembers() {
  loading.value = true
  error.value = ''
  try {
    siteMembers.value = await fetchSiteMembers()
  } catch (err) {
    error.value = err instanceof Error ? err.message : '加载社员名单失败'
  } finally {
    loading.value = false
  }
}

onMounted(loadSiteMembers)
</script>

<style scoped>
.members-page {
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

.members-page__inner {
  max-width: var(--max-width);
  margin: 0 auto;
  padding: var(--space-2xl) var(--space-lg) var(--space-section);
}

.members-page__grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: var(--space-lg);
}

.members-all {
  margin-top: var(--space-3xl);
}

.members-all__head {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: var(--space-md);
  margin-bottom: var(--space-lg);
  padding-bottom: var(--space-sm);
  border-bottom: var(--border-width-sm) solid var(--color-surface-light);
}

.members-all__title {
  font-family: var(--font-display);
  font-size: var(--text-xl);
  color: var(--color-text);
}

.members-all__desc {
  margin-top: 4px;
  font-size: var(--text-sm);
  color: var(--color-text-muted);
}

.members-all__count {
  flex: none;
  font-size: var(--text-xs);
  color: var(--color-text-muted);
}

.members-all__grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  gap: var(--space-md);
}

.members-all__state {
  padding: var(--space-xl);
  text-align: center;
  color: var(--color-text-muted);
  background: var(--color-surface);
}

.members-all__state a {
  color: var(--color-accent);
}

.members-all__state.is-error {
  color: #e79b9b;
}

.members-all__note {
  margin-top: var(--space-sm);
  font-size: var(--text-xs);
  color: var(--color-text-muted);
}

.members-page__join {
  text-align: center;
  margin-top: var(--space-2xl);
  margin-bottom: var(--space-3xl);
  padding: var(--space-2xl);
  background: var(--color-surface);
  border: var(--border-width) solid var(--color-brown);
}

.members-page__join p {
  font-family: var(--font-display);
  font-size: var(--text-lg);
  color: var(--color-text);
  margin-bottom: var(--space-lg);
}
</style>
