<?php
/**
 * Plugin Name: Vetra Gantt
 * Description: برنامه‌ریزی پروژه با WBS، گانت و تقویم جلالی
 * Version: 1.1.2
 * Requires PHP: 7.4
 * Text Domain: vetra-gantt
 */
defined('ABSPATH') || exit;
define('VG_VERSION', '1.1.2');
define('VG_PATH', plugin_dir_path(__FILE__));
define('VG_URL', plugin_dir_url(__FILE__));
require_once VG_PATH . 'includes/class-database.php';
require_once VG_PATH . 'includes/class-holidays.php';
require_once VG_PATH . 'includes/class-scheduler.php';
require_once VG_PATH . 'includes/class-rest-api.php';
require_once VG_PATH . 'includes/class-shortcode.php';
require_once VG_PATH . 'includes/class-admin.php';
register_activation_hook(__FILE__, array('Vetra_Gantt_Database', 'install'));
add_action('plugins_loaded', array('Vetra_Gantt_Database', 'maybe_upgrade'));
add_action('init', array('Vetra_Gantt_Shortcode', 'register'));
add_action('rest_api_init', array('Vetra_Gantt_RestAPI', 'register'));
add_action('admin_menu', array('Vetra_Gantt_Admin', 'register_menu'));
add_action('admin_init', array('Vetra_Gantt_Admin', 'register_settings'));
add_action('admin_enqueue_scripts', array('Vetra_Gantt_Admin', 'enqueue'));
