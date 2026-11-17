const API = '/admin-api.php'

export interface SponsorPublic {
  id: number
  name: string
  type: string
  message: string
  link: string
  date: string
  visible: boolean
  userId?: number
  source?: string
}

export interface SponsorStatus {
  configured: boolean
  pageUrl: string
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

export async function fetchSponsorStatus(): Promise<SponsorStatus> {
  const json = await post({ action: 'sponsor_status' })
  return {
    configured: Boolean(json.configured),
    pageUrl: String(json.pageUrl || ''),
  }
}

export async function fetchSponsorCode(): Promise<string> {
  const json = await post({ action: 'sponsor_code' })
  return String(json.code || '')
}

export async function claimSponsor(
  outTradeNo: string,
  anonymous: boolean,
): Promise<{ already: boolean; sponsor: SponsorPublic }> {
  const json = await post({
    action: 'sponsor_claim',
    outTradeNo,
    anonymous: anonymous ? 1 : 0,
  })
  return {
    already: Boolean(json.already),
    sponsor: json.sponsor as SponsorPublic,
  }
}
