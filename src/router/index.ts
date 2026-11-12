import { createRouter, createWebHistory } from 'vue-router'
import { setSeo } from '@/lib/seo'

declare module 'vue-router' {
  interface RouteMeta {
    title?: string
    description?: string
    noindex?: boolean
  }
}

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/',
      name: 'home',
      component: () => import('@/views/HomeView.vue'),
      meta: {
        title: 'YITMC - 燕京理工学院MC玩家创作协会',
        description: '燕京理工学院MC玩家创作协会(YITMC)官方网站 - 我的世界校园复刻工程展示与社团招新',
      },
    },
    {
      path: '/works',
      name: 'works',
      component: () => import('@/views/WorksView.vue'),
      meta: {
        title: '作品展示 - YITMC',
        description: 'YITMC 校园复刻工程与社团创作作品展示，包含复原工程、其他建筑与社团合照。',
      },
    },
    {
      path: '/works/:id',
      name: 'work-detail',
      component: () => import('@/views/WorkDetailView.vue'),
      meta: { title: '作品详情 - YITMC' },
    },
    {
      path: '/news',
      name: 'news',
      component: () => import('@/views/NewsView.vue'),
      meta: {
        title: '社团动态 - YITMC',
        description: 'YITMC 社团动态：活动公告、项目进展与荣誉成果。',
      },
    },
    {
      path: '/news/:id',
      name: 'news-detail',
      component: () => import('@/views/NewsDetailView.vue'),
      meta: { title: '社团动态 - YITMC' },
    },
    {
      path: '/events',
      name: 'events',
      component: () => import('@/views/EventsView.vue'),
      meta: {
        title: '活动中心 - YITMC',
        description: 'YITMC 近期活动与往期回顾：交流会、建筑赛与服务器活动。',
      },
    },
    {
      path: '/docs',
      name: 'docs',
      component: () => import('@/views/DocsView.vue'),
      meta: {
        title: '指南与文档 - YITMC',
        description: 'YITMC 新人指南、服务器连接与皮肤站使用文档。',
      },
    },
    {
      path: '/docs/:id',
      name: 'doc-detail',
      component: () => import('@/views/DocsView.vue'),
      meta: { title: '指南与文档 - YITMC' },
    },
    {
      path: '/wall',
      name: 'wall',
      component: () => import('@/views/WallView.vue'),
      meta: {
        title: '留言墙 - YITMC',
        description: 'YITMC 留言墙：留下你的足迹，与同好们分享想法与祝福。',
      },
    },
    {
      path: '/account',
      name: 'account',
      component: () => import('@/views/AccountView.vue'),
      meta: { title: '我的档案 - YITMC', noindex: true },
    },
    {
      path: '/profile/:id',
      name: 'profile',
      component: () => import('@/views/ProfileView.vue'),
      meta: { title: '成员主页 - YITMC', noindex: true },
    },
    {
      path: '/members',
      name: 'members',
      component: () => import('@/views/MembersView.vue'),
      meta: {
        title: '成员风采 - YITMC',
        description: 'YITMC 管理团队与成员风采。',
      },
    },
    {
      path: '/open',
      name: 'open',
      component: () => import('@/views/OpenPlatformView.vue'),
      meta: {
        title: '社团开放平台 - YITMC',
        description: 'YITMC 原创素材与网站源码，以 MIT 协议开放下载。',
      },
    },
    {
      path: '/about',
      name: 'about',
      component: () => import('@/views/AboutView.vue'),
      meta: {
        title: '关于我们 - YITMC',
        description: '燕京理工学院 MC 玩家创作协会简介、宗旨与官方信息。',
      },
    },
    {
      path: '/join',
      name: 'join',
      component: () => import('@/views/JoinView.vue'),
      meta: {
        title: '加入我们 - YITMC',
        description: '加入 YITMC：QQ 群、微信群、皮肤站与入服方式。',
      },
    },
    {
      path: '/admin',
      name: 'admin',
      component: () => import('@/views/AdminView.vue'),
      meta: { title: '网站内容管理 - YITMC', noindex: true },
    },
    {
      path: '/:pathMatch(.*)*',
      name: 'not-found',
      component: () => import('@/views/NotFoundView.vue'),
      meta: { title: '页面未找到 - YITMC', noindex: true },
    },
  ],
  scrollBehavior(to, _from, savedPosition) {
    if (savedPosition) return savedPosition
    if (to.hash) return { el: to.hash, top: 88, behavior: 'smooth' }
    return { top: 0 }
  },
})

router.afterEach((to) => {
  setSeo({
    title: to.meta.title,
    description: to.meta.description,
    path: to.path,
    noindex: to.meta.noindex,
  })
})

export default router
