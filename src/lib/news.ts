import { newsData, type NewsItem } from '@/data'
import { dateSortKey } from './date'

export interface NewsTypeMeta {
  label: string
  icon: string
}

const NEWS_TYPE_META: Record<string, NewsTypeMeta> = {
  announcement: { label: '公告', icon: 'megaphone' },
  event: { label: '活动', icon: 'party-popper' },
  project: { label: '项目进展', icon: 'construction' },
  achievement: { label: '成果', icon: 'trophy' },
}

export function newsTypeMeta(type: string): NewsTypeMeta {
  return NEWS_TYPE_META[type] || { label: '动态', icon: 'clipboard' }
}

/** 动态严格按日期由新到旧；同日期按 ID 由大到小。 */
export function sortedNews(items: NewsItem[] = newsData.news): NewsItem[] {
  return [...items].sort((a, b) => {
    const diff = dateSortKey(b.date) - dateSortKey(a.date)
    return diff !== 0 ? diff : Number(b.id) - Number(a.id)
  })
}
