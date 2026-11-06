import { initConsent, track } from './consent.js';
import { initWizard } from './wizard.js';
import { t } from './i18n.js';

const $ = (s, r = document) => r.querySelector(s);
const $$ = (s, r = document) => [...r.querySelectorAll(s)];

/* ---------- header & navigation ---------- */
function initHeader() {
  const header = $('[data-header]');
  if (!header) return;
  const forceSolid = header.classList.contains('is-solid') && !document.querySelector('[data-scene]') && !document.querySelector('.page-head');
  const onScroll = () => header.classList.toggle('is-solid', forceSolid || window.scrollY > 24);
  onScroll();
  window.addEventListener('scroll', onScroll, { passive: true });

  const toggle = $('[data-nav-toggle]');
  const nav = $('[data-nav]');
  if (!toggle || !nav) return;
  const close = () => {
    nav.classList.remove('is-open');
    toggle.setAttribute('aria-expanded', 'false');
    document.body.classList.remove('nav-open');
  };
  toggle.addEventListener('click', () => {
    const open = !nav.classList.contains('is-open');
    nav.classList.toggle('is-open', open);
    toggle.setAttribute('aria-expanded', String(open));
    document.body.classList.toggle('nav-open', open);
    if (open) nav.querySelector('a')?.focus();
  });
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && nav.classList.contains('is-open')) { close(); toggle.focus(); } });
  nav.addEventListener('click', (e) => { if (e.target.closest('a')) close(); });
}

/* ---------- tracked clicks (no personal data, only labels) ---------- */
function initTracking() {
  document.addEventListener('click', (e) => {
    const el = e.target.closest('[data-track]');
    if (!el) return;
    track(el.dataset.track, { link_label: el.dataset.trackLabel || '' });
  });
  const lead = $('[data-lead-ref]');
  if (lead) {
    // generate_lead fires once per reference: reloading the confirmation page does not count again.
    const key = 'elg_lead_' + lead.textContent.trim();
    if (!sessionStorage.getItem(key)) {
      sessionStorage.setItem(key, '1');
      track('generate_lead', { form_type: lead.dataset.leadRef || 'oferta' });
    }
    try { sessionStorage.removeItem('elg_quote_draft'); } catch (_) { /* ignore */ }
  }
}

/* ---------- gallery lightbox ---------- */
function initGallery() {
  const galleries = $$('[data-gallery]');
  if (!galleries.length) return;
  const dlg = document.createElement('dialog');
  dlg.className = 'lightbox';
  dlg.innerHTML = '<button class="btn btn--sm lightbox__close" type="button"></button><img alt=""><p></p>';
  dlg.querySelector('button').textContent = t('Închide');
  document.body.appendChild(dlg);
  const img = $('img', dlg);
  const cap = $('p', dlg);
  $('button', dlg).addEventListener('click', () => dlg.close());
  dlg.addEventListener('click', (e) => { if (e.target === dlg) dlg.close(); });
  galleries.forEach((g) => g.addEventListener('click', (e) => {
    const b = e.target.closest('button[data-full]');
    if (!b) return;
    img.src = b.dataset.full;
    img.alt = b.dataset.caption || '';
    cap.textContent = b.dataset.caption || '';
    dlg.showModal();
  }));
}

/* ---------- durability illustration ---------- */
function initDurability() {
  $$('[data-durability]').forEach((fig) => {
    const zinc = $('[data-zinc]', fig);
    const pass = $('[data-pass]', fig);
    const top = $('[data-top]', fig);
    const attack = $('[data-attack]', fig);
    const caption = $('[data-dur-caption]', fig);
    // Relative remaining zinc for a fixed, unspecified moment in time – qualitative only.
    const envs = [
      { zinc: 60, pass: 12, top: 8, dots: 0.25 },
      { zinc: 42, pass: 7, top: 3, dots: 0.6 },
      { zinc: 22, pass: 2, top: 0, dots: 1 },
    ];
    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const apply = (i) => {
      const e = envs[i];
      const set = (el, h, base) => { el.setAttribute('y', String(base - h)); el.setAttribute('height', String(h)); };
      const zTop = 200 - e.zinc;
      if (!reduce) [zinc, pass, top].forEach((el) => { el.style.transition = 'all .7s cubic-bezier(.2,.7,.2,1)'; });
      set(zinc, e.zinc, 200);
      set(pass, e.pass, zTop);
      set(top, e.top, zTop - e.pass);
      attack.style.opacity = String(e.dots);
    };
    $$('button[data-env]', fig).forEach((b) => b.addEventListener('click', () => {
      $$('button[data-env]', fig).forEach((x) => x.setAttribute('aria-pressed', String(x === b)));
      caption.textContent = b.dataset.text;
      apply(Number(b.dataset.env));
    }));
  });
}

/* ---------- click-to-load map (third-party content only on request) ---------- */
function initMap() {
  $$('[data-map]').forEach((m) => {
    $('[data-map-load]', m)?.addEventListener('click', () => {
      const f = document.createElement('iframe');
      f.src = m.dataset.src;
      f.title = t('Harta – locația Elgarom');
      f.loading = 'lazy';
      f.referrerPolicy = 'no-referrer-when-downgrade';
      m.replaceChildren(f);
      track('map_load', {});
    });
  });
}

/* ---------- light client-side validation for simple forms ---------- */
function initSimpleForms() {
  $$('form[data-validate]').forEach((form) => {
    form.addEventListener('submit', (e) => {
      let first = null;
      $$('input,textarea,select', form).forEach((el) => {
        if (!el.willValidate || el.type === 'hidden') return;
        const ok = el.checkValidity();
        el.setAttribute('aria-invalid', ok ? 'false' : 'true');
        if (!ok && !first) first = el;
      });
      if (first) { e.preventDefault(); first.focus(); first.reportValidity(); }
    });
  });
  const focus = $('[data-focus]');
  if (focus) focus.focus();
}

/* ---------- AI assistant: loaded on first open ---------- */
function initAssistant() {
  const open = $('[data-ai-open]');
  if (!open) return;
  open.addEventListener('click', async () => {
    const mod = await import('./assistant.js');
    mod.openAssistant();
  }, { once: false });
}

document.addEventListener('DOMContentLoaded', () => {
  initHeader();
  initConsent();
  initTracking();
  initGallery();
  initDurability();
  initMap();
  initSimpleForms();
  initWizard();
  initAssistant();
});
