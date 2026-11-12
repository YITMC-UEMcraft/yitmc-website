// Lightweight scroll reveal using Intersection Observer.
// Avoids importing full GSAP to keep bundle small.
// For GSAP-heavy work, dynamically import gsap + ScrollTrigger.

export function useScrollReveal() {
  let observer: IntersectionObserver | null = null

  function init(selector: string = '[data-reveal]') {
    if (typeof window === 'undefined') return

    // 环境不支持时直接显示全部内容，避免元素永远隐藏
    if (!('IntersectionObserver' in window)) {
      document.querySelectorAll(selector).forEach((el) => el.classList.add('is-revealed'))
      return
    }

    // 支持路由切换后重复调用：断开旧观察器，重新观察当前 DOM
    observer?.disconnect()

    observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.classList.add('is-revealed')
            observer?.unobserve(entry.target)
          }
        })
      },
      // threshold 用 0：15% 的比例阈值会让比视口高出数倍的容器
      //（如移动端作品列表）永远达不到比例，导致整块内容不显示
      { threshold: 0, rootMargin: '0px 0px -40px 0px' }
    )

    document.querySelectorAll(selector).forEach((el) => {
      if (!el.classList.contains('is-revealed')) observer?.observe(el)
    })
  }

  function destroy() {
    observer?.disconnect()
    observer = null
  }

  return { init, destroy }
}
