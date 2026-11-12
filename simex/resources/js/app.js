// GRUP SIMEX Industrial CRM – shared behaviour of the internal platform. No inline scripts: the CSP forbids them.
const $ = (s, r = document) => r.querySelector(s);
const $$ = (s, r = document) => [...r.querySelectorAll(s)];

export const csrf = () => $('meta[name="csrf-token"]')?.content ?? '';

export async function postJson(url, body) {
    const res = await fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify(body),
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
        const message = data.message || (res.status === 419 ? 'Sesiunea a expirat. Reîncărcați pagina.' : res.status === 403 ? 'Nu aveți permisiunea necesară.' : 'Cererea nu a putut fi procesată.');
        throw Object.assign(new Error(message), { status: res.status, data });
    }
    return data;
}

export function toast(message, isError = false) {
    const el = document.createElement('div');
    el.className = 'toast' + (isError ? ' toast--err' : '');
    el.setAttribute('role', isError ? 'alert' : 'status');
    el.textContent = message;
    document.body.appendChild(el);
    setTimeout(() => el.remove(), isError ? 6000 : 2600);
}

/* sidebar on small screens */
const side = $('[data-side]');
const toggle = $('[data-side-toggle]');
toggle?.addEventListener('click', () => {
    const open = !side.classList.contains('is-open');
    side.classList.toggle('is-open', open);
    toggle.setAttribute('aria-expanded', String(open));
});
document.addEventListener('click', (e) => {
    if (side?.classList.contains('is-open') && !e.target.closest('[data-side]') && !e.target.closest('[data-side-toggle]')) {
        side.classList.remove('is-open');
        toggle?.setAttribute('aria-expanded', 'false');
    }
});

/* theme (light / dark), remembered on this device */
const root = document.documentElement;
$('[data-theme-toggle]')?.addEventListener('click', () => {
    const next = root.dataset.theme === 'dark' ? 'light' : 'dark';
    root.dataset.theme = next;
    try { localStorage.setItem('simex.theme', next); } catch { /* private mode */ }
});

/* confirmation for destructive forms */
document.addEventListener('submit', (e) => {
    const f = e.target.closest('form[data-confirm]');
    if (f && !window.confirm(f.dataset.confirm)) e.preventDefault();
}, true);

/* copy to clipboard */
document.addEventListener('click', async (e) => {
    const btn = e.target.closest('[data-copy]');
    if (!btn) return;
    const text = btn.dataset.copy || $(btn.dataset.copyFrom)?.textContent || '';
    try { await navigator.clipboard.writeText(text.trim()); toast('Copiat.'); } catch { toast('Copierea nu a reușit.', true); }
});

/* dialogs: <button data-open="#id">, <button data-close> */
document.addEventListener('click', (e) => {
    const open = e.target.closest('[data-open]');
    if (open) { $(open.dataset.open)?.showModal(); return; }
    const close = e.target.closest('[data-close]');
    if (close) close.closest('dialog')?.close();
});

/* tabs inside a page: <div data-tabs><button data-tab="x">…<section data-pane="x"> */
$$('[data-tabs]').forEach((box) => {
    const show = (name) => {
        $$('[data-tab]', box).forEach((b) => b.classList.toggle('is-active', b.dataset.tab === name));
        $$('[data-pane]', box).forEach((p) => { p.hidden = p.dataset.pane !== name; });
    };
    box.addEventListener('click', (e) => { const b = e.target.closest('[data-tab]'); if (b) { show(b.dataset.tab); history.replaceState(null, '', '#' + b.dataset.tab); } });
    const first = location.hash.slice(1);
    show($$('[data-tab]', box).some((b) => b.dataset.tab === first) ? first : $('[data-tab]', box)?.dataset.tab);
});

/* dependent select: contacts of the chosen company */
$$('select[data-contacts-for]').forEach((sel) => {
    const company = document.getElementById(sel.dataset.contactsFor);
    if (!company) return;
    const load = async () => {
        const keep = sel.value || sel.dataset.selected || '';
        sel.innerHTML = '<option value="">—</option>';
        if (!company.value) return;
        try {
            const res = await fetch(sel.dataset.url.replace('__ID__', encodeURIComponent(company.value)), { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            (await res.json()).forEach((c) => {
                const o = new Option(c.label, c.id, false, String(c.id) === String(keep));
                sel.add(o);
            });
        } catch { /* the list simply stays empty */ }
    };
    company.addEventListener('change', load);
    load();
});

/* submit a filter form when a select changes */
$$('form[data-auto] select').forEach((s) => s.addEventListener('change', () => s.form.requestSubmit()));
