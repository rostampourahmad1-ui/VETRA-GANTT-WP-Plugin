<?php
defined('ABSPATH') || exit;

class Vetra_Gantt_Admin {
    const PAGE = 'vetra-gantt';

    public static function register_menu() {
        add_menu_page('وترا گانت', 'وترا گانت', 'edit_posts', self::PAGE, array(__CLASS__, 'render'), 'dashicons-chart-bar', 58);
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
}
