<?php
/**
 * Plugin Name: Virtual Posts
 * Plugin URI:  https://github.com/EkAndreas/virtualposts
 * Description: Show any feed as your own content in WP
 * Version:     0.1.0
 * Author:      Andreas Ek
 * Author URI:  http://www.flowcom.se
 * License:     GPLv2+
 * Text Domain: vpp_
 * Domain Path: /languages
 */

define( 'VPP__VERSION', '0.1.0' );
define( 'VPP__URL', plugin_dir_url( __FILE__ ) );
define( 'VPP__PATH', dirname( __FILE__ ) . '/' );

include_once 'includes/php_fast_cache.php';
include_once 'includes/settings.php';
include_once 'includes/settings-ui.php';
include_once 'includes/feeds.php';
include_once 'includes/virtual.php';
include_once 'includes/widgetlisting.php';

function vpp__init() {
	$locale = apply_filters( 'plugin_locale', get_locale(), 'vpp_' );
	load_textdomain( 'vpp_', WP_LANG_DIR . '/vpp_/vpp_-' . $locale . '.mo' );
	load_plugin_textdomain( 'vpp_', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}

function vpp__activate() {
	vpp__init();
	flush_rewrite_rules();
	wp_schedule_event( current_time( 'timestamp' ), 'virtualposts', 'virtualposts_cron_feeds' );
	$general = array(
		'apc' => 'auto',
		'interval' => '20'
	);
	VirtualPostsSettings::update( 'general', $general );
}
register_activation_hook( __FILE__, 'vpp__activate' );

function vpp__deactivate() {

	flush_rewrite_rules();
	wp_clear_scheduled_hook( 'virtualposts_cron_feeds' );
	VirtualPostsSettings::delete_options();

}
register_deactivation_hook( __FILE__, 'vpp__deactivate' );

add_action( 'init', 'vpp__init' );

