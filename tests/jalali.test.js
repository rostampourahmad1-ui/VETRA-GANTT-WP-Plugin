'use strict';
const assert = require('node:assert/strict');
global.window = {};
require('../assets/js/vetra-jalali.js');
const J = window.VetraJalali;

function iso(y, m, d) { return [y, String(m).padStart(2, '0'), String(d).padStart(2, '0')].join('-'); }

for (let year = 1900; year <= 2100; year += 5) {
  for (let month = 1; month <= 12; month++) {
    for (const day of [1, 15, 28]) {
      const date = iso(year, month, day);
      assert.equal(J.parse(J.display(date)), date, `round trip failed for ${date}`);
    }
  }
}

for (const date of ['2024-02-29', '2026-09-20', '2030-03-21', '2000-02-29']) assert.equal(J.parse(J.display(date)), date);
assert.equal(J.parse('۱۴۰۵/۰۶/۲۹'), '2026-09-20');
assert.equal(J.parse('١٤٠٥/٠٦/٢٩'), '2026-09-20');
for (const invalid of ['', 'abc', '1405/13/01', '1405/07/31', '1405/00/10', '1405/01/00']) assert.throws(() => J.parse(invalid));
console.log('jalali: ok');
