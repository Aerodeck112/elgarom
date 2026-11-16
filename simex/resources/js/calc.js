// Calculator: renders a workbook sheet as a grid, sends the inputs to the server and shows the server's results.
// Every figure on screen comes from the backend engine; nothing is computed in the browser.
import { postJson, toast } from './app.js';

const box = document.querySelector('[data-calc]');
if (box) {
    const cfg = JSON.parse(document.getElementById('calc-config').textContent);
    const state = { mode: 'auto', sheet: cfg.sheet, inputs: cfg.inputs || {}, params: {}, variant: cfg.variant, view: null, timer: null, seq: 0, dirty: false };
    const grid = box.querySelector('[data-grid]');
    const tabs = box.querySelector('[data-sheets]');
    const results = box.querySelector('[data-results]');
    const modeBtns = [...box.querySelectorAll('[data-mode]')];
    modeBtns.forEach((btn) => btn.addEventListener('click', () => { state.mode = btn.dataset.mode; if (state.lastView) render(state.lastView); }));
    const el = (tag, cls, text) => { const n = document.createElement(tag); if (cls) n.className = cls; if (text !== undefined) n.textContent = text; return n; };
    const ref = (sheet, col, row) => `${sheet}!${col}${row}`;
    const TONE = { ok: 'tag--ok', warn: 'tag--warn', block: 'tag--err' };
    const STATE_LABEL = { ok: 'Calculat', warn: 'Calculat – cu avertizări', block: 'Incomplet / blocat' };

    function renderGrid(view) {
        state.view = view;
        const table = el('table', 'sheet');
        const head = el('tr');
        head.appendChild(el('th', 'rn', ''));
        view.cols.forEach((c) => head.appendChild(el('th', '', c)));
        const thead = el('thead'); thead.appendChild(head); table.appendChild(thead);
        const body = el('tbody');
        const lastCol = view.cols.length;
        view.rows.forEach((row) => {
            const tr = el('tr');
            tr.appendChild(el('td', 'rn', String(row.r)));
            let col = 1;
            row.cells.forEach((cell, idx) => {
                while (col < cell.c) { tr.appendChild(el('td')); col++; }
                const td = el('td');
                const letter = view.cols[cell.c - 1];
                td.dataset.a = letter + row.r;
                if (cell.i && view.editable && !cfg.readonly) {
                    td.className = 'in';
                    td.appendChild(inputFor(view, cell, ref(view.name, letter, row.r)));
                } else {
                    td.textContent = cell.d ?? '';
                    const cls = [];
                    if (cell.b) cls.push('b');
                    if (cell.n) cls.push('n');
                    if (cell.f) cls.push('f');
                    if (cell.e) cls.push('e');
                    // A text followed by empty cells may use their width, as it does in Excel.
                    const next = row.cells[idx + 1];
                    if (!cell.n && (cell.d || '').length > 40) {
                        const span = (next ? next.c : lastCol + 1) - cell.c;
                        if (span > 1) td.colSpan = span;
                        cls.push('long');
                        col += span - 1;
                    }
                    td.className = cls.join(' ');
                }
                tr.appendChild(td);
                col++;
            });
            body.appendChild(tr);
        });
        table.appendChild(body);
        grid.replaceChildren(table);
    }

    function inputFor(view, cell, key) {
        let input;
        if (cell.t === 'l' && view.lists[cell.o]) {
            input = el('select');
            input.add(new Option('', ''));
            const current = cell.v === null || cell.v === undefined ? '' : String(cell.v);
            let found = current === '';
            view.lists[cell.o].forEach((o) => { const opt = new Option(o, o, false, o === current); if (o === current) found = true; input.add(opt); });
            if (!found) input.add(new Option(current + ' (în afara listei)', current, true, true));
        } else {
            input = el('input');
            input.type = cell.t === 'd' ? 'date' : 'text';
            if (cell.t === 'n' || typeof cell.v === 'number') { input.inputMode = 'decimal'; input.className = 'n'; }
            input.value = cell.v === null || cell.v === undefined ? '' : (typeof cell.v === 'number' ? String(cell.v).replace('.', ',') : String(cell.v));
            input.autocomplete = 'off';
            input.spellcheck = false;
        }
        input.dataset.ref = key;
        input.setAttribute('aria-label', key);
        return input;
    }

    function patch(view) {
        // Same sheet: update computed values in place so focus and caret are kept (works in both views).
        const byAddr = new Map();
        view.rows.forEach((row) => row.cells.forEach((c) => byAddr.set(view.cols[c.c - 1] + row.r, c)));
        grid.querySelectorAll('[data-a]').forEach((node) => {
            if (node.classList.contains('in') || node.querySelector('[data-ref]')) return;
            const cell = byAddr.get(node.dataset.a);
            const text = cell ? (cell.d ?? '') : '';
            if (node.textContent !== text) {
                node.textContent = text;
                node.classList.toggle('e', Boolean(cell && cell.e));
                node.classList.remove('changed'); void node.offsetWidth; node.classList.add('changed');
            }
        });
        state.view = view;
    }

    /* ---------- guided form: the same cells, laid out as labelled fields and tables ---------- */
    const txt = (c) => (c && !c.i && !c.f && !c.n ? String(c.d ?? '').trim() : '');
    const MARKER = /^(input|output|calcul|automat|manual)$/i;
    const isHeaderRow = (x) => x.texts.length >= 3 && !x.inputs.length && x.computed.length === 0;
    const isTitle = (t) => /\d/.test(t) || (t.replace(/[^A-Za-zĂÂÎȘȚăâîșț]/g, '').length > 3 && t === t.toUpperCase());
    const val = (c) => (c.v === null || c.v === undefined ? '' : String(c.v));

    function analyse(view) {
        const rows = view.rows.map((row) => ({
            r: row.r, row,
            inputs: row.cells.filter((c) => c.i),
            texts: row.cells.filter((c) => txt(c) !== ''),
            computed: row.cells.filter((c) => !c.i && c.f),
        }));
        const byR = new Map(rows.map((x) => [x.r, x]));
        const consumed = new Set();
        const blocks = [];
        let header = {};
        for (let i = 0; i < rows.length; i++) {
            const x = rows[i];
            if (consumed.has(x.r)) continue;
            if (x.inputs.length >= 3) {
                let j = i + 1;
                while (j < rows.length && rows[j].inputs.length >= 3 && rows[j].r === rows[j - 1].r + 1) j++;
                const part = rows.slice(i, j);
                const colSet = new Set();
                part.forEach((p) => p.row.cells.forEach((c) => { if (c.i || c.f) colSet.add(c.c); }));
                // Header: nearest row above that names most of these columns.
                const head = {};
                for (let r = part[0].r - 1, steps = 0; r >= 1 && steps < 4; r--, steps++) {
                    const hr = byR.get(r);
                    if (!hr) continue;
                    const named = hr.row.cells.filter((c) => colSet.has(c.c) && String(c.d ?? '').trim() !== '');
                    if (named.length >= Math.min(3, colSet.size / 2)) {
                        hr.row.cells.forEach((c) => { if (!head[c.c] && String(c.d ?? '').trim()) head[c.c] = String(c.d).trim(); });
                        consumed.add(r);
                        if (Object.keys(head).length >= colSet.size * 0.6) break;
                    }
                }
                const cols = [...colSet].sort((a, b) => a - b).filter((c) => !/^flag\b/i.test(head[c] || ''));
                blocks.forEach((b, k) => { if (b.type !== 'table' && consumed.has(b.r)) blocks[k] = null; });
                blocks.push({ type: 'table', part, cols, head });
                i = j - 1;
            } else if (isHeaderRow(x)) {
                header = {};
                x.row.cells.forEach((c) => { if (txt(c)) header[c.c] = txt(c); });
                const first = txt(x.texts[0]);
                if (isTitle(first)) blocks.push({ type: 'heading', r: x.r, x });
            } else if (x.inputs.length) {
                blocks.push({ type: 'field', r: x.r, x, header });
            } else if (x.computed.length) {
                blocks.push({ type: 'result', r: x.r, x });
            } else if (x.texts.length) {
                blocks.push({ type: x.texts[0].b || txt(x.texts[0]).length < 48 ? 'heading' : 'note', r: x.r, x });
            }
        }
        return blocks.filter(Boolean);
    }

    function fieldBlock(view, x, header = {}) {
        const first = x.inputs[0].c;
        const label = x.texts.filter((c) => c.c < first).map(txt).join(' ') || x.texts[0] && txt(x.texts[0]) || view.cols[first - 1] + x.r;
        const right = x.texts.filter((c) => c.c > first).map(txt).filter((t) => !MARKER.test(t));
        const unit = right.find((t) => t.length <= 14) || '';
        const help = right.filter((t) => t !== unit).sort((a, b) => b.length - a.length)[0] || '';
        const wrap = el('div', 'ff');
        const lab = el('label', 'ff__label', label.replace(/\s*\*$/, ''));
        if (/\*\s*$/.test(label)) lab.appendChild(el('span', 'req', ' *'));
        wrap.appendChild(lab);
        const ctl = el('div', 'ff__ctl');
        x.inputs.forEach((c, k) => {
            const input = inputFor(view, c, ref(view.name, view.cols[c.c - 1], x.r));
            input.classList.add(c.t === 'l' ? 'select' : 'input');
            if (c.n || typeof c.v === 'number') input.classList.add('input--num');
            if (k === 0) lab.htmlFor = input.id = 'f-' + view.cols[c.c - 1] + x.r;
            if (!view.editable || cfg.readonly) input.disabled = true;
            ctl.appendChild(input);
        });
        if (unit) ctl.appendChild(el('span', 'ff__unit', unit));
        wrap.appendChild(ctl);
        const side = el('div', 'ff__help');
        x.computed.filter((c) => c.c > first && String(c.d ?? '').trim() !== '' && !/flag|lips/i.test(header[c.c] || '')).slice(0, 2).forEach((c) => {
            const used = el('span', 'ff__used');
            used.appendChild(el('span', 'muted', (header[c.c] || 'Valoare folosită').replace(/:$/, '') + ': '));
            const v = el('span', '', c.d); v.dataset.a = view.cols[c.c - 1] + x.r; used.appendChild(v);
            side.appendChild(used);
        });
        if (help) side.appendChild(el('span', '', help));
        wrap.appendChild(side);
        return wrap;
    }

    function resultBlock(view, x) {
        const wrap = el('div', 'fr');
        const first = x.computed[0].c;
        const label = x.texts.filter((c) => c.c < first).map(txt)[0] || x.texts.map(txt)[0] || '';
        if (!label && x.computed.every((c) => String(c.d ?? '') === '')) return null;
        wrap.appendChild(el('span', 'fr__label', label));
        const vals = el('span', 'fr__vals');
        x.computed.slice(0, 3).forEach((c) => { const v = el('span', c.n ? 'num' : '', String(c.d ?? '') || '—'); v.dataset.a = view.cols[c.c - 1] + x.r; if (c.e) v.classList.add('e'); vals.appendChild(v); });
        wrap.appendChild(vals);
        return wrap;
    }

    function tableBlock(view, b) {
        const box = el('div', 'ft');
        const modes = {};
        b.cols.forEach((c) => {
            const counts = new Map();
            b.part.forEach((p) => { const cell = p.row.cells.find((x) => x.c === c && x.i); if (cell) counts.set(val(cell), (counts.get(val(cell)) || 0) + 1); });
            modes[c] = [...counts.entries()].sort((a, z) => z[1] - a[1])[0]?.[0];
        });
        // A row is in use when one of its inputs differs from the value most rows of that column hold (the blank default).
        const used = (p) => p.row.cells.some((c) => c.i && val(c) !== '' && val(c) !== modes[c.c]);
        const table = el('table', 't ft__t');
        const hr = el('tr');
        hr.appendChild(el('th', 'rn', '#'));
        b.cols.forEach((c) => { const th = el('th', '', b.head[c] || view.cols[c - 1]); th.title = view.cols[c - 1]; hr.appendChild(th); });
        const thead = el('thead'); thead.appendChild(hr); table.appendChild(thead);
        const tbody = el('tbody');
        let shown = 0;
        const hidden = [];
        b.part.forEach((p, k) => {
            const tr = el('tr');
            tr.appendChild(el('td', 'rn', String(k + 1)));
            b.cols.forEach((c) => {
                const cell = p.row.cells.find((x) => x.c === c);
                const td = el('td');
                if (cell && cell.i) {
                    const input = inputFor(view, cell, ref(view.name, view.cols[c - 1], p.r));
                    input.classList.add(cell.t === 'l' ? 'select' : 'input');
                    if (!view.editable || cfg.readonly) input.disabled = true;
                    td.appendChild(input);
                } else {
                    td.textContent = cell ? (cell.d ?? '') : '';
                    td.dataset.a = view.cols[c - 1] + p.r;
                    td.className = 'ft__calc' + (cell && cell.n ? ' num' : '') + (cell && cell.e ? ' e' : '');
                }
                tr.appendChild(td);
            });
            if (used(p)) shown++; else { tr.hidden = true; hidden.push(tr); }
            tbody.appendChild(tr);
        });
        table.appendChild(tbody);
        const scroll = el('div', 'ft__scroll'); scroll.appendChild(table); box.appendChild(scroll);
        const foot = el('div', 'ft__foot');
        const info = el('span', 'small muted');
        const sync = () => { info.textContent = `${tbody.querySelectorAll('tr:not([hidden])').length} rânduri afișate din ${b.part.length}`; };
        if (hidden.length && !cfg.readonly && view.editable) {
            hidden.shift().hidden = false;
            const add = el('button', 'btn btn--line btn--sm', 'Adaugă rând');
            add.type = 'button';
            add.addEventListener('click', () => { const next = hidden.shift(); if (next) { next.hidden = false; next.querySelector('input, select')?.focus(); } if (!hidden.length) add.remove(); sync(); });
            foot.appendChild(add);
        }
        sync();
        foot.appendChild(info);
        box.appendChild(foot);
        return box;
    }

    function renderForm(view) {
        state.view = view;
        const root = el('div', 'fx');
        let group = null;
        const ensure = () => { if (!group) { group = el('section', 'fx__group'); root.appendChild(group); } return group; };
        analyse(view).forEach((b) => {
            if (b.type === 'heading') { group = el('section', 'fx__group'); group.appendChild(el('h3', 'fx__h', txt(b.x.texts[0]))); root.appendChild(group); }
            else if (b.type === 'note') { const t = txt(b.x.texts[0]); ensure().appendChild(el('p', 'fx__note', t.length > 320 ? t.slice(0, 317) + '…' : t)); }
            else if (b.type === 'field') ensure().appendChild(fieldBlock(view, b.x, b.header));
            else if (b.type === 'result') { const node = resultBlock(view, b.x); if (node) ensure().appendChild(node); }
            else if (b.type === 'table') { ensure().appendChild(tableBlock(view, b)); }
        });
        grid.classList.add('is-form');
        grid.replaceChildren(root);
    }

    function render(view) {
        const form = state.mode === 'form' || (state.mode === 'auto' && ['input', 'reference'].includes(view.role));
        modeBtns.forEach((btn) => btn.classList.toggle('is-active', (btn.dataset.mode === 'form') === form));
        if (form) renderForm(view); else { grid.classList.remove('is-form'); renderGrid(view); }
    }

    function renderResults(data) {
        results.replaceChildren();
        Object.values(data.summary).forEach((v) => {
            const card = el('section', 'result');
            const head = el('div', 'result__head');
            head.appendChild(el('span', '', v.label));
            head.appendChild(el('span', 'tag ' + (TONE[v.state] || ''), STATE_LABEL[v.state] || v.state));
            card.appendChild(head);
            const main = el('div', 'result__main');
            if (v.hours !== null) {
                const big = el('div', 'result__big', v.hours_display + ' ');
                big.appendChild(el('small', '', 'ore'));
                main.appendChild(big);
                if (v.machine_cost_display) main.appendChild(el('div', 'small', `Cost utilaj: ${v.machine_cost_display}`));
                else if (data.machine) main.appendChild(el('div', 'pending', data.machine.rate_note));
            } else if (v.total !== null) {
                const big = el('div', 'result__big', v.total_display + ' ');
                big.appendChild(el('small', '', v.currency || ''));
                main.appendChild(big);
            } else {
                main.appendChild(el('div', 'muted', 'Fără rezultat valabil încă.'));
            }
            main.appendChild(el('div', 'small muted', v.status));
            card.appendChild(main);
            if (v.outputs.length) {
                const dl = el('dl');
                v.outputs.forEach((o) => { const d = el('div'); d.appendChild(el('dt', '', o.label)); d.appendChild(el('dd', '', o.display || '—')); dl.appendChild(d); });
                card.appendChild(dl);
            }
            if (v.warnings.length) {
                const ul = el('ul');
                v.warnings.forEach((w) => ul.appendChild(el('li', '', w)));
                card.appendChild(ul);
            }
            if (Object.keys(data.summary).length > 1) {
                const pick = el('label', 'check');
                pick.style.padding = '10px 16px';
                pick.style.borderTop = '1px solid var(--line)';
                const radio = el('input'); radio.type = 'radio'; radio.name = 'variant-pick'; radio.value = v.key; radio.checked = state.variant === v.key;
                radio.addEventListener('change', () => { state.variant = v.key; state.dirty = true; });
                pick.appendChild(radio); pick.appendChild(document.createTextNode(' Folosește această estimare în ofertă'));
                card.appendChild(pick);
            }
            results.appendChild(card);
        });
        if (data.checks && data.checks.length) {
            const card = el('section', 'result');
            const bad = data.checks.filter((c) => !c.ok);
            card.appendChild(el('div', 'result__head', bad.length ? `Verificări: ${bad.length} de rezolvat` : 'Verificări înainte de ofertă'));
            const dl = el('dl');
            bad.forEach((c) => {
                const d = el('div');
                d.appendChild(el('dt', '', c.label));
                d.appendChild(el('dd', 'status--err', c.value + (c.where && c.where !== c.value ? `  → ${c.where}` : '')));
                dl.appendChild(d);
            });
            const okCount = data.checks.length - bad.length;
            if (okCount) { const d = el('div'); d.appendChild(el('dt', 'status--ok', `${okCount} controale în regulă`)); dl.appendChild(d); }
            card.appendChild(dl);
            results.appendChild(card);
        }
        if (data.rejected && data.rejected.length) toast('Unele celule nu pot fi modificate: ' + data.rejected.slice(0, 3).join(', '), true);
    }

    async function evaluate(switching = false) {
        const seq = ++state.seq;
        box.classList.add('busy');
        try {
            const data = await postJson(cfg.evaluateUrl, { sheet: state.sheet, inputs: state.inputs, params: state.params, calculation: cfg.calculation || null });
            if (seq !== state.seq) return;
            if (switching || !state.view || state.view.name !== data.view.name) render(data.view); else patch(data.view);
            state.lastView = data.view;
            renderResults(data);
        } catch (err) {
            toast(err.message, true);
        } finally {
            if (seq === state.seq) box.classList.remove('busy');
        }
    }

    grid.addEventListener('input', (e) => {
        const input = e.target.closest('[data-ref]');
        if (!input) return;
        const target = state.view.role === 'params' ? state.params : state.inputs;
        target[input.dataset.ref] = input.value;
        state.dirty = true;
        clearTimeout(state.timer);
        state.timer = setTimeout(() => evaluate(false), input.tagName === 'SELECT' || input.type === 'date' ? 0 : 350);
    });
    grid.addEventListener('change', (e) => { if (e.target.closest('select[data-ref]')) { clearTimeout(state.timer); evaluate(false); } });

    tabs?.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-sheet]');
        if (!btn) return;
        state.sheet = btn.dataset.sheet;
        tabs.querySelectorAll('[data-sheet]').forEach((b) => b.classList.toggle('is-active', b === btn));
        clearTimeout(state.timer);
        evaluate(true);
    });

    const submitWith = (form, extra) => {
        Object.entries(extra).forEach(([name, value]) => {
            let field = form.querySelector(`input[name="${name}"]`);
            if (!field) { field = el('input'); field.type = 'hidden'; field.name = name; form.appendChild(field); }
            field.value = value;
        });
    };
    document.querySelectorAll('form[data-calc-save]').forEach((form) => form.addEventListener('submit', () => {
        submitWith(form, { inputs: JSON.stringify(state.inputs), variant: state.variant || '' });
        state.dirty = false;
    }));
    document.querySelectorAll('form[data-calc-params]').forEach((form) => form.addEventListener('submit', (e) => {
        if (!Object.keys(state.params).length) { e.preventDefault(); toast('Nu ați modificat niciun parametru.', true); return; }
        submitWith(form, { params: JSON.stringify(state.params) });
        state.dirty = false;
    }));
    window.addEventListener('beforeunload', (e) => { if (state.dirty) { e.preventDefault(); e.returnValue = ''; } });

    evaluate(true);
}
