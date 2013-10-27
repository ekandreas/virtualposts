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

/**
 * Copyright (c) 2013 Andreas Ek (email : andreas@flowcom.se)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2 or, at
 * your discretion, any later version, as published by the Free
 * Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

/**
 * Built using grunt-wp-plugin
 * Copyright (c) 2013 10up, LLC
 * https://github.com/10up/grunt-wp-plugin
 */

// Useful global constants
define( 'VPP__VERSION', '0.1.0' );
define( 'VPP__URL',     plugin_dir_url( __FILE__ ) );
define( 'VPP__PATH',    dirname( __FILE__ ) . '/' );

include_once 'includes/php_fast_cache.php';
include_once 'includes/settings.php';
include_once 'includes/settings-ui.php';
include_once 'includes/feeds.php';
include_once 'includes/virtual.php';

/**
 * Default initialization for the plugin:
 * - Registers the default textdomain.
 */
function vpp__init() {
	$locale = apply_filters( 'plugin_locale', get_locale(), 'vpp_' );
	load_textdomain( 'vpp_', WP_LANG_DIR . '/vpp_/vpp_-' . $locale . '.mo' );
	load_plugin_textdomain( 'vpp_', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}

/**
 * Activate the plugin
 */
function vpp__activate() {
	// First load the init scripts in case any rewrite functionality is being loaded
	vpp__init();

	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'vpp__activate' );

/**
 * Deactivate the plugin
 * Uninstall routines should be in uninstall.php
 */
function vpp__deactivate() {

	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'vpp__deactivate' );

// Wireup actions
add_action( 'init', 'vpp__init' );

// Wireup filters

// Wireup shortcodes
