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

// optional parsing must return null instead of throwing, so auto-mode tasks with no end date save correctly
assert.equal(J.parseOptional(''), null);
assert.equal(J.parseOptional('   '), null);
assert.equal(J.parseOptional(null), null);
assert.equal(J.parseOptional('1405/06/29'), '2026-09-20');
assert.throws(() => J.parseOptional('xyz'));

// calendar helpers used by the dropdown picker
assert.equal(J.isLeap(1403), true, '1403 must be leap');
assert.equal(J.isLeap(1405), false, '1405 must not be leap');
assert.equal(J.monthLength(1403, 12), 30, 'leap Esfand has 30 days');
assert.equal(J.monthLength(1405, 12), 29, 'common Esfand has 29 days');
assert.equal(J.monthLength(1405, 1), 31, 'Farvardin has 31 days');
assert.equal(J.monthLength(1405, 7), 30, 'Mehr has 30 days');
assert.equal(J.fromParts(1403, 12, 30), '2025-03-20', 'leap day converts');
assert.equal(J.fromParts(1405, 12, 30), null, 'invalid leap day rejected');
assert.equal(J.fromParts(1405, 7, 31), null, 'invalid month length rejected');
assert.deepEqual(J.toParts('2026-09-20'), [1405, 6, 29]);
assert.equal(J.weekdayIndex('2026-09-20'), 1, 'Sunday must map to 1 in Saturday-first week');
assert.equal(J.weekdayIndex('2026-09-19'), 0, 'Saturday must map to 0');
assert.match(J.today(), /^\d{4}-\d{2}-\d{2}$/, 'today must be ISO');

console.log('jalali: ok');
