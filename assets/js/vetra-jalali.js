/* Pure Gregorian/Jalali conversion, arithmetic valid for contemporary project dates. */
window.VetraJalali = (() => {
  function gregorianToJalali(gy, gm, gd) {
    const gdm = [0,31,59,90,120,151,181,212,243,273,304,334];
    const gy2 = gm > 2 ? gy + 1 : gy;
    let days = 355666 + 365 * gy + Math.floor((gy2 + 3) / 4) - Math.floor((gy2 + 99) / 100) + Math.floor((gy2 + 399) / 400) + gd + gdm[gm - 1];
    let jy = -1595 + 33 * Math.floor(days / 12053); days %= 12053;
    jy += 4 * Math.floor(days / 1461); days %= 1461;
    if (days > 365) { jy += Math.floor((days - 1) / 365); days = (days - 1) % 365; }
    const jm = days < 186 ? 1 + Math.floor(days / 31) : 7 + Math.floor((days - 186) / 30);
    const jd = 1 + (days < 186 ? days % 31 : (days - 186) % 30);
    return [jy, jm, jd];
  }
  function jalaliToGregorian(jy, jm, jd) {
    jy += 1595;
    let days = -355668 + 365 * jy + Math.floor(jy / 33) * 8 + Math.floor(((jy % 33) + 3) / 4) + jd + (jm < 7 ? (jm - 1) * 31 : (jm - 7) * 30 + 186);
    let gy = 400 * Math.floor(days / 146097); days %= 146097;
    if (days > 36524) { gy += 100 * Math.floor(--days / 36524); days %= 36524; if (days >= 365) days++; }
    gy += 4 * Math.floor(days / 1461); days %= 1461;
    if (days > 365) { gy += Math.floor((days - 1) / 365); days = (days - 1) % 365; }
    let gd = days + 1;
    const months = [0,31,((gy % 4 === 0 && gy % 100 !== 0) || gy % 400 === 0) ? 29 : 28,31,30,31,30,31,31,30,31,30,31];
    let gm = 1; while (gm <= 12 && gd > months[gm]) gd -= months[gm++];
    return [gy, gm, gd];
  }
  const pad = n => String(n).padStart(2, '0');
  function display(iso) { if (!iso) return '—'; const [y,m,d] = iso.split('-').map(Number); return gregorianToJalali(y,m,d).map((v,i) => i ? pad(v) : v).join('/'); }
  function parse(text) {
    if (!text || !text.trim()) throw Error('تاریخشمسی نامعتبر است.');
    const normalized = text.replace(/[۰-۹]/g, c => '۰۱۲۳۴۵۶۷۸۹'.indexOf(c)).replace(/[٠-٩]/g, c => '٠١٢٣٤٥٦٧٨٩'.indexOf(c));
    const m = /^(\d{4})[/-](\d{1,2})[/-](\d{1,2})$/.exec(normalized.trim());
    if (!m) throw Error('تاریخ شمسی نامعتبر است.');
    const parts = m.slice(1).map(Number), g = jalaliToGregorian(...parts);
    if (parts[1] < 1 || parts[1] > 12 || parts[2] < 1 || parts[2] > 31 || gregorianToJalali(...g).join('/') !== parts.join('/')) throw Error('تاریخ شمسی نامعتبر است.');
    return g.map((v,i) => i ? pad(v) : v).join('-');
  }
  return { display, parse, gregorianToJalali, jalaliToGregorian };
})();
