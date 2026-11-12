const API = '/admin-api.php'

export interface SiteMember {
  id: number
  nickname: string
  avatar: string
  playerName: string
  bio: string
  isPublic: boolean
  siteRole: string
  createdAt: number
}

export async function fetchSiteMembers(): Promise<SiteMember[]> {
  const res = await fetch(API, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    credentials: 'same-origin',
    body: JSON.stringify({ action: 'site_members' }),
  })
  const json = await res.json().catch(() => null)
  if (!json || !json.ok) {
    const message = json?.error || `请求失败（HTTP ${res.status}）`
    throw new Error(message)
  }
  return Array.isArray(json.members) ? json.members : []
}
