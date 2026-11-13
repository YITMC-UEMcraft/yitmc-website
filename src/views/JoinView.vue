<template>
  <PixelBackground>
    <AppHeader />
    <main class="join-page">
      <section class="page-hero">
        <h1 class="page-hero__title" data-reveal>加入我们</h1>
        <p class="page-hero__subtitle" data-reveal data-reveal-delay="1">一起用方块创造无限可能！</p>
        <BlockDivider variant="grass" />
      </section>

      <div class="join-page__inner" data-reveal data-reveal-delay="2">
        <div class="join-page__grid">
          <div class="join-page__section">
            <h2><AppIcon name="message-circle" :size="18" /> 加入QQ群</h2>
            <CopyText label="QQ群号" :text="config.qqGroup" />
            <a :href="config.qqGroupJoinUrl" target="_blank" rel="noopener" class="join-page__direct-link">
              <PixelButton variant="primary" block>一键加群</PixelButton>
            </a>
          </div>

          <div class="join-page__section">
            <h2><AppIcon name="smartphone" :size="18" /> QQ群二维码</h2>
            <QRCodeCard title="QQ群" :qr-url="config.qqGroupQrUrl" hint="扫描二维码加入QQ群" />
          </div>
        </div>

        <div class="join-page__grid">
          <div class="join-page__section">
            <h2><AppIcon name="heart" :size="18" /> 微信群二维码</h2>
            <QRCodeCard title="微信群" :qr-url="config.wechatGroupQrUrl" hint="扫描二维码加入微信群" />
          </div>

          <div class="join-page__section">
            <h2><AppIcon name="shirt" :size="18" /> 皮肤站</h2>
            <p class="join-page__desc">我们使用 YITMC 自建皮肤站，上传和管理你的 Minecraft 皮肤</p>
            <a :href="config.skinStationUrl" target="_blank" rel="noopener" class="join-page__direct-link">
              <PixelButton variant="gold" block>前往 YITMC 皮肤站</PixelButton>
            </a>
          </div>
        </div>

        <div class="join-page__section">
          <h2><AppIcon name="gamepad" :size="18" /> PCL2 自定义主页</h2>
          <p class="join-page__desc">
            在 PCL2 中打开「设置 → 个性化 → 自定义主页」，启用后填入下面的地址，
            就能在启动器里直接看到 YITMC 的服务器状态、社团资讯和快捷入口。
          </p>
          <CopyText label="PCL2 主页地址" text="https://www.yitmc.cn/homepage/" />
        </div>

        <div class="join-page__section">
          <h2><AppIcon name="smartphone" :size="18" /> 关注我们</h2>
          <div class="join-page__social-grid">
            <SocialBlock icon="music" platform="抖音" :handle="config.douyinHandle" :href="config.douyinUrl" />
            <SocialBlock icon="video" platform="B站" :handle="config.bilibiliHandle" :href="config.bilibiliUrl" />
          </div>
        </div>

        <div class="join-page__section block-inset">
          <h2><AppIcon name="help-circle" :size="18" /> 常见问题</h2>
          <div class="join-page__faq">
            <details class="join-page__faq-item">
              <summary>加入社团有什么要求？</summary>
              <p>只要是燕京理工学院在校学生，对Minecraft或建筑创作有兴趣即可加入，无任何门槛！</p>
            </details>
            <details class="join-page__faq-item">
              <summary>我没有Minecraft正版账号可以加入吗？</summary>
              <p>可以！社团服务器支持多种登录方式，具体请联系管理员获取帮助。</p>
            </details>
            <details class="join-page__faq-item">
              <summary>社团活动一般在什么时间？</summary>
              <p>线上活动灵活安排，线下活动通常安排在周末或课余时间，不影响正常学习。</p>
            </details>
            <details class="join-page__faq-item">
              <summary>不会建筑怎么办？</summary>
              <p>没关系！社团有技术培训和指导，只要你有兴趣和热情，我们可以一起学习进步。</p>
            </details>
          </div>
        </div>
      </div>
    </main>
    <AppFooter />
  </PixelBackground>
</template>

<script setup lang="ts">
import PixelBackground from '@/components/layout/PixelBackground.vue'
import AppHeader from '@/components/layout/AppHeader.vue'
import AppFooter from '@/components/layout/AppFooter.vue'
import BlockDivider from '@/components/ui/BlockDivider.vue'
import PixelButton from '@/components/ui/PixelButton.vue'
import SocialBlock from '@/components/ui/SocialBlock.vue'
import CopyText from '@/components/join/CopyText.vue'
import AppIcon from '@/components/icons/AppIcon.vue'
import QRCodeCard from '@/components/join/QRCodeCard.vue'
import { siteConfig } from '@/data'

const config = siteConfig
</script>

<style scoped>
.join-page {
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

.join-page__inner {
  max-width: 800px;
  margin: 0 auto;
  padding: var(--space-2xl) var(--space-lg) var(--space-section);
  display: flex;
  flex-direction: column;
  gap: var(--space-xl);
}

.join-page__section {
  padding: var(--space-xl);
  background: var(--color-surface);
  border: var(--border-width) solid var(--color-surface-light);
}

.join-page__section h2 {
  font-family: var(--font-pixel);
  font-size: var(--text-sm);
  color: var(--color-accent);
  margin-bottom: var(--space-lg);
  text-shadow: 1px 1px 0px rgba(0, 0, 0, 0.5);
}

.join-page__grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--space-lg);
}

.join-page__direct-link {
  display: block;
  margin-top: var(--space-md);
}

.join-page__desc {
  font-size: var(--text-sm);
  color: var(--color-text-muted);
  margin-bottom: var(--space-md);
  line-height: 1.6;
}

.join-page__social-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: var(--space-md);
}

.join-page__faq-item {
  border-top: var(--border-width-sm) solid var(--color-surface-light);
}

.join-page__faq-item:last-child {
  border-bottom: var(--border-width-sm) solid var(--color-surface-light);
}

.join-page__faq-item summary {
  font-family: var(--font-display);
  font-size: var(--text-base);
  color: var(--color-text);
  padding: var(--space-md);
  cursor: pointer;
  list-style: none;
}

.join-page__faq-item summary::before {
  content: '\25B6';
  font-size: var(--text-xs);
  color: var(--color-accent);
}

.join-page__faq-item[open] summary::before {
  content: '\25BC';
}

.join-page__faq-item p {
  font-size: var(--text-sm);
  color: var(--color-text-muted);
  padding: 0 var(--space-md) var(--space-md);
  line-height: 1.6;
}

@media (max-width: 768px) {
  .join-page__grid {
    grid-template-columns: 1fr;
  }
}
</style>
