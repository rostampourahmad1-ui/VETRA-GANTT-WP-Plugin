<?php
defined('ABSPATH') || exit;
class Vetra_Gantt_RestAPI {
    public static function register() {
        foreach (array(
            array('/projects', 'GET', 'projects'), array('/projects', 'POST', 'create_project'),
            array('/projects/(?P<id>\d+)', 'GET', 'project'), array('/projects/(?P<id>\d+)', 'PUT', 'update_project'), array('/projects/(?P<id>\d+)', 'DELETE', 'delete_project'),
            array('/projects/(?P<id>\d+)/tasks', 'GET', 'tasks'),
            array('/projects/(?P<id>\d+)/tasks', 'POST', 'create_task'),
            array('/projects/(?P<id>\d+)/reorder', 'POST', 'reorder_tasks'),
            array('/tasks/(?P<id>\d+)', 'PUT', 'update_task'),
            array('/tasks/(?P<id>\d+)', 'DELETE', 'delete_task'),
            array('/holidays', 'GET', 'holidays')
        ) as $route) register_rest_route('vetra-gantt/v1', $route[0], array(
            'methods' => $route[1], 'callback' => array(__CLASS__, $route[2]),
            'permission_callback' => array(__CLASS__, 'authorize')
        ));
    }
    public static function authorize($request) {
        if (!is_user_logged_in() || !current_user_can('edit_posts')) return new WP_Error('vg_forbidden', 'دسترسی مجاز نیست.', array('status' => 403));
        if (!in_array($request->get_method(), array('GET', 'HEAD'), true)) {
            $rate_key = 'vg_rate_' . get_current_user_id();
            $rate_count = (int) get_transient($rate_key);
            if ($rate_count >= 120) return new WP_Error('vg_rate_limit', 'تعداد درخواست‌ها بیش از حد مجاز است. کمی بعد دوباره تلاش کنید.', array('status' => 429));
            set_transient($rate_key, $rate_count + 1, MINUTE_IN_SECONDS);
        }
        $path = $request->get_route();
        $project_id = 0;
        if (preg_match('~/tasks/(\d+)$~', $path, $match)) {
            global $wpdb;
            $project_id = (int) $wpdb->get_var($wpdb->prepare('SELECT project_id FROM ' . Vetra_Gantt_Database::table('tasks') . ' WHERE id=%d', (int) $match[1]));
        } elseif (preg_match('~/projects/(\d+)~', $path, $match)) $project_id = (int) $match[1];
        if ($project_id) {
            $project = Vetra_Gantt_Database::project($project_id);
            if (!$project) return new WP_Error('vg_not_found', 'پروژه یافت نشد.', array('status' => 404));
            if ((int) $project['created_by'] !== get_current_user_id() && !current_user_can('manage_options')) return new WP_Error('vg_forbidden', 'دسترسی به پروژه مجاز نیست.', array('status' => 403));
        } elseif (preg_match('~/tasks/\d+$|/projects/\d+~', $path)) return new WP_Error('vg_not_found', 'یافت نشد.', array('status' => 404));
        return true;
    }
    private static function error($message, $status = 400) { return new WP_Error('vg_invalid', $message, array('status' => $status)); }
    private static function valid_date($value) {
        if (!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) return false;
        $d = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $d && $d->format('Y-m-d') === $value;
    }
    private static function project_payload($project) {
        if (!$project) return null;
        $holidays = json_decode($project['holidays'] ?: '[]', true);
        $project['holidays_list'] = is_array($holidays) ? array_values($holidays) : array();
        $project['task_count'] = Vetra_Gantt_Database::project_count((int) $project['id']);
        return $project;
    }
    private static function validated_project($request, $existing = array()) {
        $params = $request->get_json_params();
        if (!is_array($params)) return self::error('بدنه JSON معتبر لازم است.');
        $data = array_merge($existing, $params);
        $title = sanitize_text_field($data['title'] ?? ''); $start = $data['start_date'] ?? '';
        $workdays = $data['workdays'] ?? array(0, 1, 2, 3, 4, 6); $holidays = $data['holidays'] ?? array();
        if (!$title || !self::valid_date($start)) return self::error('عنوان و تاریخ شروع معتبر لازم است.');
        if (!is_array($workdays)) $workdays = explode(',', (string) $workdays);
        $workdays = array_values(array_unique(array_map('intval', $workdays)));
        foreach ($workdays as $day) if ($day < 0 || $day > 6) return self::error('روز کاری نامعتبر است.');
        if (!$workdays) return self::error('حداقل یک روز کاری انتخاب کنید.');
        if (!is_array($holidays) || count($holidays) > 3660) return self::error('فهرست تعطیلات نامعتبر یا بیش از حد مجاز است.');
        $holidays = array_values(array_unique($holidays));
        foreach ($holidays as $holiday) if (!self::valid_date($holiday)) return self::error('تاریخ تعطیل نامعتبر است.');
        sort($workdays); sort($holidays);
        return array('title' => $title, 'start_date' => $start, 'workdays' => implode(',', $workdays), 'holidays' => wp_json_encode($holidays));
    }
    public static function holidays($request) {
        $from = $request->get_param('from'); $to = $request->get_param('to');
        if ($from && !self::valid_date($from)) return self::error('تاریخ مبدأ نامعتبر است.');
        if ($to && !self::valid_date($to)) return self::error('تاریخ مقصد نامعتبر است.');
        if ($from && $to && $to < $from) return self::error('بازه تاریخ نامعتبر است.');
        return array('holidays' => $from || $to ? Vetra_Gantt_Holidays::for_range($from, $to) : Vetra_Gantt_Holidays::all());
    }
    public static function projects() {
        global $wpdb;
        $table = Vetra_Gantt_Database::table('projects');
        $rows = current_user_can('manage_options') ? $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC", ARRAY_A) : $wpdb->get_results($wpdb->prepare("SELECT * FROM $table WHERE created_by=%d ORDER BY id DESC", get_current_user_id()), ARRAY_A);
        return array_map(array(__CLASS__, 'project_payload'), $rows);
    }
    public static function create_project($request) {
        global $wpdb;
        $data = self::validated_project($request); if (is_wp_error($data)) return $data;
        $data['created_by'] = get_current_user_id();
        $ok = $wpdb->insert(Vetra_Gantt_Database::table('projects'), $data);
        if (!$ok) return self::error('ذخیره پروژه ناموفق بود.', 500);
        $id = (int) $wpdb->insert_id; Vetra_Gantt_Database::audit('create', 'project', $id, $id);
        return new WP_REST_Response(self::project_payload(Vetra_Gantt_Database::project($id)), 201);
    }
    public static function project($request) { return self::project_payload(Vetra_Gantt_Database::project((int) $request['id'])); }
    public static function update_project($request) {
        global $wpdb;
        $id = (int) $request['id']; $data = self::validated_project($request, Vetra_Gantt_Database::project($id));
        if (is_wp_error($data)) return $data;
        if ($wpdb->update(Vetra_Gantt_Database::table('projects'), $data, array('id' => $id)) === false) return self::error('ویرایش پروژه ناموفق بود.', 500);
        Vetra_Gantt_Database::audit('update', 'project', $id, $id);
        return self::project_payload(Vetra_Gantt_Database::project($id));
    }
    public static function delete_project($request) {
        global $wpdb;
        $id = (int) $request['id']; $projects = Vetra_Gantt_Database::table('projects'); $tasks = Vetra_Gantt_Database::table('tasks'); $deps = Vetra_Gantt_Database::table('dependencies');
        $wpdb->query('START TRANSACTION');
        $deleted_deps = $wpdb->query($wpdb->prepare("DELETE d FROM $deps d INNER JOIN $tasks t ON (t.id=d.task_id OR t.id=d.depends_on) WHERE t.project_id=%d", $id));
        $deleted_tasks = $wpdb->delete($tasks, array('project_id' => $id)); $deleted_project = $wpdb->delete($projects, array('id' => $id));
        if ($deleted_deps === false || $deleted_tasks === false || !$deleted_project) { $wpdb->query('ROLLBACK'); return self::error('حذف پروژه ناموفق بود.', 500); }
        $wpdb->query('COMMIT'); Vetra_Gantt_Database::audit('delete', 'project', $id, $id, array('tasks' => (int) $deleted_tasks));
        return array('deleted' => true);
    }
    public static function tasks($request) {
        $project = Vetra_Gantt_Database::project((int) $request['id']);
        $rows = Vetra_Gantt_Database::tasks((int) $project['id']);
        $deps = Vetra_Gantt_Database::dependencies((int) $project['id']);
        try { $rows = Vetra_Gantt_Scheduler::calculate($project, $rows, $deps); }
        catch (InvalidArgumentException $e) { return self::error($e->getMessage(), 409); }
        return array('project' => $project, 'tasks' => $rows, 'dependencies' => $deps);
    }
    private static function validated($request, $project_id, $id = 0) {
        $params = $request->get_json_params();
        if (!is_array($params)) return self::error('بدنه JSON معتبر لازم است.');
        global $wpdb;
        $table = Vetra_Gantt_Database::table('tasks');
        $existing = $id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id=%d", $id), ARRAY_A) : array();
        $data = array_merge($existing ?: array(), $params);
        $title = sanitize_text_field($data['title'] ?? '');
        $mode = $data['schedule_mode'] ?? 'auto'; $type = $data['task_type'] ?? 'task';
        $start = $data['start_date'] ?? null; $end = $data['end_date'] ?? null;
        $parent = !empty($data['parent_id']) ? (int) $data['parent_id'] : null;
        if (!$title || !in_array($mode, array('auto', 'manual'), true) || !in_array($type, array('task', 'summary', 'milestone'), true)) return self::error('عنوان یا نوع فعالیت نامعتبر است.');
        if (($start && !self::valid_date($start)) || ($end && !self::valid_date($end))) return self::error('تاریخ میلادی نامعتبر است.');
        if ($mode === 'manual' && (!$start || !$end || $end < $start)) return self::error('بازه تاریخ دستی نامعتبر است.');
        if ($parent) {
            $parent_row = $wpdb->get_row($wpdb->prepare("SELECT project_id,task_type FROM $table WHERE id=%d", $parent), ARRAY_A);
            if (!$parent_row || (int) $parent_row['project_id'] !== $project_id || $parent === $id || $parent_row['task_type'] !== 'summary') return self::error('سرگروه باید یک فعالیت خلاصه در همان پروژه باشد.');
            $ancestors = array(); $cursor = $parent;
            while ($cursor && !isset($ancestors[$cursor])) {
                if ($cursor === $id) return self::error('چرخه در WBS مجاز نیست.');
                $ancestors[$cursor] = true;
                $cursor = (int) $wpdb->get_var($wpdb->prepare("SELECT parent_id FROM $table WHERE id=%d", $cursor));
            }
            if ($cursor) return self::error('چرخه در WBS مجاز نیست.');
        }
        $deps = $params['dependencies'] ?? null;
        if ($deps !== null) {
            if (!is_array($deps) || count($deps) > 100) return self::error('وابستگی نامعتبر یا بیش از حد مجاز است.');
            $seen_dependencies = array();
            foreach ($deps as $dep) {
                if (!is_array($dep) || !in_array($dep['dep_type'] ?? '', array('FS', 'SS', 'FF', 'SF'), true) || !isset($dep['depends_on']) || !filter_var($dep['depends_on'], FILTER_VALIDATE_INT) || !isset($dep['lag_days']) || !filter_var($dep['lag_days'], FILTER_VALIDATE_INT) && (string) $dep['lag_days'] !== '0') return self::error('فرمت وابستگی نامعتبر است.');
                $depends_on = (int) $dep['depends_on'];
                if (isset($seen_dependencies[$depends_on])) return self::error('هر پیش‌نیاز را فقط یک‌بار وارد کنید.');
                $seen_dependencies[$depends_on] = true;
                if ($depends_on === $id || (int) $wpdb->get_var($wpdb->prepare("SELECT project_id FROM $table WHERE id=%d", $depends_on)) !== $project_id) return self::error('پیش‌نیاز باید فعالیت دیگر در همان پروژه باشد.');
            }
        }
        $duration = (int) ($data['duration'] ?? 1); $weight = (float) ($data['weight_percent'] ?? 0);
        if ($duration < 0 || $duration > 36500 || $weight < 0 || $weight > 100) return self::error('مدت یا سهم نامعتبر است.');
        return array('row' => array(
            'project_id' => $project_id, 'parent_id' => $parent, 'wbs_code' => $existing['wbs_code'] ?? '',
            'title' => $title, 'start_date' => $start ?: null, 'end_date' => $end ?: null,
            'duration' => $type === 'milestone' ? 0 : max(1, $duration), 'weight_percent' => $weight,
            'schedule_mode' => $mode, 'task_type' => $type, 'sort_order' => (int) ($data['sort_order'] ?? 0)
        ), 'dependencies' => $deps);
    }
    private static function save($request, $id = 0) {
        global $wpdb;
        $task_table = Vetra_Gantt_Database::table('tasks');
        $dep_table = Vetra_Gantt_Database::table('dependencies');
        $project_id = $id ? (int) $wpdb->get_var($wpdb->prepare("SELECT project_id FROM $task_table WHERE id=%d", $id)) : (int) $request['id'];
        $valid = self::validated($request, $project_id, $id);
        if (is_wp_error($valid)) return $valid;
        if (!$id) $valid['row']['sort_order'] = 1 + (int) $wpdb->get_var($wpdb->prepare("SELECT COALESCE(MAX(sort_order),0) FROM $task_table WHERE project_id=%d", $project_id));
        $wpdb->query('START TRANSACTION');
        $ok = $id ? $wpdb->update($task_table, $valid['row'], array('id' => $id)) : $wpdb->insert($task_table, $valid['row']);
        if ($ok === false) {
            $wpdb->query('ROLLBACK');
            return self::error('ذخیره ناموفق بود.', 500);
        }
        $id = $id ?: (int) $wpdb->insert_id;
        if ($valid['dependencies'] !== null) {
            if ($wpdb->delete($dep_table, array('task_id' => $id)) === false) {
                $wpdb->query('ROLLBACK');
                return self::error('ذخیره وابستگی‌ها ناموفق بود.', 500);
            }
            foreach ($valid['dependencies'] as $dep) {
                $inserted = $wpdb->insert($dep_table, array('task_id' => $id, 'depends_on' => (int) $dep['depends_on'], 'dep_type' => $dep['dep_type'], 'lag_days' => (int) $dep['lag_days']));
                if ($inserted === false) {
                    $wpdb->query('ROLLBACK');
                    return self::error('ذخیره وابستگی‌ها ناموفق بود.', 500);
                }
            }
        }
        if (!Vetra_Gantt_Database::rebuild_wbs($project_id)) {
            $wpdb->query('ROLLBACK');
            return self::error('بازسازی WBS ناموفق بود.', 409);
        }
        try {
            Vetra_Gantt_Scheduler::calculate(Vetra_Gantt_Database::project($project_id), Vetra_Gantt_Database::tasks($project_id), Vetra_Gantt_Database::dependencies($project_id));
        } catch (InvalidArgumentException $e) {
            $wpdb->query('ROLLBACK');
            return self::error($e->getMessage(), 409);
        }
        $wpdb->query('COMMIT');
        Vetra_Gantt_Database::audit($request->get_method() === 'POST' ? 'create' : 'update', 'task', $id, $project_id);
        return new WP_REST_Response(array('id' => $id), $request->get_method() === 'POST' ? 201 : 200);
    }
    public static function create_task($request) { return self::save($request); }
    public static function update_task($request) { return self::save($request, (int) $request['id']); }
    public static function delete_task($request) {
        global $wpdb;
        $id = (int) $request['id'];
        $tasks = Vetra_Gantt_Database::table('tasks'); $deps = Vetra_Gantt_Database::table('dependencies');
        if ($wpdb->get_var($wpdb->prepare("SELECT id FROM $tasks WHERE parent_id=%d LIMIT 1", $id))) return self::error('ابتدا فعالیت‌های زیرمجموعه را حذف کنید.', 409);
        $wpdb->query('START TRANSACTION');
        $project_id = (int) $wpdb->get_var($wpdb->prepare("SELECT project_id FROM $tasks WHERE id=%d", $id));
        if ($wpdb->delete($deps, array('task_id' => $id)) === false || $wpdb->delete($deps, array('depends_on' => $id)) === false || !$wpdb->delete($tasks, array('id' => $id)) || !Vetra_Gantt_Database::rebuild_wbs($project_id)) {
            $wpdb->query('ROLLBACK');
            return self::error('حذف ناموفق بود.', 500);
        }
        $wpdb->query('COMMIT');
        Vetra_Gantt_Database::audit('delete', 'task', $id, $project_id);
        return array('deleted' => true);
    }
    public static function reorder_tasks($request) {
        global $wpdb;
        $project_id = (int) $request['id']; $params = $request->get_json_params(); $items = $params['items'] ?? null; $table = Vetra_Gantt_Database::table('tasks');
        if (!is_array($items) || count($items) > 5000) return self::error('ساختار ترتیب نامعتبر است.');
        $rows = Vetra_Gantt_Database::tasks($project_id);
        if (count($items) !== count($rows)) return self::error('فهرست فعالیت‌ها کامل نیست.', 409);
        $types = array(); foreach ($rows as $row) $types[(int) $row['id']] = $row['task_type'];
        $parents = array(); $seen = array();
        foreach ($items as $item) {
            $id = (int) ($item['id'] ?? 0); $parent = !empty($item['parent_id']) ? (int) $item['parent_id'] : 0;
            if (!$id || !isset($types[$id]) || isset($seen[$id]) || ($parent && (!isset($types[$parent]) || $types[$parent] !== 'summary'))) return self::error('فعالیت یا سرگروه نامعتبر است.', 409);
            $seen[$id] = true; $parents[$id] = $parent;
        }
        foreach ($parents as $id => $parent) {
            $path = array($id => true);
            while ($parent) { if (isset($path[$parent])) return self::error('چرخه در WBS مجاز نیست.', 409); $path[$parent] = true; $parent = $parents[$parent] ?? 0; }
        }
        $wpdb->query('START TRANSACTION');
        foreach ($items as $index => $item) {
            $parent = !empty($item['parent_id']) ? (int) $item['parent_id'] : null;
            if ($wpdb->update($table, array('parent_id' => $parent, 'sort_order' => $index + 1), array('id' => (int) $item['id'], 'project_id' => $project_id)) === false) { $wpdb->query('ROLLBACK'); return self::error('ذخیره ترتیب ناموفق بود.', 500); }
        }
        if (!Vetra_Gantt_Database::rebuild_wbs($project_id)) { $wpdb->query('ROLLBACK'); return self::error('بازسازی WBS ناموفق بود.', 409); }
        $wpdb->query('COMMIT'); Vetra_Gantt_Database::audit('reorder', 'project', $project_id, $project_id);
        return array('updated' => true);
    }
}
