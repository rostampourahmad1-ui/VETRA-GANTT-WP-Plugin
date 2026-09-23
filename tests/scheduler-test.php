<?php
define('ABSPATH', __DIR__ . '/');
define('VG_PATH', dirname(__DIR__) . '/');
if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($value) { return trim(preg_replace('/[\r\n\t]+/', ' ', (string) $value)); }
}
require_once dirname(__DIR__) . '/includes/class-holidays.php';
require_once dirname(__DIR__) . '/includes/class-scheduler.php';

function vg_assert_same($expected, $actual, $message) {
    if ($expected !== $actual) throw new RuntimeException($message . ': expected ' . $expected . ', got ' . $actual);
}

$project = array('start_date' => '2026-09-20', 'workdays' => '0,1,2,3,4,6', 'holidays' => '[]');
$rows = array(
    array('id'=>'1','parent_id'=>null,'task_type'=>'task','schedule_mode'=>'auto','duration'=>'2','start_date'=>null,'end_date'=>null,'weight_percent'=>'20'),
    array('id'=>'2','parent_id'=>null,'task_type'=>'task','schedule_mode'=>'auto','duration'=>'1','start_date'=>null,'end_date'=>null,'weight_percent'=>'30'),
    array('id'=>'3','parent_id'=>null,'task_type'=>'milestone','schedule_mode'=>'auto','duration'=>'0','start_date'=>null,'end_date'=>null,'weight_percent'=>'0')
);
$deps = array(
    array('task_id'=>'2','depends_on'=>'1','dep_type'=>'FS','lag_days'=>'0'),
    array('task_id'=>'3','depends_on'=>'2','dep_type'=>'FS','lag_days'=>'0')
);
$result = Vetra_Gantt_Scheduler::calculate($project, $rows, $deps);
vg_assert_same('2026-09-20', $result[0]['start_date'], 'task 1 start');
vg_assert_same('2026-09-21', $result[0]['end_date'], 'task 1 end');
vg_assert_same('2026-09-22', $result[1]['start_date'], 'FS successor start');
vg_assert_same('2026-09-23', $result[2]['start_date'], 'milestone start');
vg_assert_same($result[2]['start_date'], $result[2]['end_date'], 'milestone dates');

$cycle = array(array('task_id'=>'1','depends_on'=>'2','dep_type'=>'FS','lag_days'=>'0'), array('task_id'=>'2','depends_on'=>'1','dep_type'=>'FS','lag_days'=>'0'));
try { Vetra_Gantt_Scheduler::calculate($project, $rows, $cycle); throw new RuntimeException('cycle was accepted'); }
catch (InvalidArgumentException $error) { /* expected */ }

// official Jalali holidays must be skipped automatically even when the project stores none:
// 2026-03-20 is Friday (non-workday), 2026-03-21 is Nowruz 1405 (Saturday but official holiday) -> first real workday is Sunday 2026-03-22
$nowruz = array('start_date' => '2026-03-20', 'workdays' => '0,1,2,3,4,6', 'holidays' => '[]');
$nh = array(
    array('id'=>'10','parent_id'=>null,'task_type'=>'task','schedule_mode'=>'auto','duration'=>'1','start_date'=>'2026-03-20','end_date'=>null,'weight_percent'=>'0'),
    array('id'=>'11','parent_id'=>null,'task_type'=>'task','schedule_mode'=>'auto','duration'=>'1','start_date'=>'2026-03-21','end_date'=>null,'weight_percent'=>'0')
);
$nr = Vetra_Gantt_Scheduler::calculate($nowruz, $nh, array());
vg_assert_same('2026-03-22', $nr[0]['start_date'], 'Friday start must snap past weekend and Nowruz');
vg_assert_same('2026-03-22', $nr[1]['start_date'], 'Nowruz itself must snap to the next workday');

// a manually stored holiday must still work on top of the official list
$manual = array('start_date' => '2026-09-20', 'workdays' => '0,1,2,3,4,6', 'holidays' => json_encode(array('2026-09-21')));
$mr = Vetra_Gantt_Scheduler::calculate($manual, array(
    array('id'=>'20','parent_id'=>null,'task_type'=>'task','schedule_mode'=>'auto','duration'=>'2','start_date'=>'2026-09-21','end_date'=>null,'weight_percent'=>'0')
), array());
vg_assert_same('2026-09-22', $mr[0]['start_date'], 'task snaps forward over stored manual holiday');
vg_assert_same('2026-09-23', $mr[0]['end_date'], 'duration counts workdays only');

echo "scheduler: ok\n";
