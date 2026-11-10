<template>
  <section class="server">
    <div class="server__inner">
      <div class="server__head" data-reveal>
        <h2 class="server__heading">服务器状态</h2>
        <div class="server__refresh">
          <span v-if="lastUpdated" class="server__refresh-time">上次更新 {{ lastUpdated }}</span>
          <button
            type="button"
            class="server__refresh-btn"
            :class="{ 'is-busy': isRefreshing }"
            :disabled="isRefreshing"
            @click="refreshAll"
          >
            {{ isRefreshing ? '刷新中…' : '刷新状态' }}
          </button>
        </div>
      </div>

      <div class="server__list">
        <div
          v-for="(entry, index) in entries"
          :key="entry.config.id"
          class="server__reveal"
          data-reveal
          :style="{ transitionDelay: `${index * 90}ms` }"
        >
          <!-- 查询中：骨架卡片 + 阶段进度 -->
          <Transition name="card-swap" mode="out-in">
            <div v-if="entry.loading" :key="`loading-${entry.config.id}`" class="server-card is-loading">
              <div class="server-card__header">
                <div class="server-card__favicon server-card__favicon--empty"></div>
                <div class="server-card__title">
                  <div class="server-card__name-row">
                    <h3 class="server-card__name">{{ entry.config.name }}</h3>
                    <span v-if="entry.config.note" class="server-card__note">{{ entry.config.note }}</span>
                    <span class="server-card__badge is-loading"><i class="server-card__dot"></i>查询中</span>
                  </div>
                  <div class="server-card__addr">
                    <code class="server-card__address">{{ displayAddress(entry.config) }}</code>
                    <span class="server-card__edition">Java 版</span>
                  </div>
                </div>
              </div>
              <div class="server-card__progress">
                <div class="server-card__progress-bar">
                  <div class="server-card__progress-fill" :style="{ width: entry.progress + '%' }"></div>
                </div>
                <div class="server-card__progress-text">{{ phaseLabel(entry.phase) }}</div>
              </div>
              <div class="server-card__stats">
                <div v-for="label in STAT_LABELS" :key="label" class="server-card__stat">
                  <span class="server-card__stat-val">--</span>
                  <span class="server-card__stat-label">{{ label }}</span>
                </div>
              </div>
            </div>

            <!-- 结果卡片 -->
            <div
              v-else
              :key="`card-${entry.config.id}`"
              class="server-card"
              :class="{ 'is-offline': !entry.status?.online }"
            >
              <div class="server-card__header">
                <img
                  v-if="entry.status?.favicon"
                  :src="entry.status.favicon"
                  alt=""
                  width="64"
                  height="64"
                  class="server-card__favicon"
                />
                <div v-else class="server-card__favicon server-card__favicon--empty"></div>
                <div class="server-card__title">
                  <div class="server-card__name-row">
                    <h3 class="server-card__name">{{ entry.config.name }}</h3>
                    <span v-if="entry.config.note" class="server-card__note">{{ entry.config.note }}</span>
                    <span class="server-card__badge" :class="entry.status?.online ? 'is-online' : 'is-offline'">
                      <i class="server-card__dot"></i>{{ entry.status?.online ? '在线' : '离线' }}
                    </span>
                  </div>
                  <div class="server-card__addr">
                    <code class="server-card__address">{{ displayAddress(entry.config) }}</code>
                    <span class="server-card__edition">Java 版</span>
                    <button
                      type="button"
                      class="server-card__copy"
                      :class="{ 'is-copied': entry.copied }"
                      @click="copyAddress(entry)"
                    >
                      {{ entry.copied ? '已复制' : '复制' }}
                    </button>
                  </div>
                </div>
              </div>

              <div class="server-card__motd" :class="{ 'server-card__motd--empty': !motdHtml(entry) }">
                <template v-if="entry.status?.online">
                  <span v-if="motdHtml(entry)" v-html="motdHtml(entry)"></span>
                  <template v-else>暂无 MOTD</template>
                </template>
                <template v-else>服务器当前无法连接，请稍后再试</template>
              </div>

              <div class="server-card__stats">
                <div class="server-card__stat server-card__stat--players">
                  <span class="server-card__stat-val">
                    {{ entry.status?.online ? `${entry.status.playersOnline} / ${entry.status.playersMax}` : '--' }}
                  </span>
                  <span class="server-card__stat-label">在线 / 最大</span>
                  <div v-if="entry.status?.online" class="server-card__tooltip">
                    <ul class="server-card__players">
                      <li v-for="player in entry.status.playerList.slice(0, 24)" :key="player">{{ player }}</li>
                    </ul>
                  </div>
                </div>
                <div class="server-card__stat">
                  <span
                    class="server-card__stat-val"
                    :class="latencyClass(entry.status?.latency)"
                  >{{ entry.status?.latency != null ? entry.status.latency + ' ms' : '--' }}</span>
                  <span class="server-card__stat-label">延迟</span>
                </div>
                <div class="server-card__stat">
                  <span
                    class="server-card__stat-val"
                    :class="entry.status?.tps ? tpsClass(entry.status.tps.tps1m) : ''"
                    :title="entry.status?.tps ? tpsTitle(entry.status.tps) : undefined"
                  >{{ entry.status?.tps ? entry.status.tps.tps1m.toFixed(1) : '--' }}</span>
                  <span class="server-card__stat-label">TPS</span>
                </div>
                <div class="server-card__stat">
                  <span
                    class="server-card__stat-val"
                    :title="entry.status?.online ? entry.status.version : undefined"
                  >{{ shortVersion(entry.status?.version) }}</span>
                  <span class="server-card__stat-label">版本</span>
                </div>
              </div>
            </div>
          </Transition>
        </div>
      </div>

      <!-- 主服 Dynmap 实时地图入口 -->
      <a
        class="server-map"
        href="https://srvlmap.yitmc.cn/"
        target="_blank"
        rel="noopener"
        data-reveal
      >
        <span class="server-map__icon">
          <AppIcon name="map" :size="26" />
        </span>
        <span class="server-map__body">
          <span class="server-map__title">社团服务器实时地图</span>
          <span class="server-map__desc">在浏览器中实时浏览燕通联合主服的地形、建筑与在线玩家位置</span>
        </span>
        <span class="server-map__cta">
          打开地图
          <AppIcon name="chevron-right" :size="14" />
        </span>
      </a>
    </div>
  </section>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { servers, type McServerConfig } from '@/data'
import AppIcon from '@/components/icons/AppIcon.vue'

/* ---------------- 配置 ---------------- */

const STAT_LABELS = ['在线 / 最大', '延迟', 'TPS', '版本']

/** TPS 实时数据（由服务器侧 tps-export 定时任务经 status-api 透出） */
interface TpsInfo {
  tps1m: number
  tps5m: number | null
  tps15m: number | null
  mspt: number | null
  age: number | null
}

interface NormalizedStatus {
  online: boolean
  version: string
  playersOnline: number
  playersMax: number
  playerList: string[]
  motd: string
  favicon: string
  /** SRV 解析结果（用于延迟查询） */
  srvHost: string
  srvPort: number
  latency: number | null
  tps: TpsInfo | null
}

interface ServerEntry {
  config: McServerConfig
  loading: boolean
  progress: number
  phase: PhaseKey
  status: NormalizedStatus | null
  copied: boolean
}

const MCSTATUS_API = 'https://api.mcstatus.io/v2/status/java/'
const MCSRVSTAT_API = 'https://api.mcsrvstat.us/3/'
const MINETOOLS_API = 'https://api.minetools.eu/ping/'
// 同源状态代理（status-api.php）：由官网服务器直查并缓存，优先于海外公共 API
const LOCAL_STATUS_API = '/status-api.php'

const entries = reactive<ServerEntry[]>(servers.map((config) => ({
  config,
  loading: true,
  progress: 5,
  phase: 'dns' as PhaseKey,
  status: null,
  copied: false,
})))

const isRefreshing = ref(false)
const lastUpdated = ref('')

/* ---------------- 地址与安全校验 ---------------- */

/** 显示地址（隐藏地址的服务器只展示打码形式） */
function displayAddress(config: McServerConfig): string {
  const addr = config.displayAddress ?? config.address
  return config.port ? `${addr}:${config.port}` : addr
}

/** 复制内容与 uemcraft 一致：`地址 -p 端口`（无自定义端口则只复制地址） */
function copyFormat(config: McServerConfig): string {
  const addr = config.displayAddress ?? config.address
  return config.port ? `${addr} -p ${config.port}` : addr
}

/** 配置地址仅允许公网域名/IP，防止把内网或环回地址拼进查询 URL */
function isPublicHost(host: string): boolean {
  if (!/^[a-z0-9.-]+$/i.test(host)) return false
  if (/^(localhost|127\.|0\.|10\.|192\.168\.|169\.254\.|172\.(1[6-9]|2\d|3[01])\.)/i.test(host)) return false
  return true
}

/* ---------------- 状态查询 ---------------- */

type PhaseKey = 'dns' | 'srv' | 'connect' | 'handshake' | 'status' | 'ping' | 'done'

const PHASES: Array<{ key: PhaseKey; label: string; pct: number; at: number }> = [
  { key: 'dns',      label: '正在解析 DNS…',   pct: 15, at: 0 },
  { key: 'srv',      label: '正在查找 SRV…',   pct: 25, at: 350 },
  { key: 'connect',  label: '正在连接…',       pct: 40, at: 750 },
  { key: 'handshake',label: '正在握手…',       pct: 55, at: 1300 },
  { key: 'status',   label: '正在获取状态…',   pct: 70, at: 2000 },
  { key: 'ping',     label: '正在测延迟…',     pct: 85, at: 3000 },
]

function phaseLabel(key: PhaseKey): string {
  return PHASES.find((p) => p.key === key)?.label ?? '正在开始…'
}

/** 阶段进度动画：按时间表推进，结果到达后立即结束 */
function startPhaseTimer(entry: ServerEntry) {
  entry.progress = 5
  entry.phase = 'dns'
  const timers = PHASES.slice(1).map((p) =>
    setTimeout(() => {
      entry.phase = p.key
      entry.progress = p.pct
    }, p.at),
  )
  return () => timers.forEach(clearTimeout)
}

async function fetchJson<T>(url: string, timeoutMs: number): Promise<T> {
  const ctrl = new AbortController()
  const timer = setTimeout(() => ctrl.abort(), timeoutMs)
  try {
    const res = await fetch(url, { signal: ctrl.signal, cache: 'no-store' })
    if (!res.ok) throw new Error(`HTTP ${res.status}`)
    return await res.json() as T
  } finally {
    clearTimeout(timer)
  }
}

function normalizeMcStatusIo(j: any): NormalizedStatus {
  const list = j?.players?.list ?? []
  return {
    online: !!j?.online,
    version: j?.version?.name_clean ?? '',
    playersOnline: j?.players?.online ?? 0,
    playersMax: j?.players?.max ?? 0,
    playerList: list.map((p: any) => (typeof p === 'string' ? p : (p?.name_clean ?? p?.name_raw ?? ''))).filter(Boolean),
    motd: j?.motd?.raw ?? '',
    favicon: j?.icon ?? '',
    srvHost: j?.srv_record?.host ?? '',
    srvPort: j?.srv_record?.port ?? 0,
    latency: null,
    tps: null,
  }
}

function normalizeMcsrvstat(j: any): NormalizedStatus {
  const list = j?.players?.list ?? []
  return {
    online: !!j?.online,
    version: j?.version ?? '',
    playersOnline: j?.players?.online ?? 0,
    playersMax: j?.players?.max ?? 0,
    playerList: list.map((p: any) => (typeof p === 'string' ? p : (p?.name ?? ''))).filter(Boolean),
    motd: Array.isArray(j?.motd?.raw) ? j.motd.raw.join('\n') : (j?.motd?.raw ?? ''),
    favicon: j?.icon ?? '',
    srvHost: '',
    srvPort: 0,
    latency: null,
    tps: null,
  }
}

/** 同源代理 status-api.php 已返回归一化结构，仅做字段兜底 */
function parseTps(v: any): TpsInfo | null {
  if (!v || typeof v.tps1m !== 'number' || !Number.isFinite(v.tps1m)) return null
  const num = (x: any): number | null => (typeof x === 'number' && Number.isFinite(x) ? x : null)
  return {
    tps1m: v.tps1m,
    tps5m: num(v.tps5m),
    tps15m: num(v.tps15m),
    mspt: num(v.mspt),
    age: num(v.age),
  }
}

function normalizeLocal(j: any): NormalizedStatus {
  const list = Array.isArray(j?.playerList) ? j.playerList : []
  return {
    online: !!j?.online,
    version: String(j?.version ?? ''),
    playersOnline: Number(j?.playersOnline ?? 0),
    playersMax: Number(j?.playersMax ?? 0),
    playerList: list.map((p: any) => String(p ?? '')).filter(Boolean),
    motd: String(j?.motd ?? ''),
    favicon: String(j?.favicon ?? ''),
    srvHost: String(j?.srvHost ?? ''),
    srvPort: Number(j?.srvPort ?? 0),
    latency: typeof j?.latency === 'number' && j.latency > 0 ? Math.round(j.latency) : null,
    tps: parseTps(j?.tps),
  }
}

/** 查询单台服务器：同源代理优先，mcstatus.io 次之，mcsrvstat 兜底，minetools 补延迟 */
async function queryServer(config: McServerConfig): Promise<NormalizedStatus> {
  const host = config.address
  const addr = config.port ? `${host}:${config.port}` : host
  if (!isPublicHost(host)) throw new Error('无效的服务器地址')

  // 同源代理：官网服务器直查（SRV、缓存、不受访客网络影响）。
  // 接口不存在（静态托管/未上传）时返回 SPA HTML，json() 解析失败会静默走公共 API。
  let local: NormalizedStatus | null = null
  try {
    const q = config.port ? `?host=${encodeURIComponent(host)}&port=${config.port}` : `?host=${encodeURIComponent(host)}`
    const l = await fetchJson<any>(LOCAL_STATUS_API + q, 5000)
    if (l && typeof l.online === 'boolean') local = normalizeLocal(l)
  } catch {
    /* 代理不可用，继续公共 API */
  }
  if (local?.online) return local

  let status: NormalizedStatus
  try {
    status = normalizeMcStatusIo(await fetchJson(MCSTATUS_API + addr, 15000))
  } catch {
    status = normalizeMcsrvstat(await fetchJson(MCSRVSTAT_API + addr, 15000))
  }
  if (!status.online && local) return local
  if (!status.online && !local) return status

  if (status.online) {
    const pingHost = status.srvHost || host
    const pingPort = status.srvPort || config.port || 25565
    if (isPublicHost(pingHost)) {
      const mt = await fetchJson<any>(`${MINETOOLS_API}${pingHost}/${pingPort}`, 8000).catch(() => null)
      const ms = mt?.latency ?? mt?.Latency
      if (typeof ms === 'number' && ms > 0) status.latency = Math.round(ms)
    }
  }
  return status
}

/* ---------------- 刷新流程 ---------------- */

async function refreshAll() {
  if (isRefreshing.value) return
  isRefreshing.value = true

  const cancelTimers = entries.map((entry) => {
    entry.loading = true
    entry.status = null
    return startPhaseTimer(entry)
  })

  await Promise.all(entries.map(async (entry, i) => {
    try {
      const status = await queryServer(entry.config)
      entry.status = status
    } catch {
      entry.status = {
        online: false, version: '', playersOnline: 0, playersMax: 0,
        playerList: [], motd: '', favicon: '', srvHost: '', srvPort: 0, latency: null, tps: null,
      }
    } finally {
      entry.progress = 100
      entry.phase = 'done'
      setTimeout(() => {
        cancelTimers[i]()
        entry.loading = false
        stampTime()
      }, 250)
    }
  }))

  isRefreshing.value = false
}

function stampTime() {
  const d = new Date()
  const p = (n: number) => String(n).padStart(2, '0')
  lastUpdated.value = `${p(d.getHours())}:${p(d.getMinutes())}:${p(d.getSeconds())}`
}

/* ---------------- 交互工具 ---------------- */

async function copyAddress(entry: ServerEntry) {
  const text = copyFormat(entry.config)
  try {
    if (navigator.clipboard && window.isSecureContext) {
      await navigator.clipboard.writeText(text)
    } else {
      throw new Error('clipboard unavailable')
    }
  } catch {
    // 剪辑板 API 不可用或被拒绝时，退回 execCommand 方案
    try {
      const ta = document.createElement('textarea')
      ta.value = text
      ta.style.position = 'fixed'
      ta.style.opacity = '0'
      document.body.appendChild(ta)
      ta.select()
      document.execCommand('copy')
      document.body.removeChild(ta)
    } catch {
      return
    }
  }
  entry.copied = true
  setTimeout(() => { entry.copied = false }, 2000)
}

function latencyClass(ms: number | null | undefined): string {
  if (ms == null) return ''
  if (ms <= 100) return 'latency-good'
  if (ms <= 300) return 'latency-ok'
  return 'latency-bad'
}

/** TPS 着色：≥19 正常 / ≥15 轻微卡顿 / 其余红色告警 */
function tpsClass(tps: number): string {
  if (tps >= 19) return 'latency-good'
  if (tps >= 15) return 'latency-ok'
  return 'latency-bad'
}

function tpsTitle(t: TpsInfo): string {
  const f = (x: number | null) => (x == null ? '--' : x.toFixed(2))
  const parts = [`1分钟 ${f(t.tps1m)}`, `5分钟 ${f(t.tps5m)}`, `15分钟 ${f(t.tps15m)}`]
  if (t.age != null) parts.push(t.age <= 90 ? '实时' : `${Math.max(1, Math.round(t.age / 60))} 分钟前更新`)
  return parts.join(' ｜ ')
}

/** 群组端的版本串可能极长（如 Waterfall 全版本列表），截断展示、悬浮看全文 */
function shortVersion(v: string | undefined): string {
  if (!v) return '--'
  return v.length > 34 ? v.slice(0, 34).replace(/[\s,]+$/, '') + '…' : v
}

/* ---------------- MOTD § 颜色解析（移植自 uemcraft） ---------------- */

const MC_COLOR_MAP: Record<string, string> = {
  '0': 'mc-color-0', '1': 'mc-color-1', '2': 'mc-color-2', '3': 'mc-color-3',
  '4': 'mc-color-4', '5': 'mc-color-5', '6': 'mc-color-6', '7': 'mc-color-7',
  '8': 'mc-color-8', '9': 'mc-color-9', a: 'mc-color-a', b: 'mc-color-b',
  c: 'mc-color-c', d: 'mc-color-d', e: 'mc-color-e', f: 'mc-color-f',
}

function escapeHtml(text: string): string {
  const div = document.createElement('div')
  div.textContent = text ?? ''
  return div.innerHTML
}

function parseMotd(raw: string): string {
  if (!raw) return ''
  const text = escapeHtml(raw).replace(/\n/g, '<br>')
  let result = ''
  let openSpans = 0
  let i = 0
  while (i < text.length) {
    const isCode = text[i] === '§' || (text[i] === '&' && i + 1 < text.length && /[0-9a-fk-or]/i.test(text[i + 1] ?? ''))
    if (isCode) {
      const code = (text[i + 1] ?? '').toLowerCase()
      if (MC_COLOR_MAP[code]) {
        if (openSpans > 0) { result += '</span>'; openSpans-- }
        result += `<span class="${MC_COLOR_MAP[code]}">`
        openSpans++
      } else if (code === 'l') { result += '<span class="mc-bold">'; openSpans++ }
      else if (code === 'o') { result += '<span class="mc-italic">'; openSpans++ }
      else if (code === 'n') { result += '<span class="mc-underline">'; openSpans++ }
      else if (code === 'm') { result += '<span class="mc-strikethrough">'; openSpans++ }
      else if (code === 'r') { while (openSpans > 0) { result += '</span>'; openSpans-- } }
      i += 2
    } else {
      result += text[i]
      i++
    }
  }
  while (openSpans > 0) { result += '</span>'; openSpans-- }
  return result
}

function motdHtml(entry: ServerEntry): string {
  return entry.status?.motd ? parseMotd(entry.status.motd) : ''
}

onMounted(refreshAll)
</script>

<style scoped>
/* ---- 区块 ---- */
.server {
  padding: var(--space-section) 0;
  background: rgba(45, 45, 45, 0.35);
}

.server__inner {
  max-width: 900px;
  margin: 0 auto;
  padding: 0 var(--space-lg);
}

.server__head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: var(--space-md);
  margin-bottom: var(--space-xl);
}

.server__heading {
  font-family: var(--font-pixel);
  font-size: var(--text-xl);
  color: var(--color-accent);
  text-shadow: 2px 2px 0px rgba(0, 0, 0, 0.5);
}

.server__refresh {
  display: flex;
  align-items: center;
  gap: var(--space-md);
}

.server__refresh-time {
  font-size: var(--text-sm);
  color: var(--color-text-muted);
}

.server__refresh-btn {
  font-family: var(--font-body);
  font-size: var(--text-sm);
  font-weight: 700;
  color: var(--color-accent);
  background: transparent;
  border: var(--border-width-sm) solid var(--color-accent);
  padding: 6px 14px;
  cursor: pointer;
  transition: background var(--duration-normal) var(--ease-out),
              color var(--duration-normal) var(--ease-out),
              transform var(--duration-fast) var(--ease-spring);
}

.server__refresh-btn:hover:not(:disabled) {
  background: var(--color-accent);
  color: #fff;
  transform: translateY(-2px);
}

.server__refresh-btn.is-busy {
  opacity: 0.7;
  animation: refresh-pulse 1.1s ease-in-out infinite;
}

@keyframes refresh-pulse {
  0%, 100% { opacity: 0.45; }
  50%      { opacity: 0.95; }
}

/* ---- 卡片列表 ---- */
.server__list {
  display: flex;
  flex-direction: column;
  gap: var(--space-lg);
}

/* ---- 实时地图入口 ---- */
.server-map {
  display: flex;
  align-items: center;
  gap: var(--space-lg);
  margin-top: var(--space-lg);
  padding: var(--space-lg);
  background: var(--color-surface);
  border: var(--border-width-sm) solid var(--color-surface-light);
  border-left: 4px solid var(--color-gold);
  box-shadow: var(--shadow-pixel-sm);
  text-decoration: none;
  transition: transform 0.2s var(--ease-spring),
              box-shadow 0.2s var(--ease-apple-out),
              border-color var(--duration-normal) var(--ease-out);
}

.server-map:hover {
  transform: translateY(-4px);
  border-color: var(--color-gold-dim);
  border-left-color: var(--color-gold);
  box-shadow: var(--shadow-pixel);
}

.server-map__icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 52px;
  height: 52px;
  flex-shrink: 0;
  color: var(--color-gold);
  background: rgba(218, 165, 32, 0.14);
  border: var(--border-width-sm) solid var(--color-gold-dim);
}

.server-map__body {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 0.25em;
}

.server-map__title {
  font-family: var(--font-display);
  font-size: var(--text-lg);
  color: var(--color-text);
  line-height: 1.2;
}

.server-map__desc {
  font-family: var(--font-body);
  font-size: var(--text-sm);
  color: var(--color-text-muted);
  line-height: 1.6;
}

.server-map__cta {
  display: inline-flex;
  align-items: center;
  gap: 0.35em;
  flex-shrink: 0;
  font-family: var(--font-body);
  font-size: var(--text-sm);
  font-weight: 700;
  color: var(--color-accent);
  background: transparent;
  border: var(--border-width-sm) solid var(--color-accent);
  padding: 6px 14px;
  line-height: 1.2;
  white-space: nowrap;
  transition: background var(--duration-normal) var(--ease-out),
              color var(--duration-normal) var(--ease-out);
}

.server-map:hover .server-map__cta {
  background: var(--color-accent);
  color: #fff;
}

.server-card {
  background: var(--color-surface);
  border: var(--border-width-sm) solid var(--color-surface-light);
  border-left: 4px solid var(--color-accent);
  box-shadow: var(--shadow-pixel-sm);
  overflow: hidden;
  transition: transform 0.2s var(--ease-spring),
              box-shadow 0.2s var(--ease-apple-out),
              border-color var(--duration-normal) var(--ease-out);
}

.server-card:hover {
  transform: translateY(-4px);
  border-color: var(--color-accent-dim);
  border-left-color: var(--color-accent);
  box-shadow: var(--shadow-pixel);
}

.server-card.is-offline {
  border-left-color: var(--color-danger);
}

.server-card.is-offline .server-card__favicon {
  filter: grayscale(0.6) opacity(0.5);
}

/* ---- 头部 ---- */
.server-card__header {
  display: flex;
  align-items: center;
  gap: var(--space-md);
  padding: var(--space-lg);
  padding-bottom: 0;
}

.server-card__favicon {
  width: 64px;
  height: 64px;
  border: var(--border-width-sm) solid var(--color-surface-light);
  image-rendering: pixelated;
  flex-shrink: 0;
  background: var(--color-surface-light);
}

.server-card__favicon--empty {
  background:
    linear-gradient(45deg, rgba(0, 0, 0, 0.2) 25%, transparent 25%, transparent 75%, rgba(0, 0, 0, 0.2) 75%),
    linear-gradient(45deg, rgba(0, 0, 0, 0.2) 25%, transparent 25%, transparent 75%, rgba(0, 0, 0, 0.2) 75%);
  background-size: 16px 16px;
  background-position: 0 0, 8px 8px;
}

.server-card__title {
  flex: 1;
  min-width: 0;
}

.server-card__name-row {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
  flex-wrap: wrap;
}

.server-card__name {
  font-family: var(--font-display);
  font-size: var(--text-lg);
  color: var(--color-text);
  line-height: 1.2;
}

.server-card__note {
  font-size: var(--text-xs);
  color: var(--color-text-muted);
}

.server-card__badge {
  display: inline-flex;
  align-items: center;
  gap: 0.35em;
  font-size: var(--text-xs);
  font-weight: 700;
  padding: 0.2em 0.7em;
  line-height: 1;
  flex-shrink: 0;
}

.server-card__dot {
  width: 8px;
  height: 8px;
  flex-shrink: 0;
}

.server-card__badge.is-online {
  background: rgba(92, 154, 59, 0.16);
  color: var(--color-accent-hover);
}

.server-card__badge.is-online .server-card__dot {
  background: var(--color-accent-hover);
  animation: pulse-dot 2s infinite;
}

.server-card__badge.is-offline {
  background: rgba(192, 57, 43, 0.16);
  color: var(--color-danger);
}

.server-card__badge.is-offline .server-card__dot {
  background: var(--color-danger);
}

.server-card__badge.is-loading {
  background: rgba(59, 130, 196, 0.16);
  color: var(--color-water);
}

.server-card__badge.is-loading .server-card__dot {
  background: var(--color-water);
  animation: pulse-dot 1.5s infinite;
}

@keyframes pulse-dot {
  0%, 100% { box-shadow: 0 0 4px currentColor; }
  50%      { box-shadow: 0 0 10px currentColor, 0 0 18px currentColor; }
}

/* ---- 地址行 ---- */
.server-card__addr {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
  margin-top: 0.3em;
  flex-wrap: wrap;
}

.server-card__address {
  font-family: var(--font-body);
  font-size: var(--text-sm);
  background: var(--color-surface-light);
  border: 1px solid var(--color-surface-light);
  padding: 0.25rem 0.6rem;
  user-select: all;
  color: var(--color-text-muted);
}

.server-card__edition {
  font-size: var(--text-xs);
  font-weight: 700;
  color: var(--color-text-muted);
  background: var(--color-surface-light);
  border: 1px solid var(--color-surface-light);
  padding: 0.15rem 0.5rem;
  line-height: 1;
  white-space: nowrap;
}

.server-card__copy {
  font-family: var(--font-body);
  font-size: var(--text-xs);
  font-weight: 700;
  color: var(--color-accent);
  background: transparent;
  border: 1px solid var(--color-accent);
  padding: 0.2rem 0.55rem;
  cursor: pointer;
  line-height: 1;
  transition: background var(--duration-fast) var(--ease-out),
              color var(--duration-fast) var(--ease-out),
              border-color var(--duration-fast) var(--ease-out);
}

.server-card__copy:hover {
  background: var(--color-accent);
  color: #fff;
}

.server-card__copy.is-copied {
  background: var(--color-accent);
  border-color: var(--color-accent);
  color: #fff;
  animation: copy-pop 0.3s var(--ease-spring);
}

@keyframes copy-pop {
  0%   { transform: scale(1); }
  50%  { transform: scale(1.12); }
  100% { transform: scale(1); }
}

/* ---- MOTD ---- */
.server-card__motd {
  margin: var(--space-md) var(--space-lg);
  padding: var(--space-sm) var(--space-md);
  font-family: var(--font-body);
  font-size: var(--text-sm);
  color: var(--color-text);
  line-height: 1.7;
  background: var(--color-bg);
  border: 1px solid var(--color-surface-light);
  min-height: 2.6em;
}

.server-card__motd--empty {
  color: var(--color-text-muted);
  font-style: italic;
}

.server-card__motd :deep(.mc-color-0) { color: #aaa; }
.server-card__motd :deep(.mc-color-1) { color: #55f; }
.server-card__motd :deep(.mc-color-2) { color: #5f5; }
.server-card__motd :deep(.mc-color-3) { color: #5ff; }
.server-card__motd :deep(.mc-color-4) { color: #f55; }
.server-card__motd :deep(.mc-color-5) { color: #f5f; }
.server-card__motd :deep(.mc-color-6) { color: #fa0; }
.server-card__motd :deep(.mc-color-7) { color: #aaa; }
.server-card__motd :deep(.mc-color-8) { color: #888; }
.server-card__motd :deep(.mc-color-9) { color: #77f; }
.server-card__motd :deep(.mc-color-a) { color: #5f5; }
.server-card__motd :deep(.mc-color-b) { color: #5ff; }
.server-card__motd :deep(.mc-color-c) { color: #f55; }
.server-card__motd :deep(.mc-color-d) { color: #f5f; }
.server-card__motd :deep(.mc-color-e) { color: #ff5; }
.server-card__motd :deep(.mc-color-f) { color: #fff; }
.server-card__motd :deep(.mc-bold) { font-weight: 700; }
.server-card__motd :deep(.mc-italic) { font-style: italic; }
.server-card__motd :deep(.mc-underline) { text-decoration: underline; }
.server-card__motd :deep(.mc-strikethrough) { text-decoration: line-through; }

/* ---- 统计 ---- */
.server-card__stats {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  border-top: 1px solid var(--color-surface-light);
}

.server-card__stat {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.15em;
  padding: var(--space-md) var(--space-sm);
  text-align: center;
}

.server-card__stat + .server-card__stat {
  border-left: 1px solid var(--color-surface-light);
}

.server-card__stat-val {
  font-family: var(--font-body);
  font-size: var(--text-base);
  font-weight: 700;
  color: var(--color-text);
  line-height: 1;
}

.server-card__stat-label {
  font-size: var(--text-xs);
  color: var(--color-text-muted);
  line-height: 1;
}

.is-loading .server-card__stat-val {
  color: var(--color-text-muted);
}

.latency-good { color: var(--color-accent-hover); }
.latency-ok   { color: var(--color-gold); }
.latency-bad  { color: var(--color-danger); }

/* ---- 玩家列表悬浮 ---- */
.server-card__stat--players {
  cursor: default;
}

.server-card__tooltip {
  position: absolute;
  bottom: calc(100% + 8px);
  left: 50%;
  transform: translate(-50%, 4px);
  background: var(--color-surface);
  border: 1px solid var(--color-surface-light);
  box-shadow: var(--shadow-pixel-sm);
  padding: var(--space-sm) var(--space-md);
  min-width: 120px;
  max-width: 260px;
  z-index: 10;
  opacity: 0;
  visibility: hidden;
  transition: opacity 0.15s ease, visibility 0.15s ease, transform 0.15s ease;
  pointer-events: none;
}

.server-card__stat--players:hover .server-card__tooltip {
  opacity: 1;
  visibility: visible;
  transform: translate(-50%, 0);
}

.server-card__players {
  list-style: none;
  margin: 0;
  padding: 0;
  font-family: var(--font-body);
  font-size: var(--text-xs);
  color: var(--color-text-muted);
  text-align: center;
  line-height: 1.7;
}

.server-card__players:empty::after {
  content: '暂无玩家在线';
  font-style: italic;
}

/* ---- 骨架进度 ---- */
.server-card.is-loading {
  border-left-color: var(--color-water);
}

.server-card__progress {
  padding: var(--space-sm) var(--space-lg) var(--space-md);
}

.server-card__progress-bar {
  height: 3px;
  background: var(--color-surface-light);
  overflow: hidden;
}

.server-card__progress-fill {
  height: 100%;
  background: var(--color-water);
  transition: width 0.3s ease;
  animation: progress-pulse 1.5s ease-in-out infinite;
}

@keyframes progress-pulse {
  0%, 100% { opacity: 1; }
  50%      { opacity: 0.5; }
}

.server-card__progress-text {
  font-size: var(--text-xs);
  color: var(--color-text-muted);
  margin-top: var(--space-xs);
}

/* ---- 骨架/结果卡片切换 ---- */
.card-swap-enter-active {
  transition: opacity 0.3s var(--ease-out), transform 0.3s var(--ease-out);
}

.card-swap-leave-active {
  transition: opacity 0.15s ease;
}

.card-swap-enter-from {
  opacity: 0;
  transform: translateY(10px);
}

.card-swap-leave-to {
  opacity: 0;
}

/* ---- 响应式 ---- */
@media (max-width: 640px) {
  .server-card__header {
    flex-direction: column;
    align-items: flex-start;
  }

  .server-card__stats {
    grid-template-columns: 1fr;
  }

  .server-card__stat + .server-card__stat {
    border-left: none;
    border-top: 1px solid var(--color-surface-light);
  }

  .server-map {
    flex-wrap: wrap;
  }

  .server-map__cta {
    width: 100%;
    justify-content: center;
  }
}

@media (prefers-reduced-motion: reduce) {
  .server-card,
  .server-card__progress-fill,
  .server-card__dot,
  .server__refresh-btn,
  .server-map {
    animation: none;
    transition: none;
  }
}
</style>
