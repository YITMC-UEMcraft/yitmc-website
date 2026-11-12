const API = '/admin-api.php'

export interface SocialLink {
  label: string
  url: string
}

export interface PlayerProfile {
  uuid: string
  name: string
}

export interface AuthUser {
  id: number
  nickname: string
  avatar: string
  playerUuid: string
  playerName: string
  bio: string
  contact?: string
  socialLinks: SocialLink[]
  email?: string
  players?: PlayerProfile[]
  isPublic?: number
  showContact?: number
  createdAt: number
}

export interface AuthState {
  user: AuthUser | null
  configured: boolean
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
    const error = new Error(json?.error || `请求失败（HTTP ${res.status}）`) as Error & {
      code?: string
      status?: number
    }
    error.code = json?.code
    error.status = res.status
    throw error
  }
  return json
}

export async function fetchMe(): Promise<AuthState> {
  const json = await post({ action: 'oidc_me' })
  return {
    user: json.user || null,
    configured: Boolean(json.configured),
  }
}

export async function logout(): Promise<void> {
  await post({ action: 'oidc_logout' })
}

export async function updateProfile(input: {
  bio: string
  contact: string
  socialLinks: SocialLink[]
  playerUuid: string
  isPublic: boolean
  showContact: boolean
}): Promise<AuthUser> {
  const json = await post({
    action: 'user_update',
    bio: input.bio,
    contact: input.contact,
    socialLinks: input.socialLinks,
    playerUuid: input.playerUuid,
    isPublic: input.isPublic ? 1 : 0,
    showContact: input.showContact ? 1 : 0,
  })
  return json.user as AuthUser
}

export async function fetchPublicUser(id: number | string): Promise<AuthUser> {
  const json = await post({ action: 'user_public', id: String(id) })
  return json.user as AuthUser
}

export function loginHref(returnPath = '/account'): string {
  return `/admin-api.php?action=oidc_start&return=${encodeURIComponent(returnPath)}`
}
