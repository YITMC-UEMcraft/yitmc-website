<template>
  <PixelBackground>
    <AppHeader />
    <main class="open-page">
      <section class="page-hero">
        <h1 class="page-hero__title" data-reveal>社团开放平台</h1>
        <p class="page-hero__subtitle" data-reveal data-reveal-delay="1">
          YITMC 原创素材与网站源码，以 MIT 协议开放
        </p>
        <BlockDivider variant="grass" />
      </section>

      <div class="open-page__inner">
        <section class="open-intro">
          <article class="open-panel block-inset" data-reveal>
            <span class="open-eyebrow">OPEN LICENSE</span>
            <h2>MIT License</h2>
            <p>
              除特别标注外，本平台列出的原创素材与网站源代码均以 MIT 协议开放。你可以自由使用、修改、分发和商用，
              只需保留版权声明与许可证文本。
            </p>
            <div class="open-panel__actions">
              <a :href="openAssets.licenseUrl" download class="open-btn open-btn--primary">下载 LICENSE</a>
              <a :href="openAssets.noticeUrl" target="_blank" rel="noopener" class="open-btn">查看素材范围说明</a>
            </div>
          </article>

          <article class="open-panel block-inset" data-reveal data-reveal-delay="1">
            <span class="open-eyebrow">SOURCE CODE</span>
            <h2>网站源码</h2>
            <p>
              完整的 Vue 3 前端与 PHP 接口代码托管在 GitHub，仓库中包含本页展示的全部开放素材，
              以及构建、部署和维护脚本。
            </p>
            <div class="open-panel__actions">
              <a :href="openAssets.repoUrl" target="_blank" rel="noopener" class="open-btn open-btn--primary">打开 GitHub 仓库</a>
              <a :href="openAssets.repoZipUrl" class="open-btn">下载仓库 ZIP</a>
            </div>
          </article>
        </section>

        <div class="open-stats" data-reveal>
          <span><strong>{{ openAssets.totalAssets }}</strong> 组素材</span>
          <span><strong>{{ openAssets.totalFiles }}</strong> 个文件</span>
          <span><strong>{{ formatBytes(openAssets.totalBytes) }}</strong> 原始体积</span>
          <span><strong>MIT</strong> 开放协议</span>
        </div>

        <section v-for="category in openAssets.categories" :key="category.id" class="open-section">
          <header class="open-section__head" data-reveal>
            <div>
              <h2 class="open-section__title">{{ category.title }}</h2>
              <p class="open-section__desc">{{ category.description }}</p>
            </div>
            <span class="open-section__count">
              {{ category.assets.length }} 组 · {{ formatBytes(category.bytes) }}
            </span>
          </header>

          <div class="open-grid">
            <article
              v-for="asset in category.assets"
              :key="asset.key"
              class="open-card block-inset"
              data-reveal
            >
              <a :href="asset.preview" target="_blank" rel="noopener" class="open-card__preview">
                <img :src="asset.preview" :alt="asset.name" loading="lazy" decoding="async" />
              </a>
              <div class="open-card__body">
                <h3 class="open-card__name">{{ asset.name }}</h3>
                <div class="open-card__variants">
                  <a
                    v-for="variant in asset.variants"
                    :key="variant.url"
                    :href="variant.url"
                    :download="variant.downloadName"
                    class="open-download"
                  >
                    下载 {{ variant.format }}
                    <small>{{ formatBytes(variant.bytes) }}</small>
                  </a>
                </div>
              </div>
            </article>
          </div>
        </section>

        <section class="open-excluded block-inset" data-reveal>
          <h2>未开放的内容</h2>
          <p>为尊重成员肖像权与第三方版权，以下内容不纳入 MIT 开放范围：</p>
          <ul>
            <li v-for="item in openAssets.excluded" :key="item.id">
              <strong>{{ item.title }}</strong>
              <span>{{ item.reason }}（{{ item.fileCount }} 个文件）</span>
            </li>
          </ul>
        </section>

        <section class="open-notes block-inset" data-reveal>
          <h2>使用说明</h2>
          <ul>
            <li>MIT 许可证允许自由使用、修改、分发与商业使用，但必须保留版权声明和许可证文本。</li>
            <li>
              Minecraft 是 Mojang Synergies AB 的商标。游戏内截图涉及的 Minecraft 画面权利仍归 Mojang 所有，
              MIT 仅覆盖 YITMC 的原创建筑与截图创作部分。
            </li>
            <li>MIT 不授予使用 YITMC 名称、Logo 或吉祥物暗示官方认可、赞助或合作的权利。</li>
          </ul>
        </section>
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
import openAssets from '@/data/open-assets.json'

function formatBytes(bytes: number): string {
  if (bytes < 1024) return `${bytes} B`
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`
  return `${(bytes / 1024 / 1024).toFixed(1)} MB`
}
</script>

<style scoped>
.open-page {
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

.open-page__inner {
  max-width: var(--max-width);
  margin: 0 auto;
  padding: var(--space-2xl) var(--space-lg) var(--space-section);
}

.open-intro {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: var(--space-lg);
}

.open-panel {
  padding: var(--space-xl);
  background: var(--color-surface);
}

.open-eyebrow {
  display: block;
  margin-bottom: var(--space-xs);
  font-family: var(--font-pixel);
  font-size: 10px;
  letter-spacing: 0.08em;
  color: var(--color-accent);
}

.open-panel h2 {
  margin-bottom: var(--space-sm);
  font-size: var(--text-xl);
  color: var(--color-text);
}

.open-panel p {
  font-size: var(--text-sm);
  line-height: 1.8;
  color: var(--color-text-muted);
}

.open-panel__actions {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-sm);
  margin-top: var(--space-lg);
}

.open-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: var(--space-sm) var(--space-md);
  font-family: var(--font-display);
  font-size: var(--text-sm);
  color: var(--color-text) !important;
  background: var(--color-bg);
  border: var(--border-width-sm) solid var(--color-surface-light);
  transition: border-color 0.15s ease, color 0.15s ease;
}

.open-btn:hover {
  border-color: var(--color-accent);
  color: var(--color-accent) !important;
}

.open-btn--primary {
  color: #10200a !important;
  background: var(--color-accent);
  border-color: var(--color-accent-dim);
}

.open-btn--primary:hover {
  color: #10200a !important;
  background: var(--color-accent-hover);
}

.open-stats {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-around;
  gap: var(--space-md);
  margin-top: var(--space-xl);
  padding: var(--space-md) var(--space-lg);
  background: var(--color-surface);
  border-top: var(--border-width-sm) solid var(--color-surface-light);
  border-bottom: var(--border-width-sm) solid var(--color-surface-light);
  font-size: var(--text-sm);
  color: var(--color-text-muted);
}

.open-stats strong {
  font-family: var(--font-display);
  font-size: var(--text-lg);
  color: var(--color-accent-hover);
}

.open-section {
  margin-top: var(--space-3xl);
}

.open-section__head {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: var(--space-md);
  margin-bottom: var(--space-lg);
  padding-bottom: var(--space-sm);
  border-bottom: var(--border-width-sm) solid var(--color-surface-light);
}

.open-section__title {
  font-size: var(--text-xl);
  color: var(--color-text);
}

.open-section__desc {
  margin-top: 4px;
  font-size: var(--text-sm);
  color: var(--color-text-muted);
}

.open-section__count {
  flex: none;
  font-size: var(--text-xs);
  color: var(--color-text-muted);
}

.open-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  gap: var(--space-lg);
}

.open-card {
  display: flex;
  flex-direction: column;
  overflow: hidden;
  background: var(--color-surface);
}

.open-card__preview {
  display: block;
  aspect-ratio: 16 / 10;
  overflow: hidden;
  background: var(--color-bg);
}

.open-card__preview img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.25s ease;
}

.open-card__preview:hover img {
  transform: scale(1.03);
}

.open-card__body {
  display: flex;
  flex-direction: column;
  gap: var(--space-sm);
  padding: var(--space-md);
}

.open-card__name {
  font-family: var(--font-display);
  font-size: var(--text-base);
  color: var(--color-text);
  overflow-wrap: anywhere;
}

.open-card__variants {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-xs);
}

.open-download {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 5px 9px;
  font-size: var(--text-xs);
  color: var(--color-accent) !important;
  background: var(--color-bg);
  border: var(--border-width-sm) solid var(--color-accent-dim);
  transition: background 0.15s ease, color 0.15s ease;
}

.open-download:hover {
  color: #10200a !important;
  background: var(--color-accent);
}

.open-download small {
  font-size: 10px;
  opacity: 0.75;
}

.open-excluded,
.open-notes {
  margin-top: var(--space-3xl);
  padding: var(--space-xl);
  background: var(--color-surface);
}

.open-excluded h2,
.open-notes h2 {
  margin-bottom: var(--space-sm);
  font-size: var(--text-xl);
  color: var(--color-text);
}

.open-excluded > p,
.open-notes > p {
  font-size: var(--text-sm);
  color: var(--color-text-muted);
}

.open-excluded ul,
.open-notes ul {
  display: flex;
  flex-direction: column;
  gap: var(--space-sm);
  margin-top: var(--space-md);
  padding-left: 1.2em;
  font-size: var(--text-sm);
  line-height: 1.8;
  color: var(--color-text-muted);
}

.open-excluded li strong {
  margin-right: var(--space-xs);
  color: var(--color-text);
}

@media (max-width: 900px) {
  .open-intro {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 640px) {
  .open-section__head {
    align-items: flex-start;
    flex-direction: column;
    gap: var(--space-xs);
  }

  .open-stats {
    justify-content: flex-start;
  }
}
</style>
