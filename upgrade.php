<?php
/**
 * Google Analytics Upgrade.
 *
 * @since 1.0.0
 * @package GeoDir_Google_Analytics
 * @author AyeCode Ltd
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( get_option( 'geodir_ga_db_version' ) != GEODIR_GA_VERSION ) {
	/**
     * Include custom database table related functions.
     *
     * @since 1.0.0
     * @package GeoDir_Google_Analytics
     */
    add_action( 'plugins_loaded', 'geodir_ga_upgrade_all', 10 );

    // Upgrade old options to new options before loading the rest GD options.
    if ( GEODIR_GA_VERSION <= '2.0.0.0' ) {
        add_action( 'init', 'geodir_ga_upgrade_200' );
    }
}

/**
 * Upgrade for all versions.
 *
 * @since 2.0.0
 */
function geodir_ga_upgrade_all() {
	
}

/**
 * Upgrade for 2.0.0 version.
 *
 * @since 2.0.0
 */
function geodir_ga_upgrade_200() {
	
}