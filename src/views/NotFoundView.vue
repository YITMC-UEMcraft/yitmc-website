<template>
  <PixelBackground>
    <AppHeader />
    <main class="notfound-page">
      <section class="notfound block-inset">
        <p class="notfound__code">404</p>
        <h1 class="notfound__title">这个区块还没生成</h1>
        <p class="notfound__desc">你访问的页面不存在，可能被挖掉了，或者链接输错了。</p>
        <div class="notfound__actions">
          <PixelButton to="/" variant="primary">回到首页</PixelButton>
          <PixelButton to="/works" variant="secondary">看看作品</PixelButton>
          <PixelButton to="/news" variant="secondary">社团动态</PixelButton>
        </div>
      </section>
    </main>
    <AppFooter />
  </PixelBackground>
</template>

<script setup lang="ts">
import PixelBackground from '@/components/layout/PixelBackground.vue'
import AppHeader from '@/components/layout/AppHeader.vue'
import AppFooter from '@/components/layout/AppFooter.vue'
import PixelButton from '@/components/ui/PixelButton.vue'
</script>

<style scoped>
.notfound-page {
  min-height: calc(100vh - var(--header-height));
  padding: calc(var(--header-height) + var(--space-section)) var(--space-lg) var(--space-section);
  display: grid;
  place-items: center;
}

.notfound {
  width: min(100%, 640px);
  padding: var(--space-2xl) var(--space-xl);
  background: var(--color-surface);
  text-align: center;
}

.notfound__code {
  font-family: var(--font-pixel);
  font-size: clamp(3rem, 2rem + 6vw, 6rem);
  line-height: 1;
  color: var(--color-accent);
  text-shadow: 4px 4px 0 rgba(0, 0, 0, 0.55);
  margin-bottom: var(--space-md);
}

.notfound__title {
  font-size: var(--text-2xl);
  color: var(--color-text);
  margin-bottom: var(--space-sm);
}

.notfound__desc {
  color: var(--color-text-muted);
  margin-bottom: var(--space-xl);
}

.notfound__actions {
  display: flex;
  justify-content: center;
  flex-wrap: wrap;
  gap: var(--space-md);
}
</style>
