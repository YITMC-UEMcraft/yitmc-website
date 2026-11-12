<template>
  <div class="admin">
    <p v-if="phase === 'checking'" class="admin__center">正在检查登录状态…</p>

    <form v-else-if="phase === 'login'" class="admin__login" @submit.prevent="login">
      <h1>YITMC 网站内容管理</h1>
      <p class="admin__tip">请输入管理密码（由上一任社长或管理员提供）</p>
      <input v-model="password" type="password" placeholder="管理密码" autocomplete="current-password" />
      <button type="submit" :disabled="busy || !password">登 录</button>
      <p v-if="msg" class="admin__msg" :class="{ 'is-error': isError }">{{ msg }}</p>
      <a href="/" class="admin__back">← 返回官网首页</a>
    </form>

    <template v-else>
      <header class="admin__bar">
        <strong>YITMC 内容管理</strong>
        <span class="admin__bar-tip">保存后刷新网站即可看到最新内容</span>
        <button type="button" @click="logout">退出登录</button>
      </header>

      <nav class="admin__tabs">
        <button v-for="t in tabs" :key="t.key" type="button"
          :class="{ 'is-active': tab === t.key }" @click="tab = t.key">{{ t.label }}</button>
      </nav>

      <main class="admin__main">
        <section v-show="tab === 'site'">
          <h2>联系方式与基本信息</h2>
          <div class="field-grid">
            <label v-for="f in SITE_TEXT_FIELDS" :key="f.key">
              <span>{{ f.label }}</span>
              <input v-model="site[f.key]" />
            </label>
          </div>
          <div class="image-grid">
            <div v-for="f in SITE_IMAGE_FIELDS" :key="f.key" class="image-field">
              <span>{{ f.label }}</span>
              <img v-if="site[f.key]" :src="site[f.key]" alt="" />
              <input v-model="site[f.key]" placeholder="图片路径" />
              <button type="button" @click="pickImage('site-config.json', site, f.key)">上传新图片</button>
            </div>
          </div>
          <button type="button" class="save-btn" :disabled="busy" @click="save('site-config.json')">保存「联系方式」</button>
        </section>

        <section v-show="tab === 'works'">
          <h2>作品展示</h2>
          <details class="sub-block">
            <summary>分类筛选标签（key 请勿随意修改）</summary>
            <div v-for="(c, i) in works.categories" :key="'c' + i" class="row-3">
              <label><span>标识 key</span><input v-model="c.key" /></label>
              <label><span>显示名称</span><input v-model="c.label" /></label>
              <button type="button" class="danger" @click="del(works.categories, i, '分类')">删除分类</button>
            </div>
            <button type="button" class="add-btn" @click="works.categories.push({ key: 'newcat', label: '新分类' })">+ 添加分类</button>
          </details>

          <div v-for="(w, i) in works.works" :key="'w' + i" class="item-card">
            <header>
              <b>{{ i + 1 }}. {{ w.title || '(未命名作品)' }}</b>
              <button type="button" class="danger" @click="del(works.works, i, '这件作品')">删除</button>
            </header>
            <div class="field-grid">
              <label><span>标题</span><input v-model="w.title" /></label>
              <label><span>分类</span>
                <select v-model="w.category">
                  <option v-for="c in works.categories" :key="c.key" :value="c.key">{{ c.label }}</option>
                </select>
              </label>
              <label><span>日期 / 年份</span><input v-model="w.date" placeholder="如 2026 或 2026-09-15" /></label>
              <label><span>编号 ID</span><input v-model.number="w.id" /></label>
            </div>
            <label class="wide"><span>作品简介</span><textarea v-model="w.description" rows="2"></textarea></label>
            <label class="wide"><span>作者（多人用逗号分隔）</span>
              <input :value="(w.authors || []).join('，')" @change="setList(w, 'authors', $event)" />
            </label>
            <div class="image-field">
              <span>封面图片</span>
              <img v-if="w.image" :src="w.image" alt="" />
              <input v-model="w.image" placeholder="图片路径" />
              <button type="button" @click="pickImage('works.json', w, 'image')">上传封面</button>
            </div>
          </div>
          <button type="button" class="add-btn" :disabled="busy" @click="addWork">+ 添加一件作品</button>
          <button type="button" class="save-btn" :disabled="busy" @click="save('works.json')">保存「作品展示」</button>
        </section>

        <section v-show="tab === 'news'">
          <h2>社团动态</h2>
          <div v-for="(n, i) in news.news" :key="'n' + i" class="item-card">
            <header>
              <b>{{ n.date }} {{ n.title || '(未命名)' }}</b>
              <button type="button" class="danger" @click="del(news.news, i, '这条动态')">删除</button>
            </header>
            <div class="field-grid">
              <label><span>标题</span><input v-model="n.title" /></label>
              <label><span>日期</span><input v-model="n.date" placeholder="如 2026-09-15" /></label>
              <label><span>类型</span>
                <select v-model="n.type">
                  <option value="announcement">公告</option>
                  <option value="event">活动</option>
                  <option value="project">项目进展</option>
                  <option value="achievement">成果</option>
                </select>
              </label>
              <label><span>编号 ID</span><input v-model.number="n.id" /></label>
            </div>
            <label class="wide"><span>摘要（列表页显示的一句话）</span><textarea v-model="n.summary" rows="2"></textarea></label>
            <label class="wide"><span>正文详情</span><textarea v-model="n.content" rows="5"></textarea></label>
            <div class="image-field">
              <span>配图（可选，不上传则不显示）</span>
              <img v-if="n.image" :src="n.image" alt="" />
              <input v-model="n.image" placeholder="留空表示没有配图" />
              <button type="button" @click="pickImage('news.json', n, 'image')">上传配图</button>
              <button v-if="n.image" type="button" class="danger" @click="n.image = ''">移除配图</button>
            </div>
          </div>
          <button type="button" class="add-btn" :disabled="busy" @click="addNews">+ 发布一条新动态</button>
          <button type="button" class="save-btn" :disabled="busy" @click="save('news.json')">保存「社团动态」</button>
        </section>

        <section v-show="tab === 'events'">
          <h2>活动中心</h2>
          <p class="hint">「即将到来 / 进行中」显示在近期活动，「已结束」显示在往期回顾。状态由管理员手动选择。</p>
          <div v-for="(e, i) in events.events" :key="'e' + i" class="item-card">
            <header>
              <b>{{ e.title || '(未命名活动)' }}</b>
              <button type="button" class="danger" @click="del(events.events, i, '这场活动')">删除</button>
            </header>
            <div class="field-grid">
              <label><span>标题</span><input v-model="e.title" /></label>
              <label><span>状态</span>
                <select v-model="e.status">
                  <option value="upcoming">即将到来</option>
                  <option value="ongoing">进行中</option>
                  <option value="past">已结束</option>
                </select>
              </label>
              <label><span>日期标签</span><input v-model="e.dateLabel" placeholder="如 2026.11" /></label>
              <label><span>开始日期</span><input v-model="e.dateStart" placeholder="如 2026-11-20" /></label>
              <label><span>结束日期（可留空）</span><input v-model="e.dateEnd" placeholder="如 2026-11-21" /></label>
              <label><span>地点（可留空）</span><input v-model="e.location" placeholder="如 线上 / 报告厅" /></label>
              <label><span>编号 ID</span><input v-model.number="e.id" /></label>
            </div>
            <label class="wide"><span>摘要</span><textarea v-model="e.summary" rows="2"></textarea></label>
            <div class="image-field">
              <span>封面（可选）</span>
              <img v-if="e.cover" :src="e.cover" alt="" />
              <input v-model="e.cover" placeholder="留空显示占位图" />
              <button type="button" @click="pickImage('events.json', e, 'cover')">上传封面</button>
              <button v-if="e.cover" type="button" class="danger" @click="e.cover = ''">移除封面</button>
            </div>
            <div class="field-grid">
              <label><span>跳转链接（可留空）</span><input v-model="e.link" placeholder="/join 或 https://..." /></label>
              <label class="checkbox-field"><span>首页精选</span><input v-model="e.featured" type="checkbox" /></label>
            </div>
          </div>
          <button type="button" class="add-btn" :disabled="busy" @click="addEvent">+ 添加一场活动</button>
          <button type="button" class="save-btn" :disabled="busy" @click="save('events.json')">保存「活动中心」</button>
        </section>

        <section v-show="tab === 'docs'">
          <h2>指南文档</h2>
          <p class="hint">正文支持 Markdown（标题、列表、表格、代码块、链接）。文档 ID 会用作访问地址：/docs/ID。</p>
          <div v-for="(d, i) in docs.docs" :key="'d' + i" class="item-card">
            <header>
              <b>{{ d.title || '(未命名文档)' }}</b>
              <button type="button" class="danger" @click="del(docs.docs, i, '这篇文档')">删除</button>
            </header>
            <div class="field-grid">
              <label><span>标题</span><input v-model="d.title" /></label>
              <label><span>文档 ID（英文/数字/-）</span><input v-model="d.id" placeholder="如 getting-started" /></label>
              <label><span>分类</span><input v-model="d.category" list="doc-categories" /></label>
              <label><span>排序（小在前）</span><input v-model.number="d.order" type="number" /></label>
              <label><span>更新日期</span><input v-model="d.updatedAt" placeholder="如 2026-11-12" /></label>
            </div>
            <datalist id="doc-categories">
              <option v-for="c in docCategories" :key="c" :value="c" />
            </datalist>
            <label class="wide"><span>正文（Markdown）</span><textarea v-model="d.content" rows="10"></textarea></label>
            <button type="button" class="add-btn" @click="docPreview[i] = !docPreview[i]">
              {{ docPreview[i] ? '收起预览' : '预览 Markdown' }}
            </button>
            <div v-if="docPreview[i]" class="doc-preview" v-html="renderMarkdown(d.content).html"></div>
          </div>
          <button type="button" class="add-btn" :disabled="busy" @click="addDoc">+ 添加一篇文档</button>
          <button type="button" class="save-btn" :disabled="busy" @click="save('docs.json')">保存「指南文档」</button>
        </section>

        <section v-show="tab === 'members'">
          <h2>成员风采（管理团队）</h2>
          <div v-for="(m, i) in members.leadership" :key="'m' + i" class="item-card">
            <header>
              <b>{{ m.name }} — {{ m.role }}</b>
              <button type="button" class="danger" @click="del(members.leadership, i, '这位成员')">删除</button>
            </header>
            <div class="field-grid">
              <label><span>姓名</span><input v-model="m.name" /></label>
              <label><span>职务</span><input v-model="m.role" placeholder="如：社长、技术部长" /></label>
              <label><span>年级</span><input v-model="m.grade" placeholder="如 2026级" /></label>
              <label><span>编号 ID</span><input v-model.number="m.id" /></label>
            </div>
            <label class="wide"><span>个人介绍</span><textarea v-model="m.description" rows="2"></textarea></label>
            <div class="image-field">
              <span>头像</span>
              <img v-if="m.avatar" :src="m.avatar" alt="" />
              <input v-model="m.avatar" placeholder="图片路径" />
              <button type="button" @click="pickImage('members.json', m, 'avatar')">上传头像</button>
            </div>
          </div>
          <button type="button" class="add-btn" :disabled="busy" @click="addMember">+ 添加一位成员</button>
          <button type="button" class="save-btn" :disabled="busy" @click="save('members.json')">保存「成员风采」</button>
        </section>

        <section v-show="tab === 'stats'">
          <h2>首页数据亮点</h2>
          <div v-for="(s, i) in stats.stats" :key="'s' + i" class="row-3">
            <label><span>数值</span><input v-model.number="s.value" type="number" /></label>
            <label><span>后缀</span><input v-model="s.suffix" placeholder="+、年 等" /></label>
            <label><span>名称</span><input v-model="s.label" /></label>
          </div>
          <button type="button" class="add-btn" :disabled="busy" @click="stats.stats.push({ value: 1, suffix: '+', label: '新数据' })">+ 添加一条</button>
          <button type="button" class="save-btn" :disabled="busy" @click="save('stats.json')">保存「首页数据」</button>
        </section>

        <section v-show="tab === 'settings'">
          <h2>设置</h2>
          <div class="settings-card">
            <h3>修改管理密码（换届交接时使用）</h3>
            <label><span>当前密码</span><input v-model="pw.oldPw" type="password" autocomplete="current-password" /></label>
            <label><span>新密码（至少 8 位）</span><input v-model="pw.newPw" type="password" autocomplete="new-password" /></label>
            <label><span>再输一次新密码</span><input v-model="pw.newPw2" type="password" autocomplete="new-password" /></label>
            <button type="button" class="save-btn" :disabled="busy" @click="changePassword">确认修改密码</button>
            <p class="hint">换届交接：在这里把密码改成新的并告知下一任即可。忘记密码需联系网站维护者。</p>
          </div>
        </section>

        <p v-if="msg" class="admin__msg" :class="{ 'is-error': isError }">{{ msg }}</p>
        <p class="data-note">说明：本页面直接修改服务器上的 /data/*.json 与 /uploads/ 图片文件，保存即全网生效。</p>
      </main>
    </template>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import {
  siteConfig, worksData, newsData, membersData, statsData, eventsData, docsData,
} from '@/data'
import { renderMarkdown } from '@/lib/markdown'

const API = '/admin-api.php'

const tabs = [
  { key: 'site', label: '联系方式' },
  { key: 'works', label: '作品展示' },
  { key: 'news', label: '社团动态' },
  { key: 'events', label: '活动中心' },
  { key: 'docs', label: '指南文档' },
  { key: 'members', label: '成员风采' },
  { key: 'stats', label: '首页数据' },
  { key: 'settings', label: '设置' },
]

const SITE_TEXT_FIELDS = [
  { key: 'clubName', label: '社团中文名' },
  { key: 'clubNameEn', label: '社团英文名' },
  { key: 'clubMotto', label: '社团口号' },
  { key: 'schoolName', label: '学校名称' },
  { key: 'registrationInfo', label: '注册信息' },
  { key: 'qqGroup', label: 'QQ 群号（纯数字）' },
  { key: 'qqGroupJoinUrl', label: 'QQ 群加群链接' },
  { key: 'presidentWechat', label: '社长微信号' },
  { key: 'bilibiliUrl', label: 'B 站主页链接' },
  { key: 'douyinUrl', label: '抖音主页链接' },
  { key: 'skinStationUrl', label: '皮肤站链接' },
  { key: 'icpNumber', label: 'ICP 备案号' },
  { key: 'uemcraftUrl', label: '应大MC同好会链接' },
]

const SITE_IMAGE_FIELDS = [
  { key: 'qqGroupQrUrl', label: 'QQ 群二维码' },
  { key: 'wechatGroupQrUrl', label: '微信群二维码' },
  { key: 'officialProofUrl', label: '官方社团证明图' },
]

// eslint-disable-next-line
const phase = ref<'checking' | 'login' | 'ready'>('checking')
const busy = ref(false)
const password = ref('')
const msg = ref('')
const isError = ref(false)
const tab = ref('site')
const pw = reactive({ oldPw: '', newPw: '', newPw2: '' })
// eslint-disable-next-line
const dataFiles = ref<Record<string, any>>({})

const site = computed(() => dataFiles.value['site-config.json'] ?? {})
const works = computed(() => dataFiles.value['works.json'] ?? { categories: [], works: [] })
const news = computed(() => dataFiles.value['news.json'] ?? { news: [] })
const members = computed(() => dataFiles.value['members.json'] ?? { leadership: [] })
const stats = computed(() => dataFiles.value['stats.json'] ?? { stats: [] })
const events = computed(() => dataFiles.value['events.json'] ?? { events: [] })
const docs = computed(() => dataFiles.value['docs.json'] ?? { docs: [] })
const docCategories = computed<string[]>(() =>
  [...new Set<string>(docs.value.docs.map((d: any) => String(d.category || '')).filter(Boolean))],
)
const docPreview = reactive<Record<number, boolean>>({})

function flash(text: string, error = false) {
  msg.value = text
  isError.value = error
  if (!error && text) setTimeout(() => { if (msg.value === text) msg.value = '' }, 4000)
}

async function api(body?: FormData | Record<string, unknown>): Promise<any> {
  const init: RequestInit = body instanceof FormData
    ? { method: 'POST', body }
    : {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'state', ...(body ?? {}) }),
      }
  const res = await fetch(API, { credentials: 'same-origin', ...init })
  const json = await res.json().catch(() => ({
    ok: false,
    error: res.status === 413
      ? '图片太大被服务器拒收，请压缩到 20MB 以内再上传'
      : '服务器响应异常（HTTP ' + res.status + '），请稍后重试或联系维护者',
  }))
  if (json.code === 'AUTH_REQUIRED') throw Object.assign(new Error('need-login'), { needLogin: true })
  return json
}

function cloneValue<T>(value: T): T {
  return JSON.parse(JSON.stringify(value)) as T
}

/** 服务器上缺失或损坏的数据文件回落到内置默认值，避免新模块的空对象导致表单报错 */
function enterEditor(st: any) {
  const files: Record<string, any> = st.files || {}
  const ensure = (name: string, valid: boolean, fallback: unknown) => {
    if (!valid) files[name] = cloneValue(fallback)
  }
  const siteFile = files['site-config.json']
  ensure('site-config.json', !!siteFile && typeof siteFile === 'object' && 'clubName' in siteFile, siteConfig)
  const worksFile = files['works.json']
  ensure('works.json', !!worksFile && Array.isArray(worksFile.works) && Array.isArray(worksFile.categories), worksData)
  const newsFile = files['news.json']
  ensure('news.json', !!newsFile && Array.isArray(newsFile.news), newsData)
  const membersFile = files['members.json']
  ensure('members.json', !!membersFile && Array.isArray(membersFile.leadership), membersData)
  const statsFile = files['stats.json']
  ensure('stats.json', !!statsFile && Array.isArray(statsFile.stats), statsData)
  const eventsFile = files['events.json']
  ensure('events.json', !!eventsFile && Array.isArray(eventsFile.events), eventsData)
  const docsFile = files['docs.json']
  ensure('docs.json', !!docsFile && Array.isArray(docsFile.docs), docsData)

  dataFiles.value = files
  phase.value = 'ready'
}

async function bootstrap() {
  try {
    const st = await api()
    if (st.ok && st.files) enterEditor(st)
    else phase.value = 'login'
  } catch {
    phase.value = 'login'
  }
}

async function login() {
  busy.value = true
  try {
    const res = await api({ action: 'login', password: password.value })
    if (res.ok && res.files) { enterEditor(res); flash('登录成功') }
    else flash(res.error || '登录失败', true)
  } catch {
    flash('连接服务器失败，请稍后再试', true)
  } finally { busy.value = false; password.value = '' }
}

async function logout() {
  await api({ action: 'logout' }).catch(() => {})
  dataFiles.value = {}
  phase.value = 'login'
}

async function save(file: string) {
  busy.value = true
  try {
    const res = await api({ action: 'save', file, content: dataFiles.value[file] })
    if (res.ok) flash('已保存！打开官网任意页面刷新即可看到最新内容')
    else flash(res.error || '保存失败', true)
  } catch {
    flash('保存请求失败，请重试', true)
  } finally { busy.value = false }
}

function pickImage(file: string, item: any, key: string) {
  const input = document.createElement('input')
  input.type = 'file'
  input.accept = 'image/jpeg,image/png,image/webp,image/gif'
  input.onchange = async () => {
    if (!input.files?.length) return
    busy.value = true
    try {
      const fd = new FormData()
      fd.append('action', 'upload')
      fd.append('file', input.files[0])
      const res = await api(fd)
      if (res.ok) { item[key] = res.url; flash('图片已上传：' + res.url) }
      else flash(res.error || '上传失败', true)
    } catch {
      flash('上传请求失败，请重试', true)
    } finally { busy.value = false }
  }
  input.click()
}

async function changePassword() {
  if (!pw.newPw || pw.newPw.length < 8) return flash('新密码至少 8 位', true)
  if (pw.newPw !== pw.newPw2) return flash('两次输入的新密码不一致', true)
  busy.value = true
  try {
    const res = await api({ action: 'password', oldPassword: pw.oldPw, newPassword: pw.newPw })
    if (res.ok) { flash('密码已修改，请牢记新密码！'); pw.oldPw = ''; pw.newPw = ''; pw.newPw2 = '' }
    else flash(res.error || '修改失败', true)
  } catch {
    flash('请求失败，请重试', true)
  } finally { busy.value = false }
}

function del(arr: any[], index: number, name: string) {
  if (confirm('确定要删除' + name + '吗？删除后点下方“保存”才会生效。')) arr.splice(index, 1)
}

function addWork() {
  const cats = works.value.categories
  works.value.works.push({
    id: nextId(works.value.works),
    title: '',
    category: cats.length ? cats[cats.length - 1].key : 'architecture',
    image: '',
    description: '',
    authors: [],
    date: String(new Date().getFullYear()),
  })
}

function addNews() {
  news.value.news.unshift({
    id: nextId(news.value.news),
    title: '',
    date: today(),
    type: 'event',
    summary: '',
    content: '',
    image: '',
  })
}

function addEvent() {
  const now = new Date()
  events.value.events.unshift({
    id: nextId(events.value.events),
    title: '',
    status: 'upcoming',
    dateStart: today(),
    dateEnd: '',
    dateLabel: `${now.getFullYear()}.${String(now.getMonth() + 1).padStart(2, '0')}`,
    location: '',
    summary: '',
    cover: '',
    link: '',
    featured: false,
  })
}

function addDoc() {
  docs.value.docs.push({
    id: `doc-${Date.now().toString(36)}`,
    title: '',
    category: docCategories.value[0] || '新人指南',
    content: '',
    order: docs.value.docs.length + 1,
    updatedAt: today(),
  })
}

function addMember() {
  members.value.leadership.push({
    id: nextId(members.value.leadership),
    name: '', role: '', avatar: '', description: '', grade: '',
  })
}

function nextId(arr: any[]): number {
  return arr.reduce((max, it) => Math.max(max, Number(it?.id) || 0), 0) + 1
}

function setList(item: any, key: string, ev: Event) {
  item[key] = (ev.target as HTMLInputElement).value
    .split(/[,，、]/).map((s: string) => s.trim()).filter(Boolean)
}

function today(): string {
  const d = new Date()
  const p = (n: number) => String(n).padStart(2, '0')
  return p(d.getFullYear()) + '-' + p(d.getMonth() + 1) + '-' + p(d.getDate())
}

onMounted(bootstrap)
</script>

<style scoped>
.admin { min-height: 100vh; background: #101216; color: #e8e6d9; padding: 24px; box-sizing: border-box; }
.admin__center { display: flex; height: 60vh; align-items: center; justify-content: center; }
.admin__login { max-width: 380px; margin: 12vh auto; display: flex; flex-direction: column; gap: 14px; text-align: center; }
.admin__login h1 { font-size: 20px; margin: 0; }
.admin__tip { color: #9a9788; font-size: 13px; }
.admin__login input { width: 100%; box-sizing: border-box; padding: 11px 12px; border-radius: 8px; border: 1px solid #3a3d45; background: #191c22; color: inherit; font-size: 15px; }
.admin__login button { padding: 12px; border-radius: 8px; border: none; background: #42b883; color: #06281a; font-weight: 700; cursor: pointer; }
.admin__login button:disabled { opacity: .5; cursor: default; }
.admin__back { color: #8a887b; font-size: 13px; }
.admin__bar { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; border-bottom: 1px solid #2a2d34; padding-bottom: 12px; margin-bottom: 16px; }
.admin__bar-tip { color: #9a9788; font-size: 12px; flex: 1; }
.admin__bar button { background: transparent; border: 1px solid #4a4d55; color: inherit; border-radius: 6px; padding: 6px 12px; cursor: pointer; }
.admin__tabs { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 20px; }
.admin__tabs button { border: 1px solid #3a3d45; background: #171a20; color: #b9b6a7; padding: 8px 14px; border-radius: 999px; cursor: pointer; font-size: 14px; }
.admin__tabs button.is-active { background: #42b883; border-color: #42b883; color: #06281a; font-weight: 700; }
.admin__main h2 { font-size: 18px; margin: 6px 0 14px; color: #dcd9ca; }
.field-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 10px 14px; margin-bottom: 12px; }
.field-grid label, .wide { display: flex; flex-direction: column; gap: 4px; font-size: 13px; color: #9a9788; }
.wide { margin-bottom: 10px; }
input, textarea, select { width: 100%; box-sizing: border-box; padding: 9px 10px; border-radius: 7px; border: 1px solid #3a3d45; background: #191c22; color: #e8e6d9; font-size: 14px; font-family: inherit; }
.checkbox-field { flex-direction: row; align-items: center; gap: 8px; }
.checkbox-field input { width: auto; }
.doc-preview { margin: 4px 0 14px; padding: 14px; border: 1px solid #2a2d34; border-radius: 8px; background: #0f1115; color: #d8d5c7; line-height: 1.8; max-height: 420px; overflow: auto; }
.doc-preview :deep(h1), .doc-preview :deep(h2), .doc-preview :deep(h3) { margin: 14px 0 8px; color: #e8e6d9; }
.doc-preview :deep(p), .doc-preview :deep(ul), .doc-preview :deep(ol) { margin: 0 0 10px; }
.doc-preview :deep(ul), .doc-preview :deep(ol) { padding-left: 1.4em; }
.doc-preview :deep(code) { background: #191c22; border: 1px solid #2a2d34; padding: 1px 4px; }
.doc-preview :deep(pre) { background: #191c22; border: 1px solid #2a2d34; padding: 10px; overflow: auto; }
.doc-preview :deep(table) { width: 100%; border-collapse: collapse; }
.doc-preview :deep(th), .doc-preview :deep(td) { border: 1px solid #2a2d34; padding: 4px 8px; }
textarea { resize: vertical; }
.row-3 { display: grid; grid-template-columns: 1fr 1fr auto; gap: 10px; align-items: end; margin-bottom: 8px; }
.item-card { border: 1px solid #2a2d34; border-radius: 10px; padding: 14px; margin-bottom: 14px; background: #14161b; }
.item-card header { display: flex; justify-content: space-between; align-items: center; gap: 10px; margin-bottom: 12px; }
.sub-block { border: 1px dashed #3a3d45; border-radius: 10px; padding: 10px 14px; margin-bottom: 16px; }
.sub-block summary { cursor: pointer; color: #b9b6a7; }
.image-field { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin-bottom: 10px; font-size: 13px; color: #9a9788; }
.image-field img { width: 64px; height: 64px; object-fit: cover; border-radius: 6px; border: 1px solid #3a3d45; background: #000; }
.image-field input { width: 240px; }
.save-btn, .add-btn { margin: 8px 8px 30px 0; padding: 11px 20px; border-radius: 8px; border: none; cursor: pointer; font-weight: 700; }
.save-btn { background: #42b883; color: #06281a; }
.add-btn { background: transparent; border: 1px dashed #4a4d55; color: #b9b6a7; }
.add-btn:hover { border-color: #42b883; color: #42b883; }
button.danger { background: transparent; border: 1px solid #a34848; color: #e08585; border-radius: 6px; padding: 5px 10px; cursor: pointer; font-size: 12px; }
.settings-card { max-width: 420px; display: flex; flex-direction: column; gap: 12px; border: 1px solid #2a2d34; border-radius: 10px; padding: 16px; background: #14161b; }
.settings-card h3 { margin: 0; font-size: 15px; }
.hint { font-size: 12px; color: #8a887b; }
.data-note { color: #6e6c60; font-size: 12px; border-top: 1px solid #22252b; padding-top: 12px; }
.admin__msg { padding: 10px 14px; border-radius: 8px; background: #17301f; color: #7fd8ab; }
.admin__msg.is-error { background: #331717; color: #e79b9b; }
@media (max-width: 640px) { .row-3 { grid-template-columns: 1fr 1fr; } .admin { padding: 14px; } }
</style>