/**
 * 把 2026-09-15 / 2026-09 / 2026.9 / 2026-9 之类的日期转成可比较的数字。
 * 月/日缺省时按当月/当日 1 号处理，仅用于内容排序。
 */
export function dateSortKey(value: string | number | null | undefined): number {
  if (typeof value === 'number') return value
  const match = String(value ?? '')
    .trim()
    .match(/^(\d{4})(?:[./-](\d{1,2}))?(?:[./-](\d{1,2}))?/)
  if (!match) return 0
  const year = Number(match[1])
  const month = Math.min(12, Math.max(1, Number(match[2] || 1)))
  const day = Math.min(31, Math.max(1, Number(match[3] || 1)))
  return year * 10000 + month * 100 + day
}
