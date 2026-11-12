/**
 * 构建后生成静态 sitemap.xml（publicDir 之外的 dist 根目录）。
 * 数据来自 src/data 的内置内容；后台新增内容在下次构建后进入 sitemap。
 */
import { readFileSync, writeFileSync } from 'node:fs'
import { join } from 'node:path'

const ROOT = process.cwd()
const DATA = join(ROOT, 'src', 'data')
const BASE = 'https://www.yitmc.cn'

function readJson(name) {
  return JSON.parse(readFileSync(join(DATA, name), 'utf8'))
}

function escapeXml(value) {
  return String(value)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&apos;')
}

function normalizeDate(date) {
  if (/^\d{4}-\d{2}-\d{2}$/.test(date)) return date
  if (/^\d{4}-\d{2}$/.test(date)) return `${date}-01`
  if (/^\d{4}$/.test(date)) return `${date}-01-01`
  return ''
}

function url(loc, lastmod = '', changefreq = 'monthly', priority = '0.6') {
  return [
    '  <url>',
    `    <loc>${escapeXml(loc)}</loc>`,
    ...(lastmod ? [`    <lastmod>${escapeXml(lastmod)}</lastmod>`] : []),
    `    <changefreq>${changefreq}</changefreq>`,
    `    <priority>${priority}</priority>`,
    '  </url>',
  ].join('\n')
}

const urls = []

const staticPages = [
  ['/', 'weekly', '1.0'],
  ['/works', 'weekly', '0.9'],
  ['/news', 'weekly', '0.9'],
  ['/events', 'weekly', '0.8'],
  ['/docs', 'weekly', '0.7'],
  ['/wall', 'weekly', '0.5'],
  ['/members', 'monthly', '0.6'],
  ['/open', 'monthly', '0.7'],
  ['/about', 'monthly', '0.7'],
  ['/join', 'monthly', '0.7'],
]
for (const [path, changefreq, priority] of staticPages) {
  urls.push(url(BASE + path, '', changefreq, priority))
}

for (const item of readJson('news.json').news || []) {
  if (item?.id == null) continue
  urls.push(url(`${BASE}/news/${encodeURIComponent(String(item.id))}`, normalizeDate(String(item.date || '')), 'monthly', '0.7'))
}

for (const item of readJson('works.json').works || []) {
  if (item?.id == null) continue
  urls.push(url(`${BASE}/works/${encodeURIComponent(String(item.id))}`, '', 'monthly', '0.6'))
}

for (const item of readJson('docs.json').docs || []) {
  if (!item?.id) continue
  urls.push(url(`${BASE}/docs/${encodeURIComponent(String(item.id))}`, normalizeDate(String(item.updatedAt || '')), 'monthly', '0.6'))
}

const xml = [
  '<?xml version="1.0" encoding="UTF-8"?>',
  '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
  ...urls,
  '</urlset>',
  '',
].join('\n')

writeFileSync(join(ROOT, 'dist', 'sitemap.xml'), xml)
console.log(`sitemap.xml generated: ${urls.length} urls`)
