const $ = (s, r = document) => r.querySelector(s);
const $$ = (s, r = document) => [...r.querySelectorAll(s)];

/* sidebar on small screens */
const side = $('[data-side]');
const toggle = $('[data-side-toggle]');
toggle?.addEventListener('click', () => {
  const open = !side.classList.contains('is-open');
  side.classList.toggle('is-open', open);
  toggle.setAttribute('aria-expanded', String(open));
});

/* confirmation for destructive forms */
document.addEventListener('submit', (e) => {
  const f = e.target.closest('form[data-confirm]');
  if (f && !window.confirm(f.dataset.confirm)) e.preventDefault();
}, true);

/* slug from title */
$$('[data-slug-from]').forEach((slug) => {
  const src = document.getElementById(slug.dataset.slugFrom);
  if (!src) return;
  let touched = slug.value !== '';
  slug.addEventListener('input', () => { touched = true; });
  src.addEventListener('input', () => {
    if (touched) return;
    slug.value = src.value.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '')
      .replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 120);
  });
});

/* repeater */
function renumber(rep) {
  const name = rep.dataset.name;
  $$(':scope > [data-row]', rep).forEach((row, i) => {
    $$('[name]', row).forEach((el) => {
      el.name = el.name.replace(new RegExp('^' + name.replace(/[[\]]/g, '\\$&') + '\\[[^\\]]+\\]'), `${name}[${i}]`);
    });
  });
}
document.addEventListener('click', (e) => {
  const rep = e.target.closest('[data-repeater]');
  if (!rep) return;
  if (e.target.closest('[data-repeater-add]')) {
    const tpl = $(':scope > template', rep);
    const node = tpl.content.firstElementChild.cloneNode(true);
    tpl.before(node);
    renumber(rep);
    node.querySelector('input,textarea,select')?.focus();
    return;
  }
  const row = e.target.closest('[data-row]');
  if (!row || row.parentElement !== rep) return;
  if (e.target.closest('[data-remove]')) { row.remove(); renumber(rep); }
  if (e.target.closest('[data-up]') && row.previousElementSibling?.matches('[data-row]')) { rep.insertBefore(row, row.previousElementSibling); renumber(rep); }
  if (e.target.closest('[data-down]') && row.nextElementSibling?.matches('[data-row]')) { rep.insertBefore(row.nextElementSibling, row); renumber(rep); }
});

/* media picker */
const picker = $('[data-picker]');
let pickCallback = null;
async function loadPicker(q = '') {
  const grid = $('[data-picker-grid]', picker);
  const res = await fetch(grid.dataset.src + '?q=' + encodeURIComponent(q), { headers: { Accept: 'application/json' } });
  const items = await res.json();
  grid.replaceChildren();
  if (!items.length) { grid.textContent = 'Nicio imagine găsită.'; return; }
  items.forEach((m) => {
    const card = document.createElement('div');
    card.className = 'media-card';
    const b = document.createElement('button');
    b.type = 'button';
    const img = document.createElement('img');
    img.src = m.thumb; img.alt = '';
    const cap = document.createElement('div');
    cap.textContent = m.alt || m.name;
    b.append(img, cap);
    b.setAttribute('aria-label', 'Alege: ' + (m.alt || m.name));
    b.addEventListener('click', () => { pickCallback?.(m); if (!picker.dataset.multi) picker.close(); });
    card.appendChild(b);
    grid.appendChild(card);
  });
}
if (picker) {
  $('[data-picker-close]', picker).addEventListener('click', () => picker.close());
  let t;
  $('[data-picker-q]', picker).addEventListener('input', (e) => { clearTimeout(t); t = setTimeout(() => loadPicker(e.target.value), 250); });
}
document.addEventListener('click', (e) => {
  const open = e.target.closest('[data-media-open]');
  if (open && picker) {
    const single = open.closest('[data-media-pick]');
    const multi = open.closest('[data-media-multi]');
    picker.dataset.multi = multi ? '1' : '';
    if (single) {
      pickCallback = (m) => {
        $('input[type=hidden]', single).value = m.id;
        const img = $('img', single); img.src = m.thumb; img.hidden = false;
        $('[data-media-clear]', single).hidden = false;
      };
    } else if (multi) {
      pickCallback = (m) => {
        const list = $('[data-list]', multi);
        if (list.querySelector(`[data-id="${m.id}"]`)) return;
        const fig = document.createElement('figure');
        fig.dataset.id = m.id;
        fig.innerHTML = '<img alt=""><input type="hidden"><div class="tools"><button type="button" data-left aria-label="Mută la stânga">‹</button><button type="button" data-remove aria-label="Elimină">×</button><button type="button" data-right aria-label="Mută la dreapta">›</button></div>';
        fig.querySelector('img').src = m.thumb;
        const inp = fig.querySelector('input');
        inp.name = multi.dataset.name; inp.value = m.id;
        list.appendChild(fig);
      };
    }
    picker.showModal();
    loadPicker();
    return;
  }
  const clear = e.target.closest('[data-media-clear]');
  if (clear) {
    const single = clear.closest('[data-media-pick]');
    $('input[type=hidden]', single).value = '';
    $('img', single).hidden = true;
    clear.hidden = true;
    return;
  }
  const fig = e.target.closest('[data-media-multi] figure');
  if (fig) {
    if (e.target.closest('[data-remove]')) fig.remove();
    if (e.target.closest('[data-left]') && fig.previousElementSibling) fig.parentElement.insertBefore(fig, fig.previousElementSibling);
    if (e.target.closest('[data-right]') && fig.nextElementSibling) fig.parentElement.insertBefore(fig.nextElementSibling, fig);
  }
});

/* copy buttons */
document.addEventListener('click', async (e) => {
  const b = e.target.closest('[data-copy]');
  if (!b) return;
  try { await navigator.clipboard.writeText(b.dataset.copy); b.textContent = 'Copiat'; } catch (_) { /* ignore */ }
});
