import siteConfigJson from './site-config.json'
import worksJson from './works.json'
import newsJson from './news.json'
import membersJson from './members.json'
import statsJson from './stats.json'
import eventsJson from './events.json'
import docsJson from './docs.json'
import serversJson from './servers.json'
import pclJson from './pcl.json'

/** 社团活动 */
export interface ClubEvent {
  id: number
  title: string
  status: 'upcoming' | 'ongoing' | 'past'
  dateStart: string
  dateEnd: string
  dateLabel: string
  location: string
  summary: string
  cover: string
  link: string
  featured: boolean
}

/** 社团动态 */
export interface NewsItem {
  id: number
  title: string
  date: string
  type: string
  summary: string
  content: string
  image?: string
}

/** 指南文档 */
export interface ClubDoc {
  id: string
  title: string
  category: string
  content: string
  order: number
  updatedAt: string
}

/** 我的世界服务器状态配置 */
export interface McServerConfig {
  id: string
  name: string
  note: string
  address: string
  /** 对外展示地址（不传则同 address），用于隐藏真实地址 */
  displayAddress?: string
  port: number
  /** SRV 解析失败时的兜底端口（PCL 主页使用） */
  fallbackPort?: number
  edition: 'java' | 'bedrock'
}

/** PCL2 自定义主页配置 */
export interface PclLink {
  label: string
  url: string
}

export interface PclConfig {
  title: string
  intro: string
  newsCount: number
  links: PclLink[]
}

/**
 * 全站数据的唯一出口。
 * 默认值来自打包内置的 src/data/*.json；
 * 应用启动前会尝试从 /data/*.json（服务器上由管理后台维护的可编辑副本）
 * 拉取最新内容进行原位覆盖，拉取失败则静默回退到内置数据。
 */
export const siteConfig: typeof siteConfigJson = siteConfigJson
export const worksData: typeof worksJson = worksJson
export const newsData: { news: NewsItem[] } = newsJson as { news: NewsItem[] }
export const membersData: typeof membersJson = membersJson
export const statsData: typeof statsJson = statsJson
export const eventsData: { events: ClubEvent[] } = eventsJson as { events: ClubEvent[] }
export const docsData: { docs: ClubDoc[] } = docsJson as { docs: ClubDoc[] }
export const serversData: { servers: McServerConfig[] } = serversJson as { servers: McServerConfig[] }
export const pclData: PclConfig = pclJson as PclConfig

const FILES = [
  { name: 'site-config.json', data: siteConfig },
  { name: 'works.json', data: worksData },
  { name: 'news.json', data: newsData },
  { name: 'members.json', data: membersData },
  { name: 'stats.json', data: statsData },
  { name: 'events.json', data: eventsData },
  { name: 'docs.json', data: docsData },
  { name: 'servers.json', data: serversData },
  { name: 'pcl.json', data: pclData as unknown as Record<string, unknown> },
] as const

function replaceInPlace(target: Record<string, unknown>, source: Record<string, unknown>) {
  for (const key of Object.keys(target)) delete target[key]
  Object.assign(target, source)
}

function fetchWithTimeout(url: string, ms: number): Promise<Response> {
  const ctrl = new AbortController()
  const timer = setTimeout(() => ctrl.abort(), ms)
  return fetch(url, { cache: 'no-store', signal: ctrl.signal }).finally(() => clearTimeout(timer))
}

/** 尝试从服务器加载后台维护的最新数据；任何失败都不影响站点正常展示。 */
export async function loadRemoteData(): Promise<void> {
  await Promise.all(
    FILES.map(async ({ name, data }) => {
      try {
        const res = await fetchWithTimeout('/data/' + name, 2500)
        if (!res.ok) return
        replaceInPlace(data, await res.json())
      } catch {
        /* 使用内置兜底数据 */
      }
    }),
  )
}
