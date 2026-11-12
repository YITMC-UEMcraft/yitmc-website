import { eventsData, type ClubEvent } from '@/data'

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
  return String(a.dateStart).localeCompare(String(b.dateStart))
}

function byStartDesc(a: ClubEvent, b: ClubEvent): number {
  return String(b.dateStart).localeCompare(String(a.dateStart))
}

/** 即将开始 / 进行中：精选优先，日期由近到远 */
export function upcomingEvents(limit?: number): ClubEvent[] {
  const list = eventsData.events
    .filter((e) => e.status === 'upcoming' || e.status === 'ongoing')
    .sort((a, b) => Number(b.featured) - Number(a.featured) || byStartAsc(a, b))
  return typeof limit === 'number' ? list.slice(0, limit) : list
}

/** 往期：精选优先，日期由近到远 */
export function pastEvents(limit?: number): ClubEvent[] {
  const list = eventsData.events
    .filter((e) => e.status === 'past')
    .sort((a, b) => Number(b.featured) - Number(a.featured) || byStartDesc(a, b))
  return typeof limit === 'number' ? list.slice(0, limit) : list
}

/** 首页用：优先近期，没有近期活动就展示最近往期 */
export function featuredEvents(limit = 3): ClubEvent[] {
  const upcoming = upcomingEvents()
  return (upcoming.length ? upcoming : pastEvents()).slice(0, limit)
}
