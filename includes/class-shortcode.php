<?php
defined('ABSPATH') || exit;
class Vetra_Gantt_Shortcode {
    public static function register() { add_shortcode('vetra_gantt', array(__CLASS__, 'render')); }
    public static function render($atts) {
        $atts = shortcode_atts(array('project_id' => 0, 'height' => 700), $atts, 'vetra_gantt');
        $project_id = absint($atts['project_id']);
        $height = max(400, min(1200, absint($atts['height'])));
        $project = Vetra_Gantt_Database::project($project_id);
        if (!$project) return '<p>پروژه یافت نشد.</p>';
        if (!is_user_logged_in() || !current_user_can('edit_posts') || ((int) $project['created_by'] !== get_current_user_id() && !current_user_can('manage_options'))) return '<p>دسترسی به این پروژه مجاز نیست.</p>';
        wp_enqueue_style('vetra-gantt', VG_URL . 'assets/css/vetra-gantt.css', array(), VG_VERSION);
        wp_enqueue_style('vetra-gantt-extensions', VG_URL . 'assets/css/vetra-gantt-extensions.css', array('vetra-gantt'), VG_VERSION);
        wp_enqueue_style('vetra-datepicker', VG_URL . 'assets/css/vetra-datepicker.css', array(), VG_VERSION);
        wp_enqueue_script('vetra-jalali', VG_URL . 'assets/js/vetra-jalali.js', array(), VG_VERSION, true);
        wp_enqueue_script('vetra-predecessors', VG_URL . 'assets/js/vetra-predecessors.js', array(), VG_VERSION, true);
        wp_enqueue_script('vetra-datepicker', VG_URL . 'assets/js/vetra-datepicker.js', array('vetra-jalali'), VG_VERSION, true);
        wp_enqueue_script('vetra-gantt', VG_URL . 'assets/js/vetra-gantt.js', array('vetra-jalali', 'vetra-predecessors', 'vetra-datepicker'), VG_VERSION, true);
        wp_localize_script('vetra-gantt', 'VG_CONFIG', array('restBase' => esc_url_raw(rest_url('vetra-gantt/v1')), 'nonce' => wp_create_nonce('wp_rest'), 'defaultZoom' => get_option('vg_default_zoom', 'week'), 'theme' => get_option('vg_theme', 'system'), 'glass' => (bool) get_option('vg_glass', true)));
        ob_start(); include VG_PATH . 'templates/gantt-view.php'; return ob_get_clean();
    }
}
