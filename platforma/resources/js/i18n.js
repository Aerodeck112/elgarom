const D = (() => { try { return JSON.parse(document.getElementById('elg-i18n')?.textContent || '{}'); } catch (_) { return {}; } })();
export const locale = D.locale || document.documentElement.lang || 'ro';
export const urls = D.urls || {};
export function t(key, params = {}) {
  let s = (D.t && D.t[key]) || key;
  Object.entries(params).forEach(([k, v]) => { s = s.split(':' + k).join(String(v)); });
  return s;
}
