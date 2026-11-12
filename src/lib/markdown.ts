import { marked } from 'marked'
import DOMPurify from 'dompurify'

export interface TocItem {
  id: string
  text: string
  level: 2 | 3
}

export interface RenderedMarkdown {
  html: string
  headings: TocItem[]
}

marked.setOptions({ gfm: true, breaks: false })

function slugify(text: string): string {
  return text
    .toLowerCase()
    .trim()
    .replace(/[^\p{L}\p{N}]+/gu, '-')
    .replace(/^-+|-+$/g, '')
    .slice(0, 64)
}

/** Markdown → 安全 HTML，并为 h2/h3 生成锚点与目录 */
export function renderMarkdown(markdown: string): RenderedMarkdown {
  const raw = marked.parse(markdown || '', { async: false }) as string
  const clean = DOMPurify.sanitize(raw)

  const container = document.createElement('div')
  container.innerHTML = clean

  container.querySelectorAll('a').forEach((a) => {
    if (/^https?:\/\//i.test(a.getAttribute('href') || '')) {
      a.setAttribute('target', '_blank')
      a.setAttribute('rel', 'noopener noreferrer')
    }
  })

  const headings: TocItem[] = []
  const used = new Set<string>()
  container.querySelectorAll('h2, h3').forEach((el, index) => {
    const text = el.textContent?.trim() || ''
    let id = slugify(text) || `section-${index + 1}`
    while (used.has(id)) id = `${id}-${index + 1}`
    used.add(id)
    el.id = id
    headings.push({ id, text, level: el.tagName === 'H2' ? 2 : 3 })
  })

  return { html: container.innerHTML, headings }
}
