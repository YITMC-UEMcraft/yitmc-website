import { ref } from 'vue'
import { fetchMe, logout as apiLogout, type AuthUser } from '@/lib/auth'

const user = ref<AuthUser | null>(null)
const configured = ref(false)
const loaded = ref(false)
let pending: Promise<void> | null = null

export function useAuth() {
  async function refreshAuth(force = false): Promise<void> {
    if (pending && !force) return pending
    pending = (async () => {
      try {
        const state = await fetchMe()
        user.value = state.user
        configured.value = state.configured
      } catch {
        user.value = null
      } finally {
        loaded.value = true
        pending = null
      }
    })()
    return pending
  }

  function setUser(next: AuthUser | null) {
    user.value = next
  }

  async function logout(): Promise<void> {
    try {
      await apiLogout()
    } finally {
      user.value = null
    }
  }

  return { user, configured, loaded, refreshAuth, setUser, logout }
}
