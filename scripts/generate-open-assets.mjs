/**
 * 生成「社团开放平台」素材清单。
 *
 * 只收录 YITMC 原创且可再分发的素材；成员照片、合照、官方证明、二维码和
 * 第三方 IP 图片不进入清单。输出 src/data/open-assets.json，供前台直接打包。
 */
import { readdirSync, statSync, writeFileSync } from 'node:fs'
import { basename, dirname, extname, join, relative } from 'node:path'

const ROOT = process.cwd()
const IMG_DIR = join(ROOT, 'img')
const OUT_FILE = join(ROOT, 'src', 'data', 'open-assets.json')
const IMAGE_EXT = new Set(['.png', '.jpg', '.jpeg', '.webp', '.gif'])

const CATEGORY_DEFS = [
  {
    id: 'brand',
    title: '品牌标识',
    description: 'YITMC 社团 Logo，可用于社团相关宣传与二次创作。',
  },
  {
    id: 'mascot',
    title: '吉祥物',
    description: '沃蒂娜与沃伦的官方形象素材。',
  },
  {
    id: 'campus',
    title: '校园复刻',
    description: 'YITMC 校园复刻工程的游戏内截图，预览使用 WebP，下载保留原始格式。',
  },
  {
    id: 'works',
    title: '建筑作品',
    description: '社团成员原创建筑作品截图。',
  },
]

const DISPLAY_NAMES = {
  'logo/logo': 'YITMC 社团 Logo',
  'mascot/vodina': '沃蒂娜',
  'mascot/warren': '沃伦',
}

const FORMAT_ORDER = { PNG: 0, JPG: 1, JPEG: 1, WEBP: 2, GIF: 3 }

const THIRD_PARTY_KEYWORDS = ['原神角色-丝柯克', '罗德岛']

const EXCLUDED_DEFS = [
  {
    id: 'people',
    title: '成员个人照片',
    reason: '涉及成员肖像权，未纳入开放下载。',
    test: (rel) => rel.startsWith('people/'),
  },
  {
    id: 'group-photos',
    title: '社团活动合照',
    reason: '涉及参与成员肖像权，未纳入开放下载。',
    test: (rel) => rel.startsWith('Background/社团合照/'),
  },
  {
    id: 'certificate',
    title: '官方社团证明',
    reason: '仅用于社团身份展示，不开放再分发。',
    test: (rel) => rel === '官方社团证明.jpg',
  },
  {
    id: 'qr-codes',
    title: 'QQ / 微信群二维码',
    reason: '群二维码会变化，不作为素材开放。',
    test: (rel) => rel === 'QQ群二维码.jpg' || rel === '微信群二维码.jpg',
  },
  {
    id: 'third-party',
    title: '第三方 IP 图片',
    reason: '原神、明日方舟等第三方作品版权不属于 YITMC。',
    test: (rel) => THIRD_PARTY_KEYWORDS.some((keyword) => rel.includes(keyword)),
  },
]

function walk(dir, output = []) {
  for (const entry of readdirSync(dir, { withFileTypes: true })) {
    const full = join(dir, entry.name)
    if (entry.isDirectory()) {
      walk(full, output)
    } else if (entry.isFile() && IMAGE_EXT.has(extname(entry.name).toLowerCase())) {
      output.push(full)
    }
  }
  return output
}

function classify(rel) {
  if (rel.startsWith('logo/')) return 'brand'
  if (rel.startsWith('mascot/')) return 'mascot'
  if (rel.startsWith('Background/复原工程/')) return 'campus'
  if (rel.startsWith('Background/其他建筑工程/')) return 'works'
  return null
}

function toUrl(rel) {
  return '/' + rel.split('/').map((part) => encodeURIComponent(part)).join('/')
}

function formatLabel(rel) {
  const ext = extname(rel).slice(1).toUpperCase()
  return ext === 'JPEG' ? 'JPG' : ext
}

const excludedCounts = Object.fromEntries(EXCLUDED_DEFS.map((item) => [item.id, 0]))
const groups = new Map()

for (const file of walk(IMG_DIR)) {
  const rel = relative(IMG_DIR, file).replaceAll('\\', '/')
  const excluded = EXCLUDED_DEFS.find((item) => item.test(rel))
  if (excluded) {
    excludedCounts[excluded.id] += 1
    continue
  }

  const category = classify(rel)
  if (!category) {
    console.warn(`skip unclassified image: ${rel}`)
    continue
  }

  const ext = extname(rel).toLowerCase()
  const groupKey = `${dirname(rel)}/${basename(rel, ext)}`
  const group = groups.get(groupKey) || {
    category,
    name: DISPLAY_NAMES[groupKey] || basename(rel, ext),
    preview: '',
    variants: [],
  }
  group.variants.push({
    format: formatLabel(rel),
    url: toUrl(rel),
    downloadName: basename(rel),
    bytes: statSync(file).size,
  })
  groups.set(groupKey, group)
}

const categories = CATEGORY_DEFS.map((definition) => {
  const assets = [...groups.entries()]
    .filter(([, group]) => group.category === definition.id)
    .map(([key, group]) => {
      const variants = group.variants.sort(
        (a, b) => (FORMAT_ORDER[a.format] ?? 99) - (FORMAT_ORDER[b.format] ?? 99),
      )
      const webp = variants.find((variant) => variant.format === 'WEBP')
      return {
        key,
        name: group.name,
        preview: (webp || variants[0]).url,
        bytes: variants.reduce((sum, variant) => sum + variant.bytes, 0),
        variants,
      }
    })
    .sort((a, b) => a.name.localeCompare(b.name, 'zh-CN'))

  return {
    ...definition,
    assets,
    fileCount: assets.reduce((sum, asset) => sum + asset.variants.length, 0),
    bytes: assets.reduce((sum, asset) => sum + asset.bytes, 0),
  }
})

const totalAssets = categories.reduce((sum, category) => sum + category.assets.length, 0)
const totalFiles = categories.reduce((sum, category) => sum + category.fileCount, 0)
const totalBytes = categories.reduce((sum, category) => sum + category.bytes, 0)

const excluded = EXCLUDED_DEFS.map((definition) => ({
  id: definition.id,
  title: definition.title,
  reason: definition.reason,
  fileCount: excludedCounts[definition.id],
})).filter((item) => item.fileCount > 0)

const payload = {
  license: 'MIT',
  licenseUrl: '/LICENSE.txt',
  noticeUrl: 'https://github.com/YITMC-UEMcraft/yitmc-website/blob/main/NOTICE.md',
  repoUrl: 'https://github.com/YITMC-UEMcraft/yitmc-website',
  repoZipUrl: 'https://github.com/YITMC-UEMcraft/yitmc-website/archive/refs/heads/main.zip',
  totalAssets,
  totalFiles,
  totalBytes,
  categories,
  excluded,
}

writeFileSync(OUT_FILE, JSON.stringify(payload, null, 2) + '\n')
console.log(
  `open-assets.json generated: ${totalAssets} assets / ${totalFiles} files, ` +
    `${(totalBytes / 1024 / 1024).toFixed(1)} MB`,
)
