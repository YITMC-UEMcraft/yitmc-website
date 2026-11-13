<template>
  <div class="admin bg-grid">
    <p v-if="phase === 'checking'" class="admin__center">正在检查登录状态…</p>

    <form v-else-if="phase === 'login'" class="admin__login" @submit.prevent="login">
      <h1>YITMC 网站内容管理</h1>
      <p class="admin__tip">皮肤站管理员登录官网后可直接进入；也可以输入管理密码。</p>
      <input v-model="password" type="password" placeholder="管理密码" autocomplete="current-password" />
      <button type="submit" :disabled="busy || !password">登 录</button>
      <p v-if="msg" class="admin__msg" :class="{ 'is-error': isError }">{{ msg }}</p>
      <a href="/" class="admin__back">← 返回官网首页</a>
    </form>

    <template v-else>
      <header class="admin__bar">
        <strong>YITMC 内容管理</strong>
        <span v-if="adminVia === 'oidc'" class="admin__identity">
          {{ adminName }} · {{ adminRole === 'super_admin' ? '超级管理员' : '管理员' }}（皮肤站）
        </span>
        <span class="admin__bar-tip">保存后刷新网站即可看到最新内容</span>
        <RouterLink to="/" class="admin__back">← 返回官网首页</RouterLink>
        <button type="button" @click="logout">退出登录</button>
      </header>

      <nav class="admin__tabs">
        <button v-for="t in visibleTabs" :key="t.key" type="button"
          :class="{ 'is-active': tab === t.key }" @click="tab = t.key">{{ t.label }}</button>
      </nav>

      <main class="admin__main">
        <section v-show="tab === 'overview'">
          <h2>概览</h2>
          <p class="hint">当前站点内容与待处理事项。点击卡片可直接跳到对应模块。</p>
          <div class="overview-grid">
            <button type="button" class="overview-card" @click="tab = 'works'">
              <b>{{ works.works.length }}</b><span>作品</span>
            </button>
            <button type="button" class="overview-card" @click="tab = 'news'">
              <b>{{ news.news.length }}</b><span>动态</span>
            </button>
            <button type="button" class="overview-card" @click="tab = 'events'">
              <b>{{ events.events.length }}</b><span>活动</span>
            </button>
            <button type="button" class="overview-card" @click="tab = 'docs'">
              <b>{{ docs.docs.length }}</b><span>文档</span>
            </button>
            <button type="button" class="overview-card" @click="tab = 'members'">
              <b>{{ members.leadership.length }}</b><span>成员</span>
            </button>
            <button type="button" class="overview-card" @click="tab = 'servers'">
              <b>{{ servers.servers.length }}</b><span>服务器</span>
            </button>
            <button type="button" class="overview-card" :class="{ 'is-alert': overview.pending > 0 }" @click="tab = 'wall'">
              <b>{{ overview.pending }}</b><span>待审留言</span>
            </button>
            <button type="button" class="overview-card" @click="tab = 'images'">
              <b>{{ overview.images }}</b><span>图片</span>
            </button>
          </div>
          <div class="overview-actions">
            <button type="button" class="save-btn" @click="tab = 'wall'">处理留言</button>
            <button type="button" class="add-btn" @click="uploadToLibrary">上传图片</button>
            <button type="button" class="add-btn" :disabled="overview.loading" @click="loadOverview">
              {{ overview.loading ? '刷新中…' : '刷新概览' }}
            </button>
          </div>
        </section>

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
              <label><span>关联档案 ID（可留空）</span><input v-model.number="m.profileId" type="number" min="1" placeholder="皮肤站用户档案 ID" /></label>
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

        <section v-show="tab === 'servers'">
          <div class="admin-section-head">
            <div>
              <h2>服务器列表</h2>
              <p class="hint">保存后首页服务器状态、PCL 主页和状态代理会共用这份列表。port 为 0 表示自动 SRV 解析；fallbackPort 为 SRV 失败时的兜底端口。</p>
            </div>
            <button type="button" class="add-btn" :disabled="busy" @click="addServer">+ 添加服务器</button>
          </div>

          <div v-if="servers.servers.length" class="server-list">
            <button
              v-for="(s, i) in servers.servers"
              :key="s.id || 'srv' + i"
              type="button"
              class="server-list__item"
              :class="{ 'is-active': selectedServerIndex === i }"
              @click="selectedServerIndex = i"
            >
              <span class="server-list__name">{{ s.name || '(未命名服务器)' }}</span>
              <span class="server-list__meta">
                {{ s.edition === 'bedrock' ? '基岩版' : 'Java 版' }} ·
                {{ s.displayAddress || s.address || '未填写地址' }}{{ s.port ? ':' + s.port : '' }}
              </span>
              <span class="server-list__badge">{{ s.note || '无说明' }}</span>
            </button>
          </div>
          <p v-else class="server-empty">还没有服务器，点击右上角「添加服务器」创建第一台。</p>

          <div v-if="currentServer" class="item-card server-editor">
            <header>
              <b>编辑：{{ currentServer.name || '(未命名服务器)' }}</b>
              <button type="button" class="danger" @click="removeServer(selectedServerIndex)">删除这台服务器</button>
            </header>
            <div class="field-grid">
              <label><span>名称</span><input v-model="currentServer.name" /></label>
              <label><span>说明</span><input v-model="currentServer.note" placeholder="如：社团主服" /></label>
              <label><span>真实地址</span><input v-model="currentServer.address" placeholder="srvl.yitmc.cn" /></label>
              <label><span>展示地址（可留空）</span><input v-model="currentServer.displayAddress" placeholder="留空则同真实地址" /></label>
              <label><span>端口（0 = SRV）</span><input v-model.number="currentServer.port" type="number" min="0" max="65535" /></label>
              <label><span>兜底端口</span><input v-model.number="currentServer.fallbackPort" type="number" min="0" max="65535" /></label>
              <label><span>版本类型</span>
                <select v-model="currentServer.edition">
                  <option value="java">Java 版</option>
                  <option value="bedrock">基岩版</option>
                </select>
              </label>
              <label><span>编号 ID</span><input v-model="currentServer.id" /></label>
            </div>
            <div class="server-editor__actions">
              <button type="button" class="save-btn" :disabled="busy" @click="save('servers.json')">保存服务器列表</button>
            </div>
          </div>
          <p class="hint">提示：状态探测目前只支持 Java 版；基岩版仅用于展示。</p>
        </section>

        <section v-show="tab === 'pcl'">
          <h2>PCL 主页</h2>
          <p class="hint">控制 PCL2 自定义主页的标题、欢迎语、新闻条数与快捷按钮。修改后 PCL2 会在下次刷新主页时读取。</p>
          <div class="field-grid">
            <label><span>卡片标题</span><input v-model="pcl.title" /></label>
            <label><span>新闻条数（0–5）</span><input v-model.number="pcl.newsCount" type="number" min="0" max="5" /></label>
          </div>
          <label class="wide"><span>欢迎语</span><textarea v-model="pcl.intro" rows="3"></textarea></label>
          <div v-for="(link, i) in pcl.links" :key="'pcl' + i" class="item-card">
            <header>
              <b>快捷按钮 {{ i + 1 }}</b>
              <button type="button" class="danger" @click="del(pcl.links, i, '这个按钮')">删除</button>
            </header>
            <div class="field-grid">
              <label><span>按钮文字</span><input v-model="link.label" placeholder="如：访问官网" /></label>
              <label><span>链接（http/https）</span><input v-model="link.url" placeholder="https://..." /></label>
            </div>
          </div>
          <button type="button" class="add-btn" :disabled="busy" @click="addPclLink">+ 添加快捷按钮</button>

          <details class="sub-block" open>
            <summary>自定义卡片（{{ pcl.cards.length }}）</summary>
            <p class="hint">
              卡片会插在 PCL 主页欢迎卡片和服务器列表之间。文字里可用
              <code>{%year%}</code>、<code>{%date%}</code>、<code>{%weekday%}</code>、
              <code>{%news_1_title%}</code>、<code>{%news_1_url%}</code>，以及下方自定义变量。
            </p>

            <div v-for="(card, ci) in pcl.cards" :key="'pclcard' + ci" class="item-card">
              <header>
                <b>卡片 {{ ci + 1 }}：{{ card.title || '(未命名)' }}</b>
                <button type="button" class="danger" @click="del(pcl.cards, ci, '这张卡片')">删除卡片</button>
              </header>
              <div class="field-grid">
                <label><span>卡片标题</span><input v-model="card.title" /></label>
                <label class="checkbox-field"><span>启用</span><input v-model="card.enabled" type="checkbox" /></label>
              </div>

              <div v-for="(row, ri) in card.rows" :key="'row' + ri" class="pcl-row">
                <div class="field-grid">
                  <label><span>行类型</span>
                    <select v-model="row.type">
                      <option value="text">文本</option>
                      <option value="list_item">列表项</option>
                      <option value="hint">提示条</option>
                      <option value="divider">分隔线</option>
                    </select>
                  </label>
                  <template v-if="row.type === 'text'">
                    <label><span>文本</span><input v-model="row.text" /></label>
                    <label><span>字号（10–24）</span><input v-model.number="row.fontSize" type="number" min="10" max="24" /></label>
                  </template>
                  <template v-else-if="row.type === 'list_item'">
                    <label><span>标题</span><input v-model="row.title" /></label>
                    <label><span>信息</span><input v-model="row.info" /></label>
                    <label><span>图标（方块名 / URL）</span><input v-model="row.logo" placeholder="GoldBlock" /></label>
                    <label><span>链接（可留空）</span><input v-model="row.url" placeholder="https://..." /></label>
                  </template>
                  <template v-else-if="row.type === 'hint'">
                    <label><span>提示文字</span><input v-model="row.text" /></label>
                    <label><span>颜色</span>
                      <select v-model="row.theme">
                        <option value="Blue">Blue</option>
                        <option value="Red">Red</option>
                        <option value="Yellow">Yellow</option>
                        <option value="Green">Green</option>
                      </select>
                    </label>
                  </template>
                  <template v-else>
                    <label><span>分隔文字（可留空）</span><input v-model="row.text" placeholder="默认 ────────────" /></label>
                  </template>
                </div>
                <button type="button" class="danger" @click="del(card.rows, ri, '这一行')">删除行</button>
              </div>
              <button type="button" class="add-btn" @click="addPclRow(card)">+ 添加一行</button>

              <div v-for="(btn, bi) in card.buttons" :key="'btn' + bi" class="pcl-row">
                <div class="field-grid">
                  <label><span>按钮文字</span><input v-model="btn.label" /></label>
                  <label><span>链接（http/https）</span><input v-model="btn.url" placeholder="https://..." /></label>
                </div>
                <button type="button" class="danger" @click="del(card.buttons, bi, '这个按钮')">删除按钮</button>
              </div>
              <button type="button" class="add-btn" @click="addPclButton(card)">+ 添加按钮</button>
            </div>

            <button type="button" class="add-btn" :disabled="busy" @click="addPclCard">+ 添加卡片</button>
          </details>

          <details class="sub-block">
            <summary>自定义变量（{{ pcl.vars.length }}）</summary>
            <p class="hint">在卡片文字里用 <code>{%变量名%}</code> 引用；同名变量会覆盖内置的日期/新闻变量。</p>
            <div v-for="(item, vi) in pcl.vars" :key="'var' + vi" class="row-3">
              <label><span>变量名（字母/数字/_）</span><input v-model="item.key" placeholder="qq_group" /></label>
              <label><span>值</span><input v-model="item.value" /></label>
              <button type="button" class="danger" @click="del(pcl.vars, vi, '这个变量')">删除</button>
            </div>
            <button type="button" class="add-btn" @click="addPclVar">+ 添加变量</button>
          </details>

          <button type="button" class="save-btn" :disabled="busy" @click="save('pcl.json')">保存「PCL 主页」</button>
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

        <section v-show="tab === 'wall'">
          <h2>留言墙</h2>
          <p class="hint">先审后发：新留言默认待审核，通过后才会出现在前台。删除不可恢复。</p>
          <div class="wall-tabs">
            <button v-for="opt in [
              { key: 'pending', label: '待审核' },
              { key: 'approved', label: '已通过' },
              { key: 'hidden', label: '已屏蔽' },
              { key: 'all', label: '全部' },
            ]" :key="opt.key" type="button" :class="{ 'is-active': wall.status === opt.key }" @click="setWallStatus(opt.key)">
              {{ opt.label }}
            </button>
          </div>

          <p v-if="wall.loading" class="hint">正在加载留言…</p>
          <p v-else-if="!wall.messages.length" class="hint">当前分类下暂无留言。</p>
          <div v-for="message in wall.messages" :key="message.id" class="item-card">
            <header>
              <b>{{ message.name }}</b>
              <span class="wall-status" :class="`wall-status--${message.status}`">{{ wallStatusLabel(message.status) }}</span>
            </header>
            <p class="wall-content">{{ message.content }}</p>
            <p class="wall-meta">
              <span v-if="message.userId" class="wall-account">账号绑定 #{{ message.userId }} · </span>
              {{ formatDate(message.createdAt) }}
            </p>
            <div class="wall-actions">
              <button v-if="message.status !== 'approved'" type="button" @click="moderateWall(message.id, 'approved')">通过</button>
              <button v-if="message.status !== 'hidden'" type="button" @click="moderateWall(message.id, 'hidden')">隐藏</button>
              <button type="button" class="danger" @click="moderateWall(message.id, 'delete')">删除</button>
            </div>
          </div>

          <div v-if="wall.pages > 1" class="wall-pager">
            <button type="button" :disabled="wall.page <= 1 || wall.loading" @click="loadWall(wall.page - 1)">上一页</button>
            <span>{{ wall.page }} / {{ wall.pages }}（共 {{ wall.total }} 条）</span>
            <button type="button" :disabled="wall.page >= wall.pages || wall.loading" @click="loadWall(wall.page + 1)">下一页</button>
          </div>
        </section>

        <section v-show="tab === 'images'">
          <h2>图片库</h2>
          <p class="hint">上传后的图片会保存到服务器 /uploads/，可在作品、动态、成员等处复用。仍被内容引用的图片不能删除。</p>
          <button type="button" class="save-btn" :disabled="busy" @click="uploadToLibrary">上传图片</button>
          <button type="button" class="add-btn" @click="loadImages">刷新列表</button>

          <p v-if="!images.length" class="hint">还没有上传过图片。</p>
          <div class="image-library">
            <div v-for="image in images" :key="image.name" class="image-card">
              <img :src="image.url" :alt="image.name" loading="lazy" />
              <div class="image-card__info">
                <b>{{ image.name }}</b>
                <span>{{ formatSize(image.size) }} · {{ formatDate(image.mtime) }}</span>
              </div>
              <div class="image-card__actions">
                <button type="button" @click="copyImage(image.url)">复制链接</button>
                <button type="button" class="danger" @click="deleteImage(image.name)">删除</button>
              </div>
            </div>
          </div>
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
import { computed, onMounted, reactive, ref, watch } from 'vue'
import {
  siteConfig, worksData, newsData, membersData, statsData, eventsData, docsData,
  serversData, pclData, type PclConfig,
} from '@/data'
import { renderMarkdown } from '@/lib/markdown'

const API = '/admin-api.php'

const tabs = [
  { key: 'overview', label: '概览' },
  { key: 'site', label: '联系方式' },
  { key: 'works', label: '作品展示' },
  { key: 'news', label: '社团动态' },
  { key: 'events', label: '活动中心' },
  { key: 'docs', label: '指南文档' },
  { key: 'members', label: '成员风采' },
  { key: 'servers', label: '服务器列表' },
  { key: 'pcl', label: 'PCL 主页' },
  { key: 'stats', label: '首页数据' },
  { key: 'wall', label: '留言墙' },
  { key: 'images', label: '图片库' },
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
const tab = ref('overview')
const adminVia = ref<'password' | 'oidc'>('password')
const adminRole = ref('super_admin')
const adminName = ref('')
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
const servers = computed(() => dataFiles.value['servers.json'] ?? { servers: [] })
const selectedServerIndex = ref(0)
const currentServer = computed(() => servers.value.servers[selectedServerIndex.value] || null)
watch(
  () => servers.value.servers.length,
  (length) => {
    if (length === 0) selectedServerIndex.value = 0
    else if (selectedServerIndex.value >= length) selectedServerIndex.value = length - 1
  },
)
const pcl = computed<PclConfig>(() => dataFiles.value['pcl.json'] ?? pclData)
const wall = reactive({
  messages: [] as any[],
  total: 0,
  page: 1,
  pages: 1,
  status: 'pending',
  loading: false,
})
const images = ref<{ name: string; url: string; size: number; mtime: number }[]>([])
const overview = reactive({ pending: 0, images: 0, loading: false })
const docCategories = computed<string[]>(() =>
  [...new Set<string>(docs.value.docs.map((d: any) => String(d.category || '')).filter(Boolean))],
)
const docPreview = reactive<Record<number, boolean>>({})

const visibleTabs = computed(() =>
  tabs.filter((item) => item.key !== 'settings' || adminVia.value === 'password'),
)

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
  const serversFile = files['servers.json']
  ensure('servers.json', !!serversFile && Array.isArray(serversFile.servers), serversData)
  const pclFile = files['pcl.json']
  ensure('pcl.json', !!pclFile && typeof pclFile === 'object' && Array.isArray(pclFile.links), pclData)
  const pclDataFile = files['pcl.json']
  if (!Array.isArray(pclDataFile.cards)) pclDataFile.cards = []
  if (!Array.isArray(pclDataFile.vars)) pclDataFile.vars = []

  dataFiles.value = files
  adminVia.value = st.via === 'oidc' ? 'oidc' : 'password'
  adminRole.value = st.role || 'super_admin'
  adminName.value = st.adminName || ''
  phase.value = 'ready'
  loadOverview()
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
  if (adminVia.value === 'oidc') {
    await api({ action: 'oidc_logout' }).catch(() => {})
  }
  dataFiles.value = {}
  adminVia.value = 'password'
  adminRole.value = 'super_admin'
  adminName.value = ''
  phase.value = 'login'
}

async function save(file: string) {
  if (file === 'pcl.json') {
    pcl.value.cards = (pcl.value.cards || []).filter(
      (card: any) => String(card.title || '').trim() || card.rows?.length || card.buttons?.length,
    )
    pcl.value.vars = (pcl.value.vars || []).filter((item: any) => String(item.key || '').trim())
  }
  if (file === 'members.json') {
    members.value.leadership.forEach((member: any) => {
      if (!member.profileId) delete member.profileId
    })
  }
  if (file === 'servers.json') {
    const invalid = servers.value.servers.some(
      (server: any) => !String(server.name || '').trim() || !String(server.address || '').trim(),
    )
    if (invalid) {
      flash('服务器名称和真实地址不能为空', true)
      return
    }
    servers.value.servers.forEach((server: any) => {
      server.port = Number(server.port) || 0
      server.fallbackPort = Number(server.fallbackPort) || 0
      if (!String(server.displayAddress || '').trim()) delete server.displayAddress
    })
  }
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

async function loadOverview() {
  overview.loading = true
  try {
    const [countsRes, imagesRes] = await Promise.all([
      api({ action: 'wall_counts' }),
      api({ action: 'images_list' }),
    ])
    if (countsRes.ok && countsRes.counts) {
      overview.pending = Number(countsRes.counts.pending) || 0
    }
    if (imagesRes.ok && Array.isArray(imagesRes.images)) {
      overview.images = imagesRes.images.length
    }
  } catch {
    // 概览加载失败不影响内容编辑
  } finally {
    overview.loading = false
  }
}

async function loadWall(page = wall.page) {
  wall.loading = true
  try {
    const res = await api({ action: 'wall_admin_list', status: wall.status, page, limit: 20 })
    if (res.ok) {
      wall.messages = res.messages || []
      wall.total = Number(res.total) || 0
      wall.page = Number(res.page) || 1
      wall.pages = Number(res.pages) || 1
    } else {
      flash(res.error || '加载留言失败', true)
    }
  } catch {
    flash('加载留言失败，请稍后重试', true)
  } finally {
    wall.loading = false
  }
}

async function setWallStatus(status: string) {
  wall.status = status
  await loadWall(1)
}

async function moderateWall(id: number, status: 'approved' | 'hidden' | 'delete') {
  if (status === 'delete' && !confirm('确定删除这条留言吗？删除后不可恢复。')) return
  busy.value = true
  try {
    const res = await api({ action: 'wall_moderate', id, status })
    if (res.ok) {
      flash(status === 'delete' ? '留言已删除' : '留言状态已更新')
      await loadWall(wall.page)
      loadOverview()
    } else {
      flash(res.error || '操作失败', true)
    }
  } catch {
    flash('操作失败，请稍后重试', true)
  } finally {
    busy.value = false
  }
}

async function loadImages() {
  try {
    const res = await api({ action: 'images_list' })
    if (res.ok) images.value = res.images || []
    else flash(res.error || '加载图片失败', true)
  } catch {
    flash('加载图片失败，请稍后重试', true)
  }
}

async function deleteImage(name: string) {
  if (!confirm('确定删除这张图片吗？仍被内容引用的图片会被拒绝删除。')) return
  busy.value = true
  try {
    const res = await api({ action: 'images_delete', name })
    if (res.ok) {
      flash('图片已删除')
      await loadImages()
      loadOverview()
    } else {
      flash(res.error || '删除失败', true)
    }
  } catch {
    flash('删除失败，请稍后重试', true)
  } finally {
    busy.value = false
  }
}

async function copyImage(url: string) {
  const full = location.origin + url
  try {
    await navigator.clipboard.writeText(full)
    flash('图片链接已复制：' + full)
  } catch {
    flash('复制失败，请手动复制：' + full, true)
  }
}

function uploadToLibrary() {
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
      if (res.ok) {
        flash('图片已上传：' + res.url)
        await loadImages()
        loadOverview()
      } else {
        flash(res.error || '上传失败', true)
      }
    } catch {
      flash('上传失败，请稍后重试', true)
    } finally {
      busy.value = false
    }
  }
  input.click()
}

function addServer() {
  servers.value.servers.push({
    id: `server-${Date.now().toString(36)}`,
    name: '',
    note: '',
    address: '',
    displayAddress: '',
    port: 0,
    fallbackPort: 25565,
    edition: 'java',
  })
  selectedServerIndex.value = servers.value.servers.length - 1
}

function removeServer(index: number) {
  if (!confirm('确定要删除这台服务器吗？删除后点下方“保存”才会生效。')) return
  servers.value.servers.splice(index, 1)
  selectedServerIndex.value = Math.max(0, Math.min(index, servers.value.servers.length - 1))
}

function addPclLink() {
  pcl.value.links.push({ label: '', url: '' })
}

function addPclCard() {
  pcl.value.cards.push({
    id: `card-${Date.now().toString(36)}`,
    title: '',
    enabled: true,
    rows: [],
    buttons: [],
  })
}

function addPclRow(card: any) {
  card.rows.push({ type: 'text', text: '', fontSize: 13 })
}

function addPclButton(card: any) {
  card.buttons.push({ label: '', url: '' })
}

function addPclVar() {
  pcl.value.vars.push({ key: '', value: '' })
}

function wallStatusLabel(status: string): string {
  if (status === 'approved') return '已通过'
  if (status === 'hidden') return '已屏蔽'
  return '待审核'
}

function formatDate(timestamp: number): string {
  const date = new Date(timestamp * 1000)
  if (Number.isNaN(date.getTime())) return ''
  return date.toLocaleString('zh-CN', {
    year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit',
  })
}

function formatSize(bytes: number): string {
  if (bytes < 1024) return bytes + ' B'
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB'
  return (bytes / 1024 / 1024).toFixed(1) + ' MB'
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

watch(tab, (value) => {
  if (phase.value !== 'ready') return
  if (value === 'wall') loadWall(1)
  if (value === 'images') loadImages()
  if (value === 'overview') loadOverview()
})

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
.admin__identity { padding: 3px 9px; border: 1px solid #2d6b4a; border-radius: 999px; color: #7fd8ab; font-size: 12px; }
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
.overview-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 12px; margin-bottom: 16px; }
.overview-card { display: flex; flex-direction: column; gap: 4px; padding: 16px; text-align: left; background: #14161b; border: 1px solid #2a2d34; border-radius: 10px; color: inherit; cursor: pointer; }
.overview-card b { font-size: 28px; color: #42b883; }
.overview-card span { font-size: 13px; color: #9a9788; }
.overview-card.is-alert { border-color: #a34848; }
.overview-card.is-alert b { color: #e08585; }
.overview-actions { display: flex; flex-wrap: wrap; gap: 8px; }
.wall-tabs { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 14px; }
.wall-tabs button { padding: 6px 14px; border: 1px solid #3a3d45; border-radius: 999px; background: #171a20; color: #b9b6a7; cursor: pointer; }
.wall-tabs button.is-active { background: #42b883; border-color: #42b883; color: #06281a; font-weight: 700; }
.wall-status { padding: 2px 8px; border: 1px solid #3a3d45; border-radius: 999px; font-size: 12px; color: #b9b6a7; }
.wall-status--approved { color: #7fd8ab; border-color: #2d6b4a; }
.wall-status--pending { color: #e7c47f; border-color: #7a5f2a; }
.wall-status--hidden { color: #e08585; border-color: #7a3a3a; }
.wall-content { margin: 6px 0; white-space: pre-line; line-height: 1.7; color: #d8d5c7; overflow-wrap: anywhere; }
.wall-meta { font-size: 12px; color: #8a887b; }
.wall-account { color: #7fd8ab; }
.wall-actions { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px; }
.wall-actions button { padding: 5px 12px; border: 1px solid #4a4d55; border-radius: 6px; background: transparent; color: #b9b6a7; cursor: pointer; }
.wall-actions button.danger { border-color: #a34848; color: #e08585; }
.wall-pager { display: flex; align-items: center; justify-content: center; gap: 10px; margin: 10px 0 30px; font-size: 13px; color: #9a9788; }
.wall-pager button { padding: 5px 12px; border: 1px solid #4a4d55; border-radius: 6px; background: transparent; color: inherit; cursor: pointer; }
.image-library { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 14px; margin-top: 14px; }
.image-card { display: flex; flex-direction: column; overflow: hidden; background: #14161b; border: 1px solid #2a2d34; border-radius: 10px; }
.image-card img { width: 100%; aspect-ratio: 4 / 3; object-fit: cover; background: #000; }
.image-card__info { display: flex; flex-direction: column; gap: 3px; padding: 10px; font-size: 12px; color: #9a9788; }
.image-card__info b { overflow: hidden; color: #d8d5c7; text-overflow: ellipsis; white-space: nowrap; }
.image-card__actions { display: flex; gap: 6px; padding: 0 10px 10px; }
.image-card__actions button { flex: 1; padding: 5px; border: 1px solid #4a4d55; border-radius: 6px; background: transparent; color: #b9b6a7; cursor: pointer; font-size: 12px; }
.image-card__actions button.danger { border-color: #a34848; color: #e08585; }
.pcl-row { margin-top: 10px; padding-top: 10px; border-top: 1px dashed #2a2d34; }
.pcl-row .field-grid { margin-bottom: 6px; }
.pcl-row > button.danger { margin-bottom: 6px; }
.sub-block code { padding: 1px 4px; border-radius: 4px; background: #191c22; color: #d8d5c7; }
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

/* ===== 与官网主题统一 ===== */
.admin {
  background-color: var(--color-bg);
  color: var(--color-text);
  font-family: var(--font-body);
}
.admin__center { color: var(--color-text-muted); }
.admin__login {
  padding: var(--space-xl);
  background: var(--color-surface);
  border: var(--border-width) solid var(--color-brown);
  box-shadow: var(--shadow-pixel);
}
.admin__login h1 { font-family: var(--font-display); color: var(--color-text); }
.admin__tip { color: var(--color-text-muted); }
.admin__login input,
input, textarea, select {
  background: var(--color-bg);
  color: var(--color-text);
  border: var(--border-width-sm) solid var(--color-surface-light);
  border-radius: 0;
}
input:focus, textarea:focus, select:focus { outline: none; border-color: var(--color-accent); }
.admin__login button,
.save-btn {
  background: var(--color-accent);
  border: var(--border-width-sm) solid var(--color-accent-dim);
  color: #10200a;
  border-radius: 0;
  font-family: var(--font-display);
}
.admin__login button:hover,
.save-btn:hover { background: var(--color-accent-hover); }
.admin__back { color: var(--color-text-muted); }
.admin__back:hover { color: var(--color-accent); }
.admin__bar { border-bottom-color: var(--color-surface-light); }
.admin__bar strong { font-family: var(--font-display); font-size: var(--text-lg); }
.admin__bar-tip { color: var(--color-text-muted); }
.admin__identity {
  border-color: var(--color-accent-dim);
  color: var(--color-accent);
  border-radius: 0;
}
.admin__bar button {
  border-color: var(--color-surface-light);
  color: var(--color-text);
  border-radius: 0;
}
.admin__bar button:hover { border-color: var(--color-accent); color: var(--color-accent); }
.admin__tabs button,
.wall-tabs button {
  background: var(--color-surface);
  border: var(--border-width-sm) solid var(--color-surface-light);
  color: var(--color-text-muted);
  border-radius: 0;
  font-family: var(--font-display);
}
.admin__tabs button.is-active,
.wall-tabs button.is-active {
  background: var(--color-accent);
  border-color: var(--color-accent-dim);
  color: #10200a;
}
.admin__main h2 { color: var(--color-text); font-family: var(--font-display); }
.field-grid label, .wide, .checkbox-field { color: var(--color-text-muted); }
.doc-preview {
  background: var(--color-bg);
  border-color: var(--color-surface-light);
  color: var(--color-text);
  border-radius: 0;
}
.doc-preview :deep(h1), .doc-preview :deep(h2), .doc-preview :deep(h3) { color: var(--color-text); }
.doc-preview :deep(code) { background: var(--color-surface); border-color: var(--color-surface-light); color: var(--color-text); }
.doc-preview :deep(pre) { background: var(--color-surface); border-color: var(--color-surface-light); }
.doc-preview :deep(th), .doc-preview :deep(td) { border-color: var(--color-surface-light); }
.overview-card, .item-card, .settings-card, .image-card {
  background: var(--color-surface);
  border: var(--border-width-sm) solid var(--color-surface-light);
  border-radius: 0;
  box-shadow: var(--shadow-pixel-sm);
}
.overview-card b { color: var(--color-accent); }
.overview-card span { color: var(--color-text-muted); }
.overview-card.is-alert { border-color: var(--color-danger); }
.overview-card.is-alert b { color: var(--color-danger); }
.wall-status {
  border-color: var(--color-surface-light);
  color: var(--color-text-muted);
  border-radius: 0;
}
.wall-status--approved { color: var(--color-accent); border-color: var(--color-accent-dim); }
.wall-status--pending { color: var(--color-gold); border-color: var(--color-gold-dim); }
.wall-status--hidden { color: var(--color-danger); border-color: var(--color-danger); }
.wall-content { color: var(--color-text); }
.wall-meta { color: var(--color-text-muted); }
.wall-account { color: var(--color-accent); }
.wall-actions button,
.wall-pager button {
  border-color: var(--color-surface-light);
  color: var(--color-text);
  border-radius: 0;
}
.wall-actions button:hover,
.wall-pager button:hover { border-color: var(--color-accent); color: var(--color-accent); }
.wall-actions button.danger { border-color: var(--color-danger); color: var(--color-danger); }
.image-card img { background: var(--color-bg); }
.image-card__info { color: var(--color-text-muted); }
.image-card__info b { color: var(--color-text); }
.image-card__actions button {
  border-color: var(--color-surface-light);
  color: var(--color-text);
  border-radius: 0;
}
.image-card__actions button:hover { border-color: var(--color-accent); color: var(--color-accent); }
.image-card__actions button.danger { border-color: var(--color-danger); color: var(--color-danger); }
.sub-block {
  background: var(--color-surface);
  border-color: var(--color-brown);
  border-radius: 0;
}
.sub-block summary { color: var(--color-text); }
.sub-block code { background: var(--color-bg); color: var(--color-text); }
.image-field { color: var(--color-text-muted); }
.image-field img { border-color: var(--color-surface-light); background: var(--color-bg); }
.add-btn {
  background: var(--color-surface);
  border: var(--border-width-sm) dashed var(--color-brown);
  color: var(--color-text-muted);
  border-radius: 0;
}
.add-btn:hover { border-color: var(--color-accent); color: var(--color-accent); }
button.danger {
  border-color: var(--color-danger);
  color: var(--color-danger);
  border-radius: 0;
}
button.danger:hover { background: rgba(192, 57, 43, 0.12); }
.settings-card h3 { color: var(--color-text); }
.hint, .data-note { color: var(--color-text-muted); }
.admin__msg {
  background: rgba(92, 154, 59, 0.15);
  border: var(--border-width-sm) solid var(--color-accent-dim);
  color: var(--color-accent);
  border-radius: 0;
}
.admin__msg.is-error {
  background: rgba(192, 57, 43, 0.15);
  border-color: var(--color-danger);
  color: var(--color-danger);
}

/* ===== 服务器列表编辑 ===== */
.admin-section-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: var(--space-md);
  flex-wrap: wrap;
  margin-bottom: var(--space-md);
}
.admin-section-head h2 { margin-top: 0; }
.admin-section-head .add-btn { margin: 0; }
.server-list {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  gap: 10px;
  margin-bottom: var(--space-md);
}
.server-list__item {
  display: flex;
  flex-direction: column;
  gap: 3px;
  padding: 12px 14px;
  text-align: left;
  background: var(--color-surface);
  border: var(--border-width-sm) solid var(--color-surface-light);
  color: var(--color-text);
  border-radius: 0;
  cursor: pointer;
  transition: border-color var(--duration-fast) var(--ease-out);
}
.server-list__item:hover { border-color: var(--color-accent-dim); }
.server-list__item.is-active {
  border-color: var(--color-accent);
  box-shadow: inset 0 0 0 1px var(--color-accent);
}
.server-list__name {
  font-family: var(--font-display);
  font-size: var(--text-base);
  color: var(--color-text);
}
.server-list__meta {
  font-size: 12px;
  color: var(--color-text-muted);
  overflow-wrap: anywhere;
}
.server-list__badge {
  align-self: flex-start;
  margin-top: 3px;
  padding: 1px 8px;
  font-size: 11px;
  color: var(--color-text-muted);
  border: 1px solid var(--color-surface-light);
}
.server-empty {
  padding: var(--space-lg);
  text-align: center;
  color: var(--color-text-muted);
  background: var(--color-surface);
  border: var(--border-width-sm) dashed var(--color-surface-light);
}
.server-editor { margin-top: 0; }
.server-editor__actions {
  display: flex;
  gap: var(--space-sm);
  margin-top: var(--space-xs);
}
.server-editor__actions .save-btn { margin: 0; }
.pcl-row { border-top-color: var(--color-surface-light); }
.data-note { border-top-color: var(--color-surface-light); }

@media (max-width: 640px) { .row-3 { grid-template-columns: 1fr 1fr; } .admin { padding: 14px; } }
</style>