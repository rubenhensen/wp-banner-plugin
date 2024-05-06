<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://example.com
 * @since             1.0.0
 * @package           Banner_Me
 *
 * @wordpress-plugin
 * Plugin Name:       Banner Me
 * Plugin URI:        https://hensen.io/wp-banner-plugin
 * Description:       A simple free banner for your page.
 * Version:           1.0.0
 * Author:            Ruben Hensen
 * Author URI:        http://example.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       banner-me
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'BANNER_ME_VERSION', '1.0.7' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-banner-me-activator.php
 */
function activate_banner_me() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-banner-me-activator.php';
	Banner_Me_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-banner-me-deactivator.php
 */
function deactivate_banner_me() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-banner-me-deactivator.php';
	Banner_Me_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_banner_me' );
register_deactivation_hook( __FILE__, 'deactivate_banner_me' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-banner-me.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_banner_me() {

	$plugin = new Banner_Me();
	$plugin->run();

}
run_banner_me();
