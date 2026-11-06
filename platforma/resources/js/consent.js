/*
 * Cookie consent + Google tags.
 *
 * - Nothing optional loads before a choice in "basic" mode (default).
 * - In "advanced" mode tags load with Consent Mode v2 defaults set to "denied" (cookieless pings).
 * - Exactly one delivery path: GTM container OR direct gtag.js – never both (no double counting).
 * - Events carry only whitelisted, non-personal parameters.
 */
const CONFIG = (() => {
  try { return JSON.parse(document.getElementById('elg-config')?.textContent || '{}'); } catch (_) { return {}; }
})();
const COOKIE = 'elg_consent';
const ALLOWED_PARAMS = ['form_type', 'link_label', 'step', 'file_count', 'source'];
let loaded = false;

window.dataLayer = window.dataLayer || [];
function gtag() { window.dataLayer.push(arguments); }

function readConsent() {
  const m = document.cookie.match(/(?:^|; )elg_consent=([^;]+)/);
  if (!m) return null;
  try {
    const v = JSON.parse(decodeURIComponent(m[1]));
    return v.v === CONFIG.consentVersion ? v : null; // a new policy version asks again
  } catch (_) { return null; }
}

function writeConsent(analytics, marketing) {
  const v = { v: CONFIG.consentVersion, a: analytics ? 1 : 0, m: marketing ? 1 : 0, t: Math.floor(Date.now() / 1000) };
  const secure = location.protocol === 'https:' ? '; Secure' : '';
  document.cookie = `${COOKIE}=${encodeURIComponent(JSON.stringify(v))}; Max-Age=${60 * 60 * 24 * 180}; Path=/; SameSite=Lax${secure}`;
  return v;
}

function consentState(c) {
  const a = c && c.a ? 'granted' : 'denied';
  const m = c && c.m ? 'granted' : 'denied';
  return { analytics_storage: a, ad_storage: m, ad_user_data: m, ad_personalization: m };
}

function deleteGoogleCookies() {
  document.cookie.split('; ').forEach((c) => {
    const name = c.split('=')[0];
    if (/^(_ga|_gid|_gat|_gcl_)/.test(name)) {
      const host = location.hostname;
      [host, '.' + host, '.' + host.split('.').slice(-2).join('.')].forEach((d) => {
        document.cookie = `${name}=; Max-Age=0; Path=/; Domain=${d}`;
      });
      document.cookie = `${name}=; Max-Age=0; Path=/`;
    }
  });
}

function inject(src) {
  const s = document.createElement('script');
  s.async = true;
  s.src = src;
  const nonce = document.querySelector('script[nonce]')?.nonce;
  if (nonce) s.nonce = nonce;
  document.head.appendChild(s);
}

function loadTags(c) {
  if (loaded) return;
  if (CONFIG.mode === 'gtm' && CONFIG.gtm) {
    window.dataLayer.push({ 'gtm.start': Date.now(), event: 'gtm.js' });
    inject('https://www.googletagmanager.com/gtm.js?id=' + encodeURIComponent(CONFIG.gtm));
    loaded = true;
  } else if (CONFIG.ga4 || CONFIG.ads) {
    inject('https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(CONFIG.ga4 || CONFIG.ads));
    gtag('js', new Date());
    if (CONFIG.ga4) gtag('config', CONFIG.ga4, { anonymize_ip: true });
    if (CONFIG.ads && c && c.m) gtag('config', CONFIG.ads);
    loaded = true;
  }
}

function apply(c, isUpdate) {
  gtag('consent', isUpdate ? 'update' : 'default', { ...consentState(c), wait_for_update: 500 });
  if (isUpdate) window.dataLayer.push({ event: 'consent_update', consent_analytics: c && c.a ? 'granted' : 'denied', consent_marketing: c && c.m ? 'granted' : 'denied' });
  const any = c && (c.a || c.m);
  if (CONFIG.consentMode === 'advanced' || any) loadTags(c);
  if (c && !c.a) deleteGoogleCookies();
}

export function track(name, params = {}) {
  if (!CONFIG.needsConsent) return;
  const clean = {};
  ALLOWED_PARAMS.forEach((k) => { if (params[k] !== undefined && params[k] !== '') clean[k] = String(params[k]).slice(0, 60); });
  if (CONFIG.mode === 'gtm') {
    window.dataLayer.push({ event: name, ...clean });
    return;
  }
  if (!loaded) return; // basic mode, no consent: nothing is sent
  gtag('event', name, clean);
  if (name === 'generate_lead' && CONFIG.ads && CONFIG.adsLeadLabel) {
    const c = readConsent();
    if (c && c.m) gtag('event', 'conversion', { send_to: CONFIG.ads + '/' + CONFIG.adsLeadLabel });
  }
}

export function initConsent() {
  if (!CONFIG.needsConsent) return;
  const banner = document.querySelector('[data-consent]');
  const existing = readConsent();
  apply(existing, false);

  const show = (custom) => {
    if (!banner) return;
    banner.hidden = false;
    const opts = banner.querySelector('[data-consent-opts]');
    opts.hidden = !custom;
    banner.querySelector('[data-consent-save]').hidden = !custom;
    banner.querySelector('[data-consent-customize]').hidden = !!custom;
    const c = readConsent();
    const a = banner.querySelector('input[name=analytics]');
    const m = banner.querySelector('input[name=marketing]');
    if (a) a.checked = !!(c && c.a);
    if (m) m.checked = !!(c && c.m);
    (custom ? opts.querySelector('input:not([disabled])') : banner.querySelector('[data-consent-accept]'))?.focus({ preventScroll: true });
  };
  const choose = (a, m) => {
    const prev = readConsent();
    const c = writeConsent(a, m);
    banner.hidden = true;
    // Revoking consent after tags loaded: reload so no tag keeps running in memory.
    if (loaded && prev && ((prev.a && !a) || (prev.m && !m))) { deleteGoogleCookies(); location.reload(); return; }
    apply(c, true);
  };

  if (!existing) show(false);
  banner?.querySelector('[data-consent-accept]').addEventListener('click', () => choose(true, true));
  banner?.querySelector('[data-consent-reject]').addEventListener('click', () => choose(false, false));
  banner?.querySelector('[data-consent-customize]').addEventListener('click', () => show(true));
  banner?.querySelector('[data-consent-form]').addEventListener('submit', (e) => {
    e.preventDefault();
    choose(!!banner.querySelector('input[name=analytics]')?.checked, !!banner.querySelector('input[name=marketing]')?.checked);
  });
  document.querySelectorAll('[data-consent-open]').forEach((b) => b.addEventListener('click', () => show(true)));
}
