'use strict';
const assert = require('node:assert/strict');
global.window = {};
require('../assets/js/vetra-jalali.js');
window.document = undefined; // module load must not touch DOM
require('../assets/js/vetra-datepicker.js');
const D = window.VetraDatePicker;
const J = window.VetraJalali;

// a full month grid is a multiple of 7 cells, days are consecutive and complete
for (const [jy, jm] of [[1405, 1], [1405, 6], [1405, 12], [1403, 12], [1404, 7]]) {
  const cells = D.monthGrid(jy, jm);
  assert.equal(cells.length % 7, 0, `grid for ${jy}/${jm} must align to weeks`);
  const days = cells.filter(Boolean);
  assert.equal(days.length, J.monthLength(jy, jm), `grid ${jy}/${jm} day count`);
  days.forEach((cell, i) => {
    assert.equal(cell.jd, i + 1, `${jy}/${jm} day order at ${i}`);
    assert.deepEqual(J.toParts(cell.iso), [jy, jm, i + 1], `${jy}/${jm}/${i + 1} iso`);
  });
  const real = cells.findIndex(Boolean);
  const last = cells.map(c => Boolean(c)).lastIndexOf(true);
  assert.ok(real === 0 || cells.slice(0, real).every(c => c === null), 'leading blanks only');
  assert.ok(cells.slice(real, last + 1).every(Boolean), 'no nulls between days');
}

// Saturday must start in column 0 (RTL week order starts with شنبه)
assert.equal(D.monthGrid(1405, 6)[0], null, '1405/06/01 is Sunday, column 0 must be blank');
assert.equal(D.monthGrid(1405, 6)[1].jd, 1, 'first real cell is day 1 at column 1');

// leap-year Esfand grid contains day 30 in 1403 but only 29 in 1405
assert.equal(D.monthGrid(1403, 12).filter(Boolean).pop().jd, 30, 'leap Esfand must end at 30');
assert.equal(D.monthGrid(1405, 12).filter(Boolean).pop().jd, 29, 'common Esfand must end at 29');

// format produces the same Jalali text the parser accepts
assert.equal(J.parse(D.format(1405, 6, 29)), '2026-09-20', 'formatted text must round-trip through parse');

console.log('datepicker: ok');
