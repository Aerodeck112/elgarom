// Calculator: renders a workbook sheet as a grid, sends the inputs to the server and shows the server's results.
// Every figure on screen comes from the backend engine; nothing is computed in the browser.
import { postJson, toast } from './app.js';

const box = document.querySelector('[data-calc]');
if (box) {
    const cfg = JSON.parse(document.getElementById('calc-config').textContent);
    const state = { sheet: cfg.sheet, inputs: cfg.inputs || {}, params: {}, variant: cfg.variant, view: null, timer: null, seq: 0, dirty: false };
    const grid = box.querySelector('[data-grid]');
    const tabs = box.querySelector('[data-sheets]');
    const results = box.querySelector('[data-results]');
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
        // Same sheet: update texts in place so focus and caret are kept.
        const byAddr = new Map();
        view.rows.forEach((row) => row.cells.forEach((c) => byAddr.set(view.cols[c.c - 1] + row.r, c)));
        grid.querySelectorAll('td[data-a]').forEach((td) => {
            const cell = byAddr.get(td.dataset.a);
            if (td.classList.contains('in')) return;
            const text = cell ? (cell.d ?? '') : '';
            if (td.textContent !== text) {
                td.textContent = text;
                td.classList.toggle('e', Boolean(cell && cell.e));
                td.classList.remove('changed'); void td.offsetWidth; td.classList.add('changed');
            }
        });
        state.view = view;
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
            card.appendChild(el('div', 'result__head', 'Verificări înainte de ofertă'));
            const dl = el('dl');
            data.checks.forEach((c) => {
                const d = el('div');
                d.appendChild(el('dt', '', c.label));
                const dd = el('dd', c.ok ? 'status--ok' : 'status--err', c.value + (c.ok ? '' : `  → ${c.where}`));
                d.appendChild(dd); dl.appendChild(d);
            });
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
            if (switching || !state.view || state.view.name !== data.view.name) renderGrid(data.view); else patch(data.view);
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
