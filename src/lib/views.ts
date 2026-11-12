const API = '/admin-api.php'

async function post(body: Record<string, unknown>): Promise<any> {
  const res = await fetch(API, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    credentials: 'same-origin',
    body: JSON.stringify(body),
  })
  const json = await res.json().catch(() => null)
  if (!json || !json.ok) {
    const error = new Error(json?.error || `请求失败（HTTP ${res.status}）`) as Error & { status?: number }
    error.status = res.status
    throw error
  }
  return json
}

export async function fetchNewsViews(): Promise<Record<string, number>> {
  const json = await post({ action: 'news_views' })
  const counts: Record<string, number> = {}
  if (json.counts && typeof json.counts === 'object') {
    for (const [key, value] of Object.entries(json.counts)) {
      counts[key] = Number(value) || 0
    }
  }
  return counts
}

export async function recordNewsView(id: number | string): Promise<number> {
  const json = await post({ action: 'news_view', id: String(id) })
  return Number(json.count) || 0
}

export function formatViews(count: number): string {
  if (count >= 10000) return (count / 10000).toFixed(1).replace(/\.0$/, '') + 'w'
  if (count >= 1000) return (count / 1000).toFixed(1).replace(/\.0$/, '') + 'k'
  return String(count)
}
