// Public website: reveal sections as they enter the viewport. Without JavaScript everything is simply visible.
const items = document.querySelectorAll('[data-reveal]');
if ('IntersectionObserver' in window && !matchMedia('(prefers-reduced-motion: reduce)').matches) {
    document.documentElement.classList.add('js-reveal');
    const io = new IntersectionObserver((entries) => entries.forEach((e) => { if (e.isIntersecting) { e.target.classList.add('is-visible'); io.unobserve(e.target); } }), { threshold: 0.15 });
    items.forEach((el, i) => { el.style.transitionDelay = `${Math.min(i % 4, 3) * 70}ms`; io.observe(el); });
}
const header = document.querySelector('[data-header]');
if (header) addEventListener('scroll', () => header.classList.toggle('is-scrolled', scrollY > 12), { passive: true });
