import { track } from './consent.js';

const DRAFT_KEY = 'elg_quote_draft';
const PREFILL_KEY = 'elg_ai_prefill';
const DRAFT_FIELDS = ['name', 'company', 'email', 'phone', 'city', 'part_type', 'material', 'process', 'description', 'quantity', 'weight', 'dimensions', 'deadline', 'deadline_note'];

export function initWizard() {
  const form = document.querySelector('[data-wizard]');
  if (!form) return;
  const steps = [...form.querySelectorAll('[data-step]')];
  const progress = [...form.querySelectorAll('[data-wizard-progress] li')];
  const nav = form.querySelector('[data-wizard-nav]');
  const prev = form.querySelector('[data-prev]');
  const next = form.querySelector('[data-next]');
  const fileInput = form.querySelector('[data-files]');
  const fileList = form.querySelector('[data-file-list]');
  const drop = form.querySelector('[data-drop]');
  const maxFiles = Number(form.dataset.maxFiles);
  const maxBytes = Number(form.dataset.maxBytes);
  const exts = form.dataset.exts.split(',');
  let current = Math.min(Math.max(Number(form.dataset.startStep) || 1, 1), steps.length);

  nav.hidden = false;

  // Restore draft (server-side old input wins) and AI prefill (only after the user accepted it in the assistant).
  const restore = (data, onlyEmpty = true) => {
    Object.entries(data || {}).forEach(([k, v]) => {
      if (!DRAFT_FIELDS.includes(k) || v === null || v === undefined) return;
      const els = form.querySelectorAll(`[name="${k}"]`);
      els.forEach((el) => {
        if (el.type === 'radio') { if (el.value === v && (!onlyEmpty || !form.querySelector(`[name="${k}"]:checked`) || k === 'part_type')) el.checked = true; return; }
        if (!onlyEmpty || !el.value) el.value = String(v).slice(0, Number(el.maxLength) > 0 ? Number(el.maxLength) : 6000);
      });
    });
  };
  try {
    const hasServerInput = form.querySelector('[name=name]').value || form.querySelector('[name=description]').value;
    if (!hasServerInput) restore(JSON.parse(sessionStorage.getItem(DRAFT_KEY) || 'null'));
    const pre = JSON.parse(sessionStorage.getItem(PREFILL_KEY) || 'null');
    if (pre) {
      restore(pre, true);
      form.querySelector('[data-source]').value = 'asistent_ai';
      sessionStorage.removeItem(PREFILL_KEY);
      const note = document.createElement('div');
      note.className = 'alert alert--ok';
      note.setAttribute('role', 'status');
      note.textContent = 'Am precompletat câmpurile cu informațiile din conversația cu asistentul AI. Verificați-le înainte de trimitere.';
      form.querySelector('[data-wizard-progress]').after(note);
    }
  } catch (_) { /* storage unavailable */ }

  const saveDraft = () => {
    const d = {};
    DRAFT_FIELDS.forEach((k) => {
      const el = form.querySelector(`[name="${k}"]:checked`) || form.querySelector(`[name="${k}"]:not([type=radio])`);
      if (el) d[k] = el.value;
    });
    try { sessionStorage.setItem(DRAFT_KEY, JSON.stringify(d)); } catch (_) { /* ignore */ }
  };
  form.addEventListener('input', saveDraft);

  const validateStep = (n) => {
    let first = null;
    steps[n - 1].querySelectorAll('input,select,textarea').forEach((el) => {
      if (!el.willValidate || el.type === 'file' || el.closest('.hp')) return;
      const ok = el.checkValidity();
      el.setAttribute('aria-invalid', ok ? 'false' : 'true');
      if (!ok && !first) first = el;
    });
    if (first) { first.focus(); first.reportValidity(); return false; }
    if (n === 4 && fileProblems().length) { fileInput.focus(); return false; }
    return true;
  };

  const show = (n, focus = true) => {
    current = n;
    steps.forEach((s, i) => s.classList.toggle('is-active', i === n - 1));
    progress.forEach((p, i) => {
      p.classList.toggle('is-done', i < n - 1);
      if (i === n - 1) p.setAttribute('aria-current', 'step'); else p.removeAttribute('aria-current');
    });
    prev.hidden = n === 1;
    next.hidden = n === steps.length;
    if (n === steps.length) buildReview();
    if (focus) {
      const h = steps[n - 1].querySelector('h2');
      h.setAttribute('tabindex', '-1');
      h.focus({ preventScroll: true });
      form.scrollIntoView({ behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'start' });
    }
    track('quote_step', { step: String(n) });
  };

  next.addEventListener('click', () => { if (validateStep(current)) show(current + 1); });
  prev.addEventListener('click', () => show(current - 1));

  /* files */
  const fileProblems = () => {
    const out = [];
    const files = [...fileInput.files];
    if (files.length > maxFiles) out.push(`Puteți atașa cel mult ${maxFiles} fișiere.`);
    files.forEach((f) => {
      const ext = f.name.split('.').pop().toLowerCase();
      if (!exts.includes(ext)) out.push(`„${f.name}”: tipul fișierului nu este acceptat.`);
      if (f.size > maxBytes) out.push(`„${f.name}” depășește ${Math.round(maxBytes / 1048576)} MB.`);
    });
    return out;
  };
  const renderFiles = () => {
    fileList.replaceChildren();
    [...fileInput.files].forEach((f, i) => {
      const li = document.createElement('li');
      const span = document.createElement('span');
      span.textContent = `${f.name} (${(f.size / 1048576).toFixed(1).replace('.', ',')} MB)`;
      const rm = document.createElement('button');
      rm.type = 'button';
      rm.textContent = 'Elimină';
      rm.setAttribute('aria-label', `Elimină ${f.name}`);
      rm.addEventListener('click', () => {
        const dt = new DataTransfer();
        [...fileInput.files].forEach((x, j) => { if (j !== i) dt.items.add(x); });
        fileInput.files = dt.files;
        renderFiles();
      });
      li.append(span, rm);
      fileList.appendChild(li);
    });
    fileProblems().forEach((p) => {
      const li = document.createElement('li');
      li.className = 'err';
      li.textContent = p;
      fileList.appendChild(li);
    });
  };
  fileInput.addEventListener('change', renderFiles);
  ['dragenter', 'dragover'].forEach((ev) => drop.addEventListener(ev, (e) => { e.preventDefault(); drop.classList.add('is-over'); }));
  ['dragleave', 'drop'].forEach((ev) => drop.addEventListener(ev, (e) => { e.preventDefault(); drop.classList.remove('is-over'); }));
  drop.addEventListener('drop', (e) => {
    const dt = new DataTransfer();
    [...fileInput.files, ...e.dataTransfer.files].forEach((f) => dt.items.add(f));
    fileInput.files = dt.files;
    renderFiles();
  });
  drop.addEventListener('keydown', (e) => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); fileInput.click(); } });
  drop.setAttribute('tabindex', '0');
  drop.setAttribute('role', 'button');

  /* review */
  const buildReview = () => {
    const dl = form.querySelector('[data-review]');
    dl.replaceChildren();
    const label = (name) => {
      const el = form.querySelector(`[name="${name}"]`);
      return form.querySelector(`label[for="${el?.id}"]`)?.childNodes[0]?.textContent.trim() || name;
    };
    const val = (name) => {
      const r = form.querySelector(`[name="${name}"]:checked`);
      if (r) return r.closest('label').querySelector('span').textContent;
      const el = form.querySelector(`[name="${name}"]`);
      if (el?.tagName === 'SELECT') return el.value ? el.options[el.selectedIndex].text : '';
      return el?.value || '';
    };
    const rows = [['name'], ['company'], ['email'], ['phone'], ['city'], ['part_type', 'Tipul pieselor'], ['material'], ['process'], ['description', 'Descriere'], ['quantity'], ['weight'], ['dimensions'], ['deadline'], ['deadline_note']];
    rows.forEach(([n, l]) => {
      const v = val(n);
      if (!v) return;
      const d = document.createElement('div');
      const dt = document.createElement('dt');
      const dd = document.createElement('dd');
      dt.textContent = l || label(n);
      dd.textContent = v;
      d.append(dt, dd);
      dl.appendChild(d);
    });
    const files = [...fileInput.files];
    if (files.length) {
      const d = document.createElement('div');
      d.innerHTML = '<dt>Fișiere</dt><dd></dd>';
      d.querySelector('dd').textContent = files.map((f) => f.name).join(', ');
      dl.appendChild(d);
    }
  };

  /* submit */
  form.addEventListener('submit', (e) => {
    for (let n = 1; n <= steps.length; n++) {
      steps[n - 1].classList.add('is-active');
      if (!validateStep(n)) { e.preventDefault(); show(n, false); return; }
      if (n !== current) steps[n - 1].classList.remove('is-active');
    }
    const btn = form.querySelector('[data-submit]');
    btn.disabled = true;
    btn.textContent = 'Se trimite…';
  });

  show(current, Boolean(form.querySelector('[data-focus]')) === false && current !== 1);
}
