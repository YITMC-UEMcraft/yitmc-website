import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import { loadRemoteData } from './data'
import './styles/tokens.css'
import './styles/fonts.css'
import './styles/blocks.css'
import './styles/animations.css'
import { initTheme } from './composables/useTheme'

initTheme()

const app = createApp(App)
app.use(router)

// 先尝试加载管理后台维护的最新数据（带超时兜底），再挂载应用
loadRemoteData().finally(() => {
  app.mount('#app')
})
