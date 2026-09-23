/* Lightweight Jalali dropdown date picker. Pure logic is exposed for tests; DOM wiring runs only in browser. */
window.VetraDatePicker = (() => {
  const J = window.VetraJalali;
  const months = ['فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند'];
  const weekdays = ['شنبه','یکشنبه','دوشنبه','سه‌شنبه','چهارشنبه','پنجشنبه','جمعه'];
  const pad = n => String(n).padStart(2, '0');
  function monthGrid(jy, jm) {
    const first = J.fromParts(jy, jm, 1);
    const cells = [];
    for (let i = 0; i < J.weekdayIndex(first); i++) cells.push(null);
    for (let d = 1; d <= J.monthLength(jy, jm); d++) cells.push({ jy: jy, jm: jm, jd: d, iso: J.fromParts(jy, jm, d) });
    while (cells.length % 7) cells.push(null);
    return cells;
  }
  function format(jy, jm, jd) { return jy + '/' + pad(jm) + '/' + pad(jd); }
  function attach(input) {
    if (!input || !document.body || input.dataset.vgPicker === '1') return;
    input.dataset.vgPicker = '1';
    if (!input.hasAttribute('data-jalali-typable')) input.readOnly = true;
    input.classList.add('vg-picker-input');
    let popup = null, view = null;
    const safeIso = text => { try { return J.parseOptional(text); } catch (e) { return null; } };
  const current = () => J.toParts(safeIso(input.value)) || (view || J.toParts(J.today()));
    function close() { if (popup) { popup.remove(); popup = null; document.removeEventListener('mousedown', outside, true); } }
    function host() { return input.closest('dialog[open]') || document.body; }
    function outside(e) { if (popup && !popup.contains(e.target) && e.target !== input) close(); }
    function draw() {
      popup.innerHTML =
        '<div class="vg-pk-head">' +
          '<button type="button" class="vg-pk-nav" data-nav="-1" aria-label="ماه قبل">›</button>' +
          '<select class="vg-pk-month" aria-label="ماه">' + months.map((m, i) => `<option value="${i + 1}"${i + 1 === view[1] ? ' selected' : ''}>${m}</option>`).join('') + '</select>' +
          '<select class="vg-pk-year" aria-label="سال">' + Array.from({ length: 40 }, (_, i) => { const y = view[0] - 20 + i; return `<option value="${y}"${y === view[0] ? ' selected' : ''}>${y}</option>`; }).join('') + '</select>' +
          '<button type="button" class="vg-pk-nav" data-nav="1" aria-label="ماه بعد">‹</button>' +
        '</div>' +
        '<div class="vg-pk-week">' + weekdays.map(w => `<span>${w}</span>`).join('') + '</div>' +
        '<div class="vg-pk-days">' + monthGrid(view[0], view[1]).map(cell => {
          if (!cell) return '<span class="vg-pk-blank"></span>';
          const iso = cell.iso, sel = safeIso(input.value) === iso;
          return `<button type="button" data-date="${iso}" class="${sel ? 'vg-pk-sel' : ''} ${iso === J.today() ? 'vg-pk-today' : ''}">${cell.jd}</button>`;
        }).join('') + '</div>';
      popup.querySelector('.vg-pk-month').addEventListener('change', e => { view[1] = Number(e.target.value); draw(); });
      popup.querySelector('.vg-pk-year').addEventListener('change', e => { view[0] = Number(e.target.value); draw(); });
      popup.querySelectorAll('.vg-pk-nav').forEach(b => b.addEventListener('click', () => { const n = Number(b.dataset.nav); let m = view[1] + n; if (m < 1) { m = 12; view[0]--; } if (m > 12) { m = 1; view[0]++; } view[1] = m; draw(); }));
      popup.querySelectorAll('[data-date]').forEach(b => b.addEventListener('click', () => {
        input.value = J.display(b.dataset.date);
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.dispatchEvent(new Event('change', { bubbles: true }));
        close();
      }));
    }
    function open() {
      view = current().slice();
      popup = document.createElement('div');
      popup.className = 'vg-pk';
      popup.setAttribute('role', 'dialog');
      popup.setAttribute('aria-label', 'انتخاب تاریخ شمسی');
      host().appendChild(popup);
      const r = input.getBoundingClientRect();
      popup.style.position = 'fixed';
      popup.style.top = (r.bottom + 4) + 'px';
      popup.style.right = Math.max(4, window.innerWidth - r.right) + 'px';
      draw();
      document.addEventListener('mousedown', outside, true);
    }
    input.addEventListener('click', () => { popup ? close() : open(); });
    input.addEventListener('keydown', e => { if (e.key === 'Escape') close(); });
    input.form && input.form.addEventListener('reset', close);
  }
  function init(root) { (root || document).querySelectorAll('input[data-jalali-picker]').forEach(attach); }
  return { monthGrid, format, attach, init };
})();
