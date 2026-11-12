import { worksData } from '@/data'

export function workCategoryLabel(key: string): string {
  return worksData.categories.find((c) => c.key === key)?.label || key
}
