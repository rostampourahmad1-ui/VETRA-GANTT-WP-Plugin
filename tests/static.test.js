'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const root = path.resolve(__dirname, '..');
const read = file => fs.readFileSync(path.join(root, file), 'utf8');

const main = read('vetra-gantt.php');
const api = read('includes/class-rest-api.php');
const db = read('includes/class-database.php');
const template = read('templates/gantt-view.php');
const admin = read('includes/class-admin.php');

assert.match(main, /Version: 0\.6\.0/);
assert.match(main, /class-admin\.php/);
assert.match(api, /update_project/);
assert.match(api, /delete_project/);
assert.match(api, /reorder_tasks/);
assert.match(api, /current_user_can\('manage_options'\)/);
assert.match(api, /vg_rate_limit/);
assert.match(api, /START TRANSACTION/);
assert.match(api, /ROLLBACK/);
assert.match(api, /Vetra_Gantt_Database::audit/);
assert.match(db, /audit_log/);
assert.match(db, /rebuild_wbs/);
assert.doesNotMatch(template, /name="wbs_code"/);
assert.match(template, /vg-export/);
assert.match(admin, /current_user_can\('edit_posts'\)/);

for (const file of ['assets/js/vetra-jalali.js','assets/js/vetra-gantt.js','assets/js/vetra-admin.js','includes/class-rest-api.php','includes/class-database.php']) {
  const text = read(file);
  assert.ok(!/TODO|FIXME|not implemented/i.test(text), `${file} contains unfinished marker`);
}
console.log('static contracts: ok');
