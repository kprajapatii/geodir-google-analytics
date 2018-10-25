<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * Dashboard. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * this starts the plugin.
 *
 * @since             1.0.0
 * @package           GeoDir_Google_Analytics
 *
 * @wordpress-plugin
 * Plugin Name:       GeoDirectory Google Analytics
 * Plugin URI:        https://wpgeodirectory.com
 * Description:       Allows Google Analytics tracking for GeoDirectory listings.
 * Version:           2.0.0.1-beta
 * Author:            GeoDirectory
 * Author URI:        https://wpgeodirectory.com/
 * Requires at least: 4.9
 * Tested up to:      4.9.9
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       geodir-ga
 * Domain Path:       /languages
 * Update URL:        https://github.com/AyeCode/geodir-google-analytics/
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

if ( !defined( 'GEODIR_GA_VERSION' ) ) {
	define( 'GEODIR_GA_VERSION', '2.0.0.1-beta' );
}

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function geodir_google_analytics() {
    global $geodir_ga;

	if ( !defined( 'GEODIR_GA_PLUGIN_FILE' ) ) {
		define( 'GEODIR_GA_PLUGIN_FILE', __FILE__ );
	}

	/**
	 * The core plugin class that is used to define internationalization,
	 * dashboard-specific hooks, and public-facing site hooks.
	 */
	require_once ( plugin_dir_path( GEODIR_GA_PLUGIN_FILE ) . 'includes/class-geodir-google-analytics.php' );

    $geodir_ga = GeoDir_Google_Analytics::instance();
}
add_action( 'geodirectory_loaded', 'geodir_google_analytics' );
