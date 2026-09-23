'use strict';
const assert = require('node:assert/strict');

function rebuild(rows) {
  const children = new Map();
  const byId = new Map(rows.map(row => [row.id, row]));
  for (const row of rows) {
    const parent = row.parent_id || 0;
    if (!byId.has(parent) && parent !== 0) throw Error('orphan');
    if (!children.has(parent)) children.set(parent, []);
    children.get(parent).push(row);
  }
  for (const list of children.values()) list.sort((a, b) => a.sort_order - b.sort_order || a.id - b.id);
  const visited = new Set();
  const output = [];
  function walk(parent, prefix) {
    const siblings = children.get(parent) || [];
    siblings.forEach((row, index) => {
      if (visited.has(row.id)) throw Error('cycle');
      visited.add(row.id);
      const code = prefix ? `${prefix}.${index + 1}` : String(index + 1);
      output.push({...row, task_type: children.has(row.id) ? 'summary' : row.task_type, wbs_code: code});
      walk(row.id, code);
    });
  }
  walk(0, '');
  if (visited.size !== rows.length) throw Error('cycle');
  return output;
}

const result = rebuild([
  {id: 10, parent_id: null, task_type: 'task', sort_order: 1},
  {id: 20, parent_id: 10, task_type: 'task', sort_order: 1},
  {id: 30, parent_id: 20, task_type: 'task', sort_order: 1},
  {id: 40, parent_id: 30, task_type: 'milestone', sort_order: 1},
  {id: 50, parent_id: 20, task_type: 'task', sort_order: 2},
  {id: 60, parent_id: 10, task_type: 'task', sort_order: 2},
  {id: 70, parent_id: null, task_type: 'task', sort_order: 2},
]);

assert.deepEqual(result.map(row => row.wbs_code), ['1', '1.1', '1.1.1', '1.1.1.1', '1.1.2', '1.2', '2']);
assert.equal(result.find(row => row.id === 10).task_type, 'summary', 'a parent is promoted to summary automatically');
assert.equal(result.find(row => row.id === 20).task_type, 'summary', 'nested parent is promoted to summary automatically');
assert.equal(result.find(row => row.id === 30).task_type, 'summary', 'deep parent is promoted to summary automatically');
assert.equal(result.find(row => row.id === 40).task_type, 'milestone');

assert.throws(() => rebuild([
  {id: 1, parent_id: 2, task_type: 'task', sort_order: 1},
  {id: 2, parent_id: 1, task_type: 'task', sort_order: 1},
]), /cycle/);

console.log('wbs: ok');
