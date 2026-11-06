import { track } from './consent.js';

const PREFILL_KEY = 'elg_ai_prefill';
const LABELS = {
  part_type: 'Tip piese', material: 'Material', process: 'Execuție', description: 'Descriere',
  quantity: 'Cantitate', weight: 'Greutate', dimensions: 'Dimensiuni', deadline_note: 'Termen',
};
let ready = false;
let panel; let log; let form; let input; let busy = false;

const csrf = () => document.querySelector('meta[name=csrf-token]')?.content || '';

function add(role, text) {
  const p = document.createElement('p');
  p.className = 'ai-msg ai-msg--' + role;
  p.textContent = text;
  log.appendChild(p);
  log.scrollTop = log.scrollHeight;
  return p;
}

function addLinks(links) {
  const p = document.createElement('p');
  p.className = 'ai-msg ai-msg--system';
  links.forEach((l, i) => {
    const a = document.createElement('a');
    a.href = l.url; a.textContent = l.label;
    if (i) p.append(', ');
    p.appendChild(a);
  });
  log.appendChild(p);
}

function addPrefill(data) {
  const box = document.createElement('div');
  box.className = 'ai-prefill';
  const h = document.createElement('strong');
  h.textContent = 'Propunere pentru formularul de ofertă';
  const dl = document.createElement('dl');
  Object.entries(data).forEach(([k, v]) => {
    const d = document.createElement('div');
    const dt = document.createElement('dt'); dt.textContent = LABELS[k] || k;
    const dd = document.createElement('dd'); dd.textContent = v;
    d.append(dt, dd); dl.appendChild(d);
  });
  const btn = document.createElement('button');
  btn.type = 'button'; btn.className = 'btn btn--sm';
  btn.textContent = 'Completează formularul cu aceste date';
  btn.addEventListener('click', () => {
    try { sessionStorage.setItem(PREFILL_KEY, JSON.stringify(data)); } catch (_) { /* ignore */ }
    track('ai_prefill_accepted', {});
    location.href = '/solicita-oferta#formular';
  });
  const note = document.createElement('p');
  note.className = 'small';
  note.textContent = 'Nimic nu se trimite până nu verificați și trimiteți formularul. Datele de contact le completați dumneavoastră.';
  box.append(h, dl, btn, note);
  log.appendChild(box);
  log.scrollTop = log.scrollHeight;
}

async function send(text) {
  if (busy) return;
  busy = true;
  add('user', text);
  const typing = add('system', 'Asistentul scrie…');
  typing.className = 'ai-typing';
  try {
    const res = await fetch('/asistent/mesaj', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf() },
      body: JSON.stringify({ message: text }),
      credentials: 'same-origin',
    });
    const data = await res.json().catch(() => ({}));
    typing.remove();
    if (!res.ok) {
      add('system', (data.error || data.message || 'Asistentul nu poate răspunde acum.') + ' ' + (data.fallback || ''));
      if (data.links) addLinks(data.links);
      return;
    }
    add('assistant', data.reply);
    if (data.prefill && Object.keys(data.prefill).length) addPrefill(data.prefill);
    if (data.remaining <= 3) add('system', `Mai puteți trimite ${data.remaining} mesaje în această conversație.`);
  } catch (_) {
    typing.remove();
    add('system', 'Conexiunea a eșuat. Puteți folosi formularul de ofertă.');
    addLinks([{ label: 'Formular de ofertă', url: '/solicita-oferta' }]);
  } finally {
    busy = false;
  }
}

async function init() {
  panel = document.querySelector('[data-ai-panel]');
  log = panel.querySelector('[data-ai-log]');
  form = panel.querySelector('[data-ai-form]');
  input = form.querySelector('textarea');
  form.addEventListener('submit', (e) => {
    e.preventDefault();
    const t = input.value.trim();
    if (!t) return;
    input.value = '';
    send(t);
  });
  input.addEventListener('keydown', (e) => { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); form.requestSubmit(); } });
  panel.querySelector('[data-ai-close]').addEventListener('click', close);
  panel.addEventListener('keydown', (e) => { if (e.key === 'Escape') close(); });
  panel.querySelector('[data-ai-delete]').addEventListener('click', async () => {
    await fetch('/asistent/conversatie', { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' }, credentials: 'same-origin' });
    [...log.children].slice(1).forEach((c) => c.remove());
    add('system', 'Conversația a fost ștearsă.');
  });
  try {
    const res = await fetch('/asistent/istoric', { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
    const data = await res.json();
    (data.messages || []).forEach((m) => add(m.role, m.text));
  } catch (_) { /* ignore */ }
  ready = true;
}

function close() {
  panel.hidden = true;
  document.querySelector('[data-ai-open]').hidden = false;
  document.querySelector('[data-ai-open]').focus();
}

export async function openAssistant() {
  if (!ready) await init();
  panel.hidden = false;
  document.querySelector('[data-ai-open]').hidden = true;
  input.focus();
  track('ai_chat_open', {});
}
