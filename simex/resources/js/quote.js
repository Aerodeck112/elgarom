// Quote builder: add / remove lines and show indicative totals while typing.
// The authoritative totals are always recomputed on the server when the quote is saved.
const form = document.querySelector('[data-quote-form]');
if (form) {
    const body = form.querySelector('[data-lines]');
    const template = document.getElementById('line-template');
    const num = (v) => { const n = parseFloat(String(v ?? '').replace(/\s/g, '').replace(',', '.')); return Number.isFinite(n) ? n : 0; };
    const fmt = (n) => n.toLocaleString('ro-RO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const round2 = (n) => Math.round((n + Number.EPSILON) * 100) / 100;
    let index = body.querySelectorAll('[data-line]').length;

    function recompute() {
        let subtotal = 0;
        body.querySelectorAll('[data-line]').forEach((row, i) => {
            row.querySelector('[data-pos]').textContent = String(i + 1);
            const total = round2(num(row.querySelector('[data-f="quantity"]').value) * num(row.querySelector('[data-f="unit_price"]').value) * (1 - num(row.querySelector('[data-f="discount_percent"]')?.value) / 100));
            row.querySelector('[data-total]').textContent = fmt(total);
            if (!row.querySelector('[data-f="is_optional"]')?.checked) subtotal += total;
        });
        const discount = num(form.querySelector('[name="discount_percent"]')?.value);
        const net = round2(subtotal * (1 - discount / 100));
        const vat = round2(net * num(form.querySelector('[name="vat_rate"]')?.value) / 100);
        const set = (key, value) => { const el = form.querySelector(`[data-sum="${key}"]`); if (el) el.textContent = fmt(value); };
        set('subtotal', subtotal); set('net', net); set('vat', vat); set('gross', round2(net + vat));
    }

    form.querySelector('[data-add-line]')?.addEventListener('click', () => {
        const html = template.innerHTML.replaceAll('__I__', String(index++));
        body.insertAdjacentHTML('beforeend', html);
        body.lastElementChild.querySelector('input, select')?.focus();
        recompute();
    });
    body.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-remove-line]');
        if (btn) { btn.closest('[data-line]').remove(); recompute(); }
    });
    form.addEventListener('input', recompute);
    recompute();
}

// Generic repeatable rows (RFQ items): <tbody data-rows>, <template id="row-template">, [data-add-row], [data-remove-row].
const rowsForm = document.querySelector('[data-rows-form]');
if (rowsForm) {
    const rows = rowsForm.querySelector('[data-rows]');
    const rowTemplate = document.getElementById('row-template');
    let next = rows.querySelectorAll('[data-row]').length;
    const add = () => { rows.insertAdjacentHTML('beforeend', rowTemplate.innerHTML.replaceAll('__I__', String(next++))); };
    rowsForm.querySelector('[data-add-row]')?.addEventListener('click', () => { add(); rows.lastElementChild.querySelector('input')?.focus(); });
    rows.addEventListener('click', (e) => { const b = e.target.closest('[data-remove-row]'); if (b) b.closest('[data-row]').remove(); });
    if (next === 0) add();
}
