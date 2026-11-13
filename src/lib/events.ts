import { eventsData, type ClubEvent } from '@/data'
import { dateSortKey } from './date'

export interface EventStatusMeta {
  label: string
  className: string
}

const EVENT_STATUS_META: Record<string, EventStatusMeta> = {
  upcoming: { label: '即将到来', className: 'upcoming' },
  ongoing: { label: '进行中', className: 'ongoing' },
  past: { label: '已结束', className: 'past' },
}

export function eventStatusMeta(status: string): EventStatusMeta {
  return EVENT_STATUS_META[status] || { label: '活动', className: 'past' }
}

function byStartAsc(a: ClubEvent, b: ClubEvent): number {
  return dateSortKey(a.dateStart) - dateSortKey(b.dateStart) || a.id - b.id
}

function byStartDesc(a: ClubEvent, b: ClubEvent): number {
  return dateSortKey(b.dateStart) - dateSortKey(a.dateStart) || b.id - a.id
}

/** 即将开始 / 进行中：严格按开始时间由近到远 */
export function upcomingEvents(limit?: number): ClubEvent[] {
  const list = eventsData.events
    .filter((e) => e.status === 'upcoming' || e.status === 'ongoing')
    .sort(byStartAsc)
  return typeof limit === 'number' ? list.slice(0, limit) : list
}

/** 往期：严格按开始时间由新到旧 */
export function pastEvents(limit?: number): ClubEvent[] {
  const list = eventsData.events
    .filter((e) => e.status === 'past')
    .sort(byStartDesc)
  return typeof limit === 'number' ? list.slice(0, limit) : list
}

/** 首页用：优先近期，没有近期活动就展示最近往期 */
export function featuredEvents(limit = 3): ClubEvent[] {
  const upcoming = upcomingEvents()
  return (upcoming.length ? upcoming : pastEvents()).slice(0, limit)
}
