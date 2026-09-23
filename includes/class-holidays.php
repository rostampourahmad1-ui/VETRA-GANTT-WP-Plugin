<?php
defined('ABSPATH') || exit;
class Vetra_Gantt_Holidays {
    private static function file() { return VG_PATH . 'data/iran-holidays.json'; }
    public static function all() {
        static $cache = null;
        if ($cache !== null) return $cache;
        $raw = file_exists(self::file()) ? file_get_contents(self::file()) : '';
        $data = json_decode($raw, true);
        $rows = is_array($data) && isset($data['holidays']) && is_array($data['holidays']) ? $data['holidays'] : array();
        $clean = array();
        foreach ($rows as $row) {
            $date = is_array($row) && isset($row['date']) ? $row['date'] : '';
            $title = is_array($row) && isset($row['title']) ? sanitize_text_field($row['title']) : '';
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) $clean[] = array('date' => $date, 'title' => $title);
        }
        usort($clean, function ($a, $b) { return strcmp($a['date'], $b['date']); });
        return $cache = $clean;
    }
    public static function for_range($from, $to) {
        $out = array();
        foreach (self::all() as $row) {
            if ($from && $row['date'] < $from) continue;
            if ($to && $row['date'] > $to) continue;
            $out[] = $row;
        }
        return $out;
    }
    public static function dates_in_range($from, $to) {
        return array_values(array_map(function ($row) { return $row['date']; }, self::for_range($from, $to)));
    }
}
