<?php
defined('ABSPATH') || exit;
/** Dates are ISO Gregorian in storage; all calculations use calendar days without DST arithmetic. */
class Vetra_Gantt_Scheduler {
    private static function date($value) { return new DateTimeImmutable($value, new DateTimeZone('UTC')); }
    private static function shift($date, $days) { return $date->modify(($days >= 0 ? '+' : '') . $days . ' days'); }
    private static function working($date, $calendar) {
        return in_array((int) $date->format('w'), $calendar['days'], true) && !in_array($date->format('Y-m-d'), $calendar['holidays'], true);
    }
    private static function snap($date, $calendar) {
        for ($i = 0; $i < 3660 && !self::working($date, $calendar); $i++) $date = self::shift($date, 1);
        if (!self::working($date, $calendar)) throw new InvalidArgumentException('در بازه مجاز، روز کاری قابل استفاده یافت نشد.');
        return $date;
    }
    private static function move_work($date, $steps, $calendar) {
        if ($steps === 0) return $date;
        $sign = $steps > 0 ? 1 : -1;
        $limit = abs($steps) * 8 + 3660;
        for ($i = 0, $attempts = 0; $i < abs($steps) && $attempts < $limit; $attempts++) {
            $date = self::shift($date, $sign);
            if (self::working($date, $calendar)) $i++;
        }
        if ($i < abs($steps)) throw new InvalidArgumentException('محاسبه روز کاری در بازه مجاز ممکن نشد.');
        return $date;
    }
    private static function max_date($a, $b) { return $a > $b ? $a : $b; }
    public static function calculate($project, $rows, $deps) {
        $days = array_values(array_unique(array_filter(array_map('intval', explode(',', (string) $project['workdays'])), function ($day) { return $day >= 0 && $day <= 6; })));
        if (!$days) $days = array(0, 1, 2, 3, 4, 6);
        $holidays = json_decode($project['holidays'] ?: '[]', true);
        if (!is_array($holidays)) $holidays = array();
        $holidays = array_values(array_filter($holidays, function ($date) { return is_string($date) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date); }));
        $calendar = array('days' => $days, 'holidays' => $holidays);
        $by_id = array(); $incoming = array(); $children = array();
        foreach ($rows as $row) {
            $by_id[(int) $row['id']] = $row;
            if ($row['parent_id']) $children[(int) $row['parent_id']][] = (int) $row['id'];
        }
        foreach ($deps as $dep) $incoming[(int) $dep['task_id']][] = $dep;
        $done = array(); $visiting = array();
        $solve = function ($id) use (&$solve, &$done, &$visiting, $by_id, $incoming, $children, $project, $calendar) {
            if (isset($done[$id])) return $done[$id];
            if (isset($visiting[$id])) throw new InvalidArgumentException('چرخه در ساختار یا وابستگی فعالیت‌ها وجود دارد.');
            if (!isset($by_id[$id])) throw new InvalidArgumentException('وابستگی به فعالیت نامعتبر است.');
            $visiting[$id] = true;
            $row = $by_id[$id];
            if ($row['task_type'] === 'summary' && !empty($children[$id])) {
                $first = null; $last = null; $weight = 0;
                foreach ($children[$id] as $child_id) {
                    $child = $solve($child_id);
                    $start = self::date($child['start_date']); $end = self::date($child['end_date']);
                    $first = $first === null || $start < $first ? $start : $first;
                    $last = $last === null || $end > $last ? $end : $last;
                    $weight += (float) $child['weight_percent'];
                }
                $row['start_date'] = $first->format('Y-m-d'); $row['end_date'] = $last->format('Y-m-d');
                $row['weight_percent'] = $weight;
                $count = 0;
                for ($day = $first; $day <= $last; $day = self::shift($day, 1)) if (self::working($day, $calendar)) $count++;
                $row['duration'] = $count;
            } elseif ($row['schedule_mode'] === 'auto') {
                $duration = $row['task_type'] === 'milestone' ? 0 : max(1, (int) $row['duration']);
                $base = self::snap(self::date($row['start_date'] ?: $project['start_date']), $calendar);
                $start = $base; $finish_constraint = null;
                foreach ($incoming[$id] ?? array() as $dep) {
                    $pred = $solve((int) $dep['depends_on']);
                    $anchor = self::date(in_array($dep['dep_type'], array('FS', 'FF'), true) ? $pred['end_date'] : $pred['start_date']);
                    $lag = (int) $dep['lag_days'];
                    if ($dep['dep_type'] === 'FS') $candidate = self::move_work($anchor, $lag + 1, $calendar);
                    else $candidate = self::move_work($anchor, $lag, $calendar);
                    if (in_array($dep['dep_type'], array('FS', 'SS'), true)) $start = self::max_date($start, $candidate);
                    else $finish_constraint = $finish_constraint === null ? $candidate : self::max_date($finish_constraint, $candidate);
                }
                if ($finish_constraint !== null) $start = self::max_date($start, self::move_work($finish_constraint, -max(0, $duration - 1), $calendar));
                $start = self::snap($start, $calendar);
                $end = $duration ? self::move_work($start, $duration - 1, $calendar) : $start;
                $row['start_date'] = $start->format('Y-m-d'); $row['end_date'] = $end->format('Y-m-d');
            } else {
                if (!$row['start_date'] || !$row['end_date']) throw new InvalidArgumentException('تاریخ شروع و پایان برای فعالیت دستی لازم است.');
                if ($row['end_date'] < $row['start_date']) throw new InvalidArgumentException('پایان پیش از شروع است.');
            }
            unset($visiting[$id]);
            return $done[$id] = $row;
        };
        foreach (array_keys($by_id) as $id) $solve($id);
        return array_map(function ($r) use ($done) { return $done[(int) $r['id']]; }, $rows);
    }
}
