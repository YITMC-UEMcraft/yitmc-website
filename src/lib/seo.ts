/**
 * 轻量文档头管理：title / description / canonical / OG / Twitter / JSON-LD。
 * 没有引入额外依赖，SPA 内按路由和详情数据手动调用。
 */

export const SITE_URL = 'https://www.yitmc.cn'
export const SITE_NAME = 'YITMC'
export const DEFAULT_TITLE = 'YITMC - 燕京理工学院MC玩家创作协会'
export const DEFAULT_DESCRIPTION = '燕京理工学院MC玩家创作协会(YITMC)官方网站 - 我的世界校园复刻工程展示与社团招新'
export const DEFAULT_IMAGE = '/logo/logo.png'

export interface SeoOptions {
  title?: string
  description?: string
  /** 站内路径，如 /news/1；用于 canonical 与 og:url */
  path?: string
  /** 站内绝对路径或完整 URL */
  image?: string
  type?: 'website' | 'article'
  jsonLd?: Record<string, unknown> | null
  noindex?: boolean
}

function absolute(url: string | undefined, fallback: string): string {
  const value = url || fallback
  return /^https?:\/\//i.test(value) ? value : SITE_URL + (value.startsWith('/') ? value : '/' + value)
}

function ensureMeta(attr: 'name' | 'property', key: string): HTMLMetaElement {
  let el = document.head.querySelector<HTMLMetaElement>(`meta[${attr}="${key}"]`)
  if (!el) {
    el = document.createElement('meta')
    el.setAttribute(attr, key)
    document.head.appendChild(el)
  }
  return el
}

function ensureLink(rel: string): HTMLLinkElement {
  let el = document.head.querySelector<HTMLLinkElement>(`link[rel="${rel}"]`)
  if (!el) {
    el = document.createElement('link')
    el.rel = rel
    document.head.appendChild(el)
  }
  return el
}

export function setSeo(options: SeoOptions = {}) {
  if (typeof document === 'undefined') return

  const title = options.title || DEFAULT_TITLE
  const description = options.description || DEFAULT_DESCRIPTION
  const canonical = SITE_URL + (options.path || '/')
  const image = absolute(options.image, DEFAULT_IMAGE)

  document.title = title

  ensureMeta('name', 'description').content = description
  ensureLink('canonical').href = canonical

  ensureMeta('property', 'og:site_name').content = SITE_NAME
  ensureMeta('property', 'og:title').content = title
  ensureMeta('property', 'og:description').content = description
  ensureMeta('property', 'og:type').content = options.type || 'website'
  ensureMeta('property', 'og:url').content = canonical
  ensureMeta('property', 'og:image').content = image

  ensureMeta('name', 'twitter:card').content = options.image ? 'summary_large_image' : 'summary'
  ensureMeta('name', 'twitter:title').content = title
  ensureMeta('name', 'twitter:description').content = description
  ensureMeta('name', 'twitter:image').content = image

  const robots = document.head.querySelector<HTMLMetaElement>('meta[name="robots"]')
  if (options.noindex) {
    ensureMeta('name', 'robots').content = 'noindex, nofollow'
  } else if (robots) {
    robots.remove()
  }

  document.head.querySelectorAll('script[data-yitmc-seo="jsonld"]').forEach((el) => el.remove())
  if (options.jsonLd) {
    const script = document.createElement('script')
    script.type = 'application/ld+json'
    script.dataset.yitmcSeo = 'jsonld'
    script.textContent = JSON.stringify(options.jsonLd)
    document.head.appendChild(script)
  }
}
