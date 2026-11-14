// Public website. One orchestrated entrance, one scroll-driven scene, and type that answers the pointer.
// Everything is readable without JavaScript; with reduced motion only the static page is shown.
import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

gsap.registerPlugin(ScrollTrigger);
const $ = (s, r = document) => r.querySelector(s);
const $$ = (s, r = document) => [...r.querySelectorAll(s)];
const calm = matchMedia('(prefers-reduced-motion: reduce)').matches;

const header = $('[data-header]');
const progress = $('[data-progress]');
const onScroll = () => {
    header?.classList.toggle('is-scrolled', scrollY > innerHeight * 0.6 || !$('[data-hero]'));
    if (progress) progress.style.transform = `scaleX(${Math.min(1, scrollY / Math.max(1, document.documentElement.scrollHeight - innerHeight))})`;
};
addEventListener('scroll', onScroll, { passive: true });
onScroll();

/* ---------- hero ---------- */
const hero = $('[data-hero]');
const letters = $$('[data-title] span:not(.sp)');
const titleEl = $('[data-title]');
// Size the name to the plate: at rest it spans most of the column, leaving room for the letters to widen.
const fit = () => {
    if (!titleEl) return;
    titleEl.classList.add('is-fit');
    titleEl.style.fontSize = '100px';
    titleEl.style.width = 'max-content';
    const natural = titleEl.getBoundingClientRect().width;
    titleEl.style.width = '';
    const size = Math.min((100 * titleEl.parentElement.clientWidth * 0.84) / natural, innerHeight * 0.36);
    titleEl.style.fontSize = size + 'px';
};
if (hero && letters.length) {
    document.fonts.ready.then(fit);
    addEventListener('resize', fit);
}
if (hero && letters.length && !calm) {
    const REST = 78, WIDE = 118;
    gsap.set(letters, { yPercent: 55, opacity: 0 });
    document.fonts.ready.then(() => {
        gsap.set(letters, { '--w': '50%' });
        gsap.timeline({ defaults: { ease: 'expo.out' } })
            .to(letters, { '--w': REST + '%', yPercent: 0, opacity: 1, duration: 1.1, stagger: 0.045 })
            .from($$('[data-in]', hero), { y: 22, opacity: 0, duration: 0.7, stagger: 0.09 }, '-=0.6');
    });

    // The letters nearest the pointer widen, as if the plate were being pressed there; the light follows too.
    let frame = 0;
    hero.addEventListener('pointermove', (e) => {
        if (e.pointerType === 'touch' || frame) return;
        frame = requestAnimationFrame(() => {
            frame = 0;
            const box = hero.getBoundingClientRect();
            hero.style.setProperty('--mx', `${((e.clientX - box.left) / box.width) * 100}%`);
            hero.style.setProperty('--my', `${((e.clientY - box.top) / box.height) * 100}%`);
            const reach = Math.max(180, innerWidth * 0.16);
            letters.forEach((el) => {
                const r = el.getBoundingClientRect();
                const d = Math.hypot(e.clientX - (r.left + r.width / 2), (e.clientY - (r.top + r.height / 2)) * 0.6);
                const k = Math.max(0, 1 - d / reach);
                gsap.to(el, { '--w': REST + (WIDE - REST) * k * k + '%', '--k': k, duration: 0.45, ease: 'power3.out', overwrite: 'auto' });
            });
        });
    });
    hero.addEventListener('pointerleave', () => gsap.to(letters, { '--w': REST + '%', '--k': 0, duration: 0.8, ease: 'elastic.out(1, 0.6)', overwrite: 'auto' }));
}

/* ---------- the path of a part ---------- */
const path = $('[data-path]');
if (path && !calm) {
    const q = (s) => $(s, path);
    const [s1, s2, s3] = [1, 2, 3].map((n) => q(`[data-s="${n}"]`));
    const steps = $$('[data-step]', path);
    const stops = $$('[data-rail] li', path);
    const rail = q('[data-rail]');
    const cut = q('[data-cut]');
    const laser = q('[data-laser]');
    const len = cut.getTotalLength();
    const beam = { p: 0 };
    const trace = () => {
        const pt = cut.getPointAtLength(len * beam.p);
        cut.style.strokeDashoffset = String(len * (1 - beam.p));
        laser.setAttribute('transform', `translate(${pt.x} ${pt.y})`);
    };
    path.classList.add('is-live');
    gsap.set(s1, { opacity: 1 });
    gsap.set([s2, s3], { opacity: 0 });
    gsap.set(cut, { strokeDasharray: len });
    gsap.set([q('[data-body]'), q('[data-flangefill]'), q('[data-fold]'), q('[data-slug]'), laser], { opacity: 0 });
    gsap.set($$('[data-hole]', path), { scale: 0, transformOrigin: '50% 50%' });
    gsap.set(q('[data-coat]'), { fill: '#a9b1b8' });
    gsap.set(q('[data-rack]'), { y: -34 });
    trace();

    let active = -1;
    const show = (i) => {
        if (i === active) return;
        active = i;
        steps.forEach((el, n) => el.classList.toggle('is-on', n === i));
        stops.forEach((el, n) => el.classList.toggle('is-on', n <= i));
    };
    show(0);

    const tl = gsap.timeline({
        defaults: { ease: 'none' },
        scrollTrigger: {
            trigger: path, start: 'center center', end: '+=260%', pin: true, scrub: 0.7, anticipatePin: 1,
        },
        // Driven by the timeline itself, so the text always matches what the scene shows (the scrub eases behind the scroll).
        onUpdate() {
            rail.style.setProperty('--p', this.progress().toFixed(3));
            const t = this.time();
            show(t < this.labels.piesa ? 0 : t < this.labels.protectia ? 1 : 2);
        },
    });
    // 1 · the die closes on the strip and punches a blank
    tl.to(q('[data-force]'), { y: 16, duration: 0.12, repeat: 1, yoyo: true })
        .to(q('[data-upper]'), { y: 76, duration: 0.3, ease: 'power2.in' })
        .to(q('[data-upper]'), { y: 100, duration: 0.07 })
        .set(q('[data-slug]'), { opacity: 1 })
        .to(q('[data-slug]'), { y: 40, opacity: 0, duration: 0.22 })
        .to(q('[data-upper]'), { y: 0, duration: 0.3, ease: 'power2.out' }, '<')
        .to({}, { duration: 0.1 })
        // 2 · the laser cuts the part out of the sheet, then the flange is bent
        .addLabel('piesa')
        .to(s1, { opacity: 0, duration: 0.12 })
        .to(s2, { opacity: 1, duration: 0.12 }, '<')
        .to(laser, { opacity: 1, duration: 0.04 })
        .to(beam, { p: 1, duration: 0.6, onUpdate: trace })
        .to($$('[data-hole]', path), { scale: 1, duration: 0.1, stagger: 0.05, ease: 'back.out(2.2)' })
        .to(laser, { opacity: 0, duration: 0.04 })
        .to([q('[data-body]'), q('[data-flangefill]')], { opacity: 1, duration: 0.1 })
        .to([q('.sheet--raw'), cut], { opacity: 0, duration: 0.1 }, '<')
        .to(q('[data-fold]'), { opacity: 1, duration: 0.05 })
        .to(q('[data-flange]'), { scaleX: 0.4, svgOrigin: '372 220', duration: 0.25, ease: 'power2.inOut' })
        .to(q('[data-flangefill]'), { fill: '#737b84', duration: 0.25 }, '<')
        .to({}, { duration: 0.1 })
        // 3 · the part goes into the bath and comes out zinc plated
        .addLabel('protectia')
        .to(s2, { opacity: 0, duration: 0.12 })
        .to(s3, { opacity: 1, duration: 0.12 }, '<')
        .to(q('[data-rack]'), { y: 92, duration: 0.3, ease: 'power1.inOut' })
        .to(q('[data-wave]'), { x: -41, duration: 0.6 }, '<')
        .to(q('[data-coat]'), { fill: '#cfdde6', duration: 0.3 })
        .to(q('[data-rack]'), { y: -34, duration: 0.3, ease: 'power1.inOut' })
        .fromTo(q('[data-sheen]'), { x: -20, opacity: 0.85 }, { x: 190, opacity: 0.85, duration: 0.22 })
        .to(q('[data-sheen]'), { opacity: 0, duration: 0.04 })
        .to({}, { duration: 0.12 });

    // In-page links land on the right step.
    $$('a[href$="#drum"]').forEach((a) => a.addEventListener('click', (e) => {
        e.preventDefault();
        path.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }));
    addEventListener('load', () => ScrollTrigger.refresh());
}
