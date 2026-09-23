'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
global.window = {};
require('../assets/js/vetra-predecessors.js');
const { parsePredecessors } = window.VetraGanttUtil;
const src = fs.readFileSync(path.resolve(__dirname, '../assets/js/vetra-gantt.js'), 'utf8');

// The grid must consume the shared parser rather than keeping a private copy.
assert.match(src, /window\.VetraGanttUtil/, 'grid must use the shared predecessor parser');
assert.ok(!/function parseDeps/.test(src), 'old duplicated parser must be removed');
assert.match(fs.readFileSync(path.resolve(__dirname, '../includes/class-shortcode.php'), 'utf8'), /vetra-predecessors\.js/, 'shortcode must enqueue the shared parser');

const tasks = [{ id: 101, wbs_code: '1' }, { id: 102, wbs_code: '1.1' }, { id: 103, wbs_code: '2' }];

assert.deepEqual(parsePredecessors('1FS', tasks, 102), [{ depends_on: 101, dep_type: 'FS', lag_days: 0 }], 'must resolve WBS 1, not id 1');
assert.deepEqual(parsePredecessors('1.1SS+3', tasks, 103), [{ depends_on: 102, dep_type: 'SS', lag_days: 3 }]);
assert.deepEqual(parsePredecessors('2FF-2', tasks, 101), [{ depends_on: 103, dep_type: 'FF', lag_days: -2 }]);
assert.deepEqual(parsePredecessors('1, 1.1SF+5', tasks, 103), [
  { depends_on: 101, dep_type: 'FS', lag_days: 0 },
  { depends_on: 102, dep_type: 'SF', lag_days: 5 },
]);
assert.deepEqual(parsePredecessors('', tasks, 101), [], 'empty must mean no predecessors, not error');
assert.throws(() => parsePredecessors('99FS', tasks, 101), /یافت نشد/, 'unknown WBS must be rejected');
assert.throws(() => parsePredecessors('1FS+1FS', tasks, 101), /نامعتبر/, 'malformed must be rejected');
assert.throws(() => parsePredecessors('1FS', tasks, 101), /یافت نشد/, 'self-dependency must be rejected');
assert.deepEqual(parsePredecessors('102FS', tasks, 101), [{ depends_on: 102, dep_type: 'FS', lag_days: 0 }], 'raw ids must also resolve when no WBS matches');

console.log('predecessors: ok');
