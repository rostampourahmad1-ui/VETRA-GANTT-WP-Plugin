<?php
defined('ABSPATH') || exit;
class Vetra_Gantt_Database {
    public static function table($name) {
        global $wpdb;
        return $wpdb->prefix . 'vg_' . $name;
    }
    public static function install() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $collate = $wpdb->get_charset_collate();
        $projects = self::table('projects');
        $tasks = self::table('tasks');
        $deps = self::table('dependencies');
        $audit = self::table('audit_log');
        dbDelta("CREATE TABLE $projects (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            start_date date NOT NULL,
            workdays varchar(20) NOT NULL DEFAULT '0,1,2,3,4,6',
            holidays longtext NULL,
            created_by bigint(20) unsigned NOT NULL,
            PRIMARY KEY  (id),
            KEY created_by (created_by)
        ) $collate;");
        dbDelta("CREATE TABLE $tasks (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            project_id bigint(20) unsigned NOT NULL,
            parent_id bigint(20) unsigned NULL,
            wbs_code varchar(255) NOT NULL DEFAULT '',
            title varchar(255) NOT NULL,
            start_date date NULL,
            end_date date NULL,
            duration int(11) NOT NULL DEFAULT 1,
            weight_percent decimal(7,3) NOT NULL DEFAULT 0,
            schedule_mode varchar(10) NOT NULL DEFAULT 'auto',
            task_type varchar(12) NOT NULL DEFAULT 'task',
            sort_order int(11) NOT NULL DEFAULT 0,
            PRIMARY KEY  (id),
            KEY project_id (project_id),
            KEY parent_id (parent_id)
        ) $collate;");
        dbDelta("CREATE TABLE $deps (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            task_id bigint(20) unsigned NOT NULL,
            depends_on bigint(20) unsigned NOT NULL,
            dep_type varchar(2) NOT NULL DEFAULT 'FS',
            lag_days int(11) NOT NULL DEFAULT 0,
            PRIMARY KEY  (id),
            KEY task_id (task_id),
            KEY depends_on (depends_on)
        ) $collate;");
        dbDelta("CREATE TABLE $audit (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            project_id bigint(20) unsigned NULL,
            action varchar(60) NOT NULL,
            object_type varchar(30) NOT NULL,
            object_id bigint(20) unsigned NULL,
            details longtext NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY project_created (project_id,created_at),
            KEY user_created (user_id,created_at)
        ) $collate;");
        update_option('vg_db_version', VG_VERSION);
    }
    public static function maybe_upgrade() {
        if (get_option('vg_db_version') !== VG_VERSION) self::install();
    }
    public static function project($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . self::table('projects') . ' WHERE id=%d', $id), ARRAY_A);
    }
    public static function tasks($project_id) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare('SELECT * FROM ' . self::table('tasks') . ' WHERE project_id=%d ORDER BY sort_order,id', $project_id), ARRAY_A);
    }
    public static function dependencies($project_id) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare('SELECT d.* FROM ' . self::table('dependencies') . ' d INNER JOIN ' . self::table('tasks') . ' t ON t.id=d.task_id WHERE t.project_id=%d ORDER BY d.id', $project_id), ARRAY_A);
    }
    public static function project_count($project_id) {
        global $wpdb;
        return (int) $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM ' . self::table('tasks') . ' WHERE project_id=%d', $project_id));
    }
    public static function audit($action, $object_type, $object_id = null, $project_id = null, $details = array()) {
        global $wpdb;
        $wpdb->insert(self::table('audit_log'), array(
            'user_id' => get_current_user_id(), 'project_id' => $project_id, 'action' => $action,
            'object_type' => $object_type, 'object_id' => $object_id,
            'details' => $details ? wp_json_encode($details) : null, 'created_at' => current_time('mysql', true)
        ));
    }
    public static function rebuild_wbs($project_id) {
        global $wpdb;
        $table = self::table('tasks');
        $rows = self::tasks($project_id);
        $children = array();
        foreach ($rows as $row) $children[(int) ($row['parent_id'] ?: 0)][] = (int) $row['id'];
        $visited = array(); $order = 0;
        $walk = function ($parent, $prefix = '') use (&$walk, &$visited, &$order, $children, $table, $wpdb) {
            $position = 0;
            foreach ($children[$parent] ?? array() as $id) {
                if (isset($visited[$id])) return false;
                $visited[$id] = true; $position++; $order++;
                $code = $prefix === '' ? (string) $position : $prefix . '.' . $position;
                if ($wpdb->update($table, array('wbs_code' => $code, 'sort_order' => $order), array('id' => $id)) === false) return false;
                if (!$walk($id, $code)) return false;
            }
            return true;
        };
        return $walk(0) && count($visited) === count($rows);
    }
    public static function normalize_hierarchy($project_id) {
        global $wpdb;
        $table = self::table('tasks');
        $rows = self::tasks($project_id);
        $children = array();
        foreach ($rows as $row) {
            if (!empty($row['parent_id'])) $children[(int) $row['parent_id']] = true;
        }
        foreach ($rows as $row) {
            $id = (int) $row['id'];
            if (isset($children[$id]) && $row['task_type'] !== 'summary') {
                if ($wpdb->update($table, array('task_type' => 'summary'), array('id' => $id, 'project_id' => $project_id)) === false) return false;
            }
        }
        return true;
    }
}
