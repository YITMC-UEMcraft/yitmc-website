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
