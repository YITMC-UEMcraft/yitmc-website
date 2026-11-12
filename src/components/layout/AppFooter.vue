<template>
  <footer class="app-footer">
    <BlockDivider variant="dirt" />
    <div class="app-footer__inner">
      <div class="app-footer__brand">
        <img src="/logo/logo.png" alt="YITMC" class="app-footer__logo" />
        <p class="app-footer__name">燕京理工学院 MC玩家创作协会</p>
        <p class="app-footer__motto">用方块还原校园，用创意连接世界</p>
      </div>

      <div class="app-footer__links">
        <h4 class="app-footer__heading">快速导航</h4>
        <router-link v-for="link in navLinks" :key="link.to" :to="link.to" class="app-footer__link">
          {{ link.label }}
        </router-link>
      </div>

      <div class="app-footer__social">
        <h4 class="app-footer__heading">关注我们</h4>
        <a :href="config.douyinUrl" target="_blank" rel="noopener" class="app-footer__social-link"><AppIcon name="music" :size="14" /> 抖音</a>
        <a :href="config.bilibiliUrl" target="_blank" rel="noopener" class="app-footer__social-link"><AppIcon name="video" :size="14" /> B站</a>
        <a :href="config.skinStationUrl" target="_blank" rel="noopener" class="app-footer__social-link"><AppIcon name="shirt" :size="14" /> YITMC 皮肤站</a>
      </div>

      <div class="app-footer__links">
        <h4 class="app-footer__heading">相关链接</h4>
        <a :href="config.minecraftUrl" target="_blank" rel="noopener" class="app-footer__social-link">Minecraft 正版</a>
        <a :href="config.schoolUrl" target="_blank" rel="noopener" class="app-footer__social-link">燕京理工学院</a>
        <a :href="config.muaUrl" target="_blank" rel="noopener" class="app-footer__social-link">MUA 高校联盟</a>
        <a :href="config.vcacUrl" target="_blank" rel="noopener" class="app-footer__social-link">VCAC 体素专委会</a>
        <a :href="config.uemcraftUrl" target="_blank" rel="noopener" class="app-footer__social-link">应大MC同好会</a>
        <a :href="config.acmUrl" target="_blank" rel="noopener" class="app-footer__social-link">ACM 协会</a>
      </div>
    </div>

    <div class="app-footer__bottom">
      <p>&copy; {{ year }} YITMC. 燕京理工学院MC玩家创作协会.</p>
      <a href="https://beian.miit.gov.cn/" target="_blank" rel="noopener" class="app-footer__icp">{{ config.icpNumber }}</a>
    </div>
  </footer>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import BlockDivider from '@/components/ui/BlockDivider.vue'
import AppIcon from '@/components/icons/AppIcon.vue'
import { siteConfig } from '@/data'

const config = siteConfig
const year = computed(() => new Date().getFullYear())

const navLinks = [
  { to: '/', label: '首页' },
  { to: '/works', label: '作品展示' },
  { to: '/news', label: '社团动态' },
  { to: '/events', label: '活动中心' },
  { to: '/docs', label: '指南文档' },
  { to: '/wall', label: '留言墙' },
  { to: '/members', label: '成员风采' },
  { to: '/about', label: '关于我们' },
  { to: '/join', label: '加入我们' },
]
</script>

<style scoped>
.app-footer {
  background: var(--color-surface);
  border-top: var(--border-width) solid var(--color-brown);
}

.app-footer__inner {
  max-width: var(--max-width);
  margin: 0 auto;
  padding: var(--space-2xl) var(--space-lg);
  display: grid;
  grid-template-columns: 2fr 1fr 1fr 1fr;
  gap: var(--space-xl);
}

.app-footer__logo {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  object-fit: cover;
  margin-bottom: var(--space-sm);
}

.app-footer__name {
  font-family: var(--font-display);
  font-size: var(--text-lg);
  color: var(--color-text);
}

.app-footer__motto {
  font-size: var(--text-sm);
  color: var(--color-text-muted);
  margin-top: var(--space-xs);
}

.app-footer__heading {
  font-family: var(--font-pixel);
  font-size: var(--text-xs);
  color: var(--color-accent);
  margin-bottom: var(--space-md);
  text-transform: uppercase;
}

.app-footer__link,
.app-footer__social-link {
  display: block;
  font-size: var(--text-sm);
  color: var(--color-text-muted) !important;
  padding: var(--space-xs) 0;
  transition: color var(--duration-fast) var(--ease-pixel);
}

.app-footer__link:hover,
.app-footer__social-link:hover {
  color: var(--color-accent) !important;
}

.app-footer__bottom {
  border-top: var(--border-width-sm) solid var(--color-surface-light);
  padding: var(--space-md) var(--space-lg);
  text-align: center;
  font-size: var(--text-xs);
  color: var(--color-text-muted);
}

.app-footer__icp {
  display: inline-block;
  margin-top: var(--space-xs);
  color: var(--color-text-muted) !important;
  text-decoration: none;
  transition: color var(--duration-fast) var(--ease-pixel);
}

.app-footer__icp:hover {
  color: var(--color-accent) !important;
}

@media (max-width: 768px) {
  .app-footer__inner {
    grid-template-columns: 1fr;
    text-align: center;
  }
  .app-footer__logo {
    margin: 0 auto var(--space-sm);
  }
}
</style>
