<?php
define('ABSPATH', __DIR__ . '/');
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

echo "scheduler: ok\n";
