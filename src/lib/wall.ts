const API = '/admin-api.php'

export interface WallMessage {
  id: number
  name: string
  content: string
  createdAt: number
}

export interface WallPage {
  messages: WallMessage[]
  total: number
  page: number
  pages: number
}

async function post(body: Record<string, unknown>): Promise<any> {
  const res = await fetch(API, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    credentials: 'same-origin',
    body: JSON.stringify(body),
  })
  const json = await res.json().catch(() => null)
  if (!json || !json.ok) {
    const message = json?.error || `请求失败（HTTP ${res.status}）`
    const error = new Error(message) as Error & { code?: string; status?: number }
    error.code = json?.code
    error.status = res.status
    throw error
  }
  return json
}

export async function fetchWall(page = 1, limit = 10): Promise<WallPage> {
  const json = await post({ action: 'wall_list', page, limit })
  return {
    messages: Array.isArray(json.messages) ? json.messages : [],
    total: Number(json.total) || 0,
    page: Number(json.page) || 1,
    pages: Number(json.pages) || 1,
  }
}

export async function postWall(name: string, content: string, website = ''): Promise<{ status: string }> {
  const json = await post({ action: 'wall_post', name, content, website })
  return { status: String(json.status || 'pending') }
}
