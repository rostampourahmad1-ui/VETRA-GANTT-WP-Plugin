'use strict';
const assert = require('node:assert/strict');
const data = require('../data/iran-holidays.json');

assert.ok(Array.isArray(data.holidays) && data.holidays.length > 60, 'holiday dataset must be populated');
global.window = {};
require('../assets/js/vetra-jalali.js');
const J = window.VetraJalali;

let prev = '';
for (const row of data.holidays) {
  assert.match(row.date, /^\d{4}-\d{2}-\d{2}$/, `bad ISO date ${row.date}`);
  assert.ok(row.date > prev, `dataset must be strictly sorted at ${row.date}`);
  prev = row.date;
  assert.ok(typeof row.title === 'string' && row.title.length > 0, `missing title for ${row.date}`);
  // every stored date must round-trip through the Jalali calendar (valid Gregorian)
  assert.equal(J.parse(J.display(row.date)), row.date, `unconvertible date ${row.date}`);
}

// anchors: Nowruz 1405 is 2026-03-21, and 2026 must contain the 22 Bahman anniversary
assert.ok(data.holidays.some(h => h.date === '2026-03-21'), 'Nowruz 1405 missing');
assert.ok(data.holidays.some(h => h.date === '2026-02-11'), '22 Bahman 1404 missing');
assert.ok(data.holidays.some(h => h.date === '2025-01-01' || h.date.startsWith('2025-')), 'year 2025 entries missing');

// simulate the PHP for_range filter semantics
function forRange(rows, from, to) { return rows.filter(r => (!from || r.date >= from) && (!to || r.date <= to)); }
const y2026 = forRange(data.holidays, '2026-01-01', '2026-12-31');
assert.ok(y2026.length >= 20 && y2026.length <= 40, `2026 holiday count off: ${y2026.length}`);
assert.deepEqual(forRange(y2026, '2026-04-01', '2026-04-30').map(h => h.date), ['2026-04-01', '2026-04-02', '2026-04-14'], 'April 2026 slice mismatch');

console.log('holidays: ok');
