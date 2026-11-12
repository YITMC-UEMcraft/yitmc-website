import { ref } from 'vue'

export type Theme = 'dark' | 'light'

const STORAGE_KEY = 'yitmc-theme'
const DARK_COLOR = '#1a1a1a'
const LIGHT_COLOR = '#f2ecdc'

const theme = ref<Theme>('dark')

function applyTheme(next: Theme) {
  if (typeof document === 'undefined') return
  document.documentElement.setAttribute('data-theme', next)
  document.documentElement.style.colorScheme = next
  const meta = document.querySelector<HTMLMetaElement>('meta[name="theme-color"]')
  if (meta) meta.content = next === 'light' ? LIGHT_COLOR : DARK_COLOR
  try {
    localStorage.setItem(STORAGE_KEY, next)
  } catch {
    // 隐私模式下忽略
  }
}

/** 在挂载前调用：读取本地偏好并同步主题色 */
export function initTheme() {
  if (typeof window === 'undefined') return
  let stored: string | null = null
  try {
    stored = localStorage.getItem(STORAGE_KEY)
  } catch {
    stored = null
  }
  theme.value = stored === 'light' ? 'light' : 'dark'
  applyTheme(theme.value)
}

export function useTheme() {
  function toggleTheme() {
    theme.value = theme.value === 'dark' ? 'light' : 'dark'
    applyTheme(theme.value)
  }

  return { theme, toggleTheme }
}
