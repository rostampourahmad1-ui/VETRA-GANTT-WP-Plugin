<?php
defined('ABSPATH') || exit;

class Vetra_Gantt_Admin {
    const PAGE = 'vetra-gantt';
    const SETTINGS = 'vetra-gantt-settings';

    public static function register_menu() {
        add_menu_page('وترا گانت', 'وترا گانت', 'edit_posts', self::PAGE, array(__CLASS__, 'render'), 'dashicons-chart-bar', 58);
        add_submenu_page(self::PAGE, 'تنظیمات وترا گانت', 'تنظیمات', 'manage_options', self::SETTINGS, array(__CLASS__, 'render_settings'));
    }

    public static function register_settings() {
        register_setting('vg_settings', 'vg_default_zoom', array('type' => 'string', 'default' => 'week', 'sanitize_callback' => array(__CLASS__, 'sanitize_zoom')));
        register_setting('vg_settings', 'vg_theme', array('type' => 'string', 'default' => 'system', 'sanitize_callback' => array(__CLASS__, 'sanitize_theme')));
        register_setting('vg_settings', 'vg_glass', array('type' => 'boolean', 'default' => true, 'sanitize_callback' => 'rest_sanitize_boolean'));
        add_settings_section('vg_display_section', 'نمایش و رابط کاربری', '__return_false', self::SETTINGS);
        add_settings_field('vg_default_zoom', 'مقیاس زمانی پیش‌فرض', array(__CLASS__, 'zoom_field'), self::SETTINGS, 'vg_display_section');
        add_settings_field('vg_theme', 'تم رنگی', array(__CLASS__, 'theme_field'), self::SETTINGS, 'vg_display_section');
        add_settings_field('vg_glass', 'ظاهر شیشه‌ای', array(__CLASS__, 'glass_field'), self::SETTINGS, 'vg_display_section');
    }

    public static function sanitize_zoom($value) { return in_array($value, array('day', 'week', 'month'), true) ? $value : 'week'; }
    public static function sanitize_theme($value) { return in_array($value, array('system', 'light', 'dark'), true) ? $value : 'system'; }
    public static function zoom_field() {
        $value = get_option('vg_default_zoom', 'week');
        echo '<select name="vg_default_zoom"><option value="day"' . selected($value, 'day', false) . '>روز</option><option value="week"' . selected($value, 'week', false) . '>هفته</option><option value="month"' . selected($value, 'month', false) . '>ماه</option></select>';
    }
    public static function theme_field() {
        $value = get_option('vg_theme', 'system');
        echo '<select name="vg_theme"><option value="system"' . selected($value, 'system', false) . '>هماهنگ با سیستم</option><option value="light"' . selected($value, 'light', false) . '>روشن</option><option value="dark"' . selected($value, 'dark', false) . '>تیره</option></select>';
    }
    public static function glass_field() {
        echo '<label><input type="checkbox" name="vg_glass" value="1" ' . checked(get_option('vg_glass', true), true, false) . '> استفاده از پس‌زمینه شیشه‌ای و نیمه‌شفاف</label>';
    }

    public static function enqueue($hook) {
        if ($hook !== 'toplevel_page_' . self::PAGE) return;
        wp_enqueue_style('vetra-gantt-admin', VG_URL . 'assets/css/vetra-admin.css', array(), VG_VERSION);
        wp_enqueue_style('vetra-datepicker', VG_URL . 'assets/css/vetra-datepicker.css', array(), VG_VERSION);
        wp_enqueue_script('vetra-jalali', VG_URL . 'assets/js/vetra-jalali.js', array(), VG_VERSION, true);
        wp_enqueue_script('vetra-datepicker', VG_URL . 'assets/js/vetra-datepicker.js', array('vetra-jalali'), VG_VERSION, true);
        wp_enqueue_script('vetra-gantt-admin', VG_URL . 'assets/js/vetra-admin.js', array('vetra-jalali', 'vetra-datepicker'), VG_VERSION, true);
        wp_localize_script('vetra-gantt-admin', 'VG_ADMIN', array(
            'restBase' => esc_url_raw(rest_url('vetra-gantt/v1')),
            'nonce' => wp_create_nonce('wp_rest'),
            'shortcode' => '[vetra_gantt project_id="%d" height="700"]'
        ));
    }

    public static function render() {
        if (!current_user_can('edit_posts')) wp_die(esc_html__('دسترسی مجاز نیست.', 'vetra-gantt'));
        include VG_PATH . 'templates/admin-projects.php';
    }

    public static function render_settings() {
        if (!current_user_can('manage_options')) wp_die(esc_html__('دسترسی مجاز نیست.', 'vetra-gantt'));
        include VG_PATH . 'templates/admin-settings.php';
    }
}
